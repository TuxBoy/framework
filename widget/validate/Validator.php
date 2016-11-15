<?php
namespace ITRocks\Framework\Widget\Validate;

use ITRocks\Framework\Controller\Main;
use ITRocks\Framework\Controller\Parameter;
use ITRocks\Framework\Dao\Data_Link;
use ITRocks\Framework\Dao\Option;
use ITRocks\Framework\Dao\Option\Exclude;
use ITRocks\Framework\Dao\Option\Only;
use ITRocks\Framework\Plugin\Register;
use ITRocks\Framework\Plugin\Registerable;
use ITRocks\Framework\Reflection;
use ITRocks\Framework\Reflection\Annotation\Class_\Link_Annotation;
use ITRocks\Framework\Reflection\Annotation\Parser;
use ITRocks\Framework\Reflection\Interfaces\Reflection_Class;
use ITRocks\Framework\Reflection\Interfaces\Reflection_Property;
use ITRocks\Framework\Reflection\Link_Class;
use ITRocks\Framework\Reflection\Type;
use ITRocks\Framework\View;
use ITRocks\Framework\View\Html\Template;
use ITRocks\Framework\View\View_Exception;
use ITRocks\Framework\Widget\Validate\Property;
use ITRocks\Framework\Widget\Validate\Property\Mandatory_Annotation;

/**
 * The object validator links validation processes to objects
 */
class Validator implements Registerable
{

	//--------------------------------------------------------------------------------------- $report
	/**
	 * The report is made of validate annotations that have been validated or not
	 *
	 * @var Reflection\Annotation[]|Annotation[]
	 */
	public $report = [];

	//---------------------------------------------------------------------------------------- $valid
	/**
	 * true if the last validated object was valid, else false
	 *
	 * @read_only
	 * @var boolean
	 */
	public $valid;

	//--------------------------------------------------------------------------------- $validator_on
	/**
	 * @var boolean
	 */
	public $validator_on = false;

	//------------------------------------------------------------------------ afterMainControllerRun
	public function afterMainControllerRun()
	{
		$this->validator_on = false;
	}

	//----------------------------------------------------------------------- beforeMainControllerRun
	public function beforeMainControllerRun()
	{
		$this->validator_on = true;
	}

	//----------------------------------------------------------------------------------- beforeWrite
	/**
	 * The validator hook is called before each Data_Link::write() call to validate the object
	 * before writing it.
	 *
	 * @param  $object  object
	 * @param  $options Option[]
	 * @throws View_Exception
	 */
	public function beforeWrite($object, array &$options)
	{
		if ($this->validator_on) {
			$exclude = [];
			$only    = [];
			foreach ($options as $option) {
				if ($option instanceof Exclude) {
					$exclude = array_merge($exclude, $option->properties);
				}
				elseif ($option instanceof Only) {
					$only = array_merge($only, $option->properties);
				}
				elseif ($option instanceof Skip) {
					$skip = true;
				}
			}
			if (!isset($skip) && !Result::isValid($this->validate($object, $only, $exclude))) {
				throw new View_Exception($this->notValidated($object, $only, $exclude));
			}
		}
	}

	//------------------------------------------------------------------------------- createSubObject
	/**
	 * @param $object   object
	 * @param $property Reflection_Property
	 * @param $type     Type
	 * @return object|null
	 */
	private function createSubObject($object, $property, $type)
	{
		// no need to create a sub object if property is not mandatory
		if ($property->getAnnotation(Mandatory_Annotation::ANNOTATION)->value) {
			$link_class = new Link_Class($type->getElementTypeAsString());
			$sub_object = $link_class->newInstance();
			// we attach composite object, but we do not set the sub_object in its parent property
			// we simply want to validate the new object, not save it!
			$sub_object->setComposite($object);
			return $sub_object;
		}
		return null;
	}

	//------------------------------------------------------------------------------------- getErrors
	/**
	 * @return Annotation[]
	 */
	public function getErrors()
	{
		$errors = [];
		foreach ($this->report as $annotation) {
			if ($annotation->valid === Result::ERROR) {
				$errors[] = $annotation;
			}
		}
		return $errors;
	}

	//---------------------------------------------------------------------------------- notValidated
	/**
	 * @param $object  object
	 * @param $only    string[] only property names
	 * @param $exclude string[] excluded property names
	 * @return string
	 */
	private function notValidated($object, array $only = [], array $exclude = [])
	{
		$parameters = [
			$this,
			'exclude'            => $exclude,
			'object'             => $object,
			'only'               => $only,
			Parameter::AS_WIDGET => true,
			Template::TEMPLATE   => 'not_validated'
		];
		return View::run($parameters, [], [], get_class($object), 'validate');
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Registration code for the plugin
	 *
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$register->aop->afterMethod(
			[Data_Link::class, 'beforeWrite'], [$this, 'beforeWrite']
		);
		$register->aop->afterMethod(
			[Main::class, 'runController'], [$this, 'afterMainControllerRun']
		);
		$register->aop->beforeMethod(
			[Main::class, 'runController'], [$this, 'beforeMainControllerRun']
		);
		$register->setAnnotations(Parser::T_CLASS, [
			'validate'   => Class_\Validate_Annotation::class
		]);
		$register->setAnnotations(Parser::T_PROPERTY, [
			'length'     => Property\Length_Annotation::class,
			'mandatory'  => Property\Mandatory_Annotation::class,
			'max_length' => Property\Max_Length_Annotation::class,
			'max_value'  => Property\Max_Value_Annotation::class,
			'min_length' => Property\Min_Length_Annotation::class,
			'min_value'  => Property\Min_Value_Annotation::class,
			'precision'  => Property\Precision_Annotation::class,
			'signed'     => Property\Signed_Annotation::class,
			'validate'   => Property\Validate_Annotation::class
		]);
	}

	//-------------------------------------------------------------------------------------- validate
	/**
	 * @param $object             object
	 * @param $only_properties    string[] property names if we want to check those properties only
	 * @param $exclude_properties string[] property names if we don't want to check those properties
	 * @param $root               boolean
	 * @return string|null|true @values Result::const
	 */
	public function validate($object, array $only_properties = [], array $exclude_properties = [],
		$root = true)
	{
		$class = new Link_Class($object);
		$properties = $class->getAnnotation(Link_Annotation::ANNOTATION)->value
			? $class->getLinkProperties()
			: $class->accessProperties();

		if ($root) {
			$this->report = [];
			$this->valid = Result::VALID;
		}
		$this->valid = Result::andResult($this->valid, $this->validateProperties($object, $properties,
			$only_properties, $exclude_properties));
		$this->valid = Result::andResult($this->valid, $this->validateObject($object, $class));

		return $this->valid;
	}

	//---------------------------------------------------------------------------- validateAnnotation
	/**
	 * @param $object     object
	 * @param $annotation Annotation
	 * @param $property   Reflection_Property
	 * @return string|null|true @values Result::const
	 */
	protected function validateAnnotation($object, $annotation, Reflection_Property $property = null)
	{
		$annotation->object = $object;
		if ($property) {
			/** @var $annotation Property\Annotation always when $property is set */
			$annotation->property = $property;
		}
		$annotation->valid = $annotation->validate($object);
		if ($annotation->valid === true)  $annotation->valid = Result::INFORMATION;
		if ($annotation->valid === false) $annotation->valid = Result::ERROR;
		if ($annotation->valid !== Result::NONE) {
			$this->report[] = $annotation;
		}
		return $annotation->valid;
	}

	//--------------------------------------------------------------------------- validateAnnotations
	/**
	 * Returns true if the object follows validation rules
	 *
	 * @param $object      object
	 * @param $annotations Annotation[]
	 * @param $property    Reflection_Property
	 * @return string|null|true @values Result::const
	 */
	protected function validateAnnotations(
		$object, array $annotations, Reflection_Property $property = null
	) {
		$result = true;
		foreach ($annotations as $annotation_name => $annotation) {
			if (is_array($annotation)) {
				$result = Result::andResult(
					$result, $this->validateAnnotations($object, $annotation, $property)
				);
			}
			elseif (isA($annotation, Annotation::class)) {
				$result = Result::andResult(
					$result, $this->validateAnnotation($object, $annotation, $property)
				);
			}
		}
		return $result;
	}

	/**
	 * @param $object             object
	 * @param $only_properties    string[]
	 * @param $exclude_properties string[]
	 * @param $property           Reflection\Reflection_Property
	 * @return string|null|true @values Result::const
	 */
	private function validateComponent(
		$object, array $only_properties, array $exclude_properties, $property
	) {
		$result = true;
		$type = $property->getType();
		if ($type->isClass()) {
			// save current report
			$current_report = $this->report;
			$this->report = [];

			$sub_objects = [];

			// @link Collection (or Map ?)
			if ($type->isMultiple())
			{
				// if there are existing sub objects, validate them
				if (count($object->{$property->name})) {
					array_walk($object->{$property->name}, function($sub_object) {
						$sub_objects[] = $sub_object;
					});
				}
				else {
					// if no existing sub object, create one if needed
					$sub_object = $this->createSubObject($object, $property, $type);
				}
			}
			// @link Object
			else {
				// get existing sub object, or create one if needed
				$sub_object = $property->getValue($object)
					?: $this->createSubObject($object, $property, $type);
			}
			if (isset($sub_object)) {
				$sub_objects[] = $sub_object;
			}

			// validate sub_objects
			foreach($sub_objects as $sub_object) {
				$result = Result::andResult($result, $this->validate($sub_object, $only_properties,
					$exclude_properties, false
				));
			}

			// update properties path of report annotations to be relative to parent property
			/** @noinspection PhpUnusedParameterInspection */
			array_walk($this->report,
				function ($annotation, $key, Reflection\Reflection_Property $property) {
					if ($annotation->property) {
						$parent_class_name = $property->final_class;
						$path = $property->path . DOT . $annotation->property->path;
						$annotation->property = new Reflection\Reflection_Property($parent_class_name, $path);
					}
				},
				$property
			);

			// restore saved report and merge with this
			$this->report = array_merge($current_report, $this->report);
		}
		return $result;
	}

	//-------------------------------------------------------------------------------- validateObject
	/**
	 * @param $object          object
	 * @param Reflection_Class $class
	 * @return string|null|true @values Result::const
	 */
	protected function validateObject($object, Reflection_Class $class)
	{
		return $this->validateAnnotations($object, $class->getAnnotations());
	}

	//---------------------------------------------------------------------------- validateProperties
	/**
	 * @param $object             object
	 * @param $properties         Reflection_Property[]
	 * @param $only_properties    string[]
	 * @param $exclude_properties string[]
	 * @return string|null|true @values Result::const
	 */
	protected function validateProperties(
		$object, array $properties, array $only_properties, array $exclude_properties
	) {
		$result = true;
		$exclude_properties = array_flip($exclude_properties);
		$only_properties    = array_flip($only_properties);
		foreach ($properties as $property) {
			if (
				(!$only_properties || isset($only_properties[$property->name]))
				&& !isset($exclude_properties[$property->name])
				// exclude composite to avoid infinite recursion
				&& !$property->getAnnotation('composite')->value
			) {
				$result = Result::andResult(
					$result, $this->validateAnnotations($object, $property->getAnnotations(), $property)
				);
				// fire validation on mandatory component properties
				if ($property->getAnnotation('component')->value) {
					$result = Result::andResult($result, $this->validateComponent($object, $only_properties,
						$exclude_properties, $property));
				}
			}
		}
		return $result;
	}

}
