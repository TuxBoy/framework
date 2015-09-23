<?php
namespace SAF\Framework\Widget\Validate;

use SAF\Framework\Controller;
use SAF\Framework\Controller\Parameter;
use SAF\Framework\Dao\Data_Link;
use SAF\Framework\Dao\Option;
use SAF\Framework\Dao\Option\Only;
use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Reflection\Annotation\Parser;
use SAF\Framework\Reflection\Annotation\Template;
use SAF\Framework\Reflection\Annotation\Template\Validator;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\View;
use SAF\Framework\View\View_Exception;
use SAF\Framework\Widget\Validate\Property;

/**
 * The object validator links validation processes to objects
 */
class Object_Validator implements Registerable
{

	//--------------------------------------------------------------------------------------- $report
	/**
	 * The validation report contains a detailed list of validate annotations and values
	 *
	 * @read_only
	 * @var Validator[]|Property\Property_Validate_Annotation[]
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

	//----------------------------------------------------------------------------------- beforeWrite
	/**
	 * The validator hook is called before each Data_Link::write() call to validate the object
	 * before writing it.
	 *
	 * @param  $object  object
	 * @param  $options Option[]
	 * @throws View_Exception
	 */
	public function beforeWrite($object, $options)
	{
		$only = [];
		foreach ($options as $option) {
			if ($option instanceof Only) {
				$only = $option->properties;
			}
		}
		if (!$this->validate($object, $only)) {
			throw new View_Exception($this->notValidated($object, $only));
		}
	}

	//------------------------------------------------------------------------------------- getErrors
	/**
	 * @return Property\Property_Validate_Annotation[]
	 */
	public function getErrors()
	{
		$errors = [];
		foreach ($this->report as $annotation) {
			if ($annotation->valid === Validate::ERROR) {
				$errors[] = $annotation;
			}
		}
		return $errors;
	}

	//---------------------------------------------------------------------------------- notValidated
	/**
	 * @param $object object
	 * @param $only   string[] only property names
	 * @return string
	 */
	private function notValidated($object, $only = [])
	{
		$parameters = [
			$this,
			'object'                     => $object,
			'only'                       => $only,
			Parameter::AS_WIDGET         => true,
			View\Html\Template::TEMPLATE => 'not_validated'
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
		$register->aop->beforeMethod([Data_Link::class, 'write'], [$this, 'beforeWrite']);
		$register->setAnnotations(Parser::T_PROPERTY, [
			'length'     => Property\Length_Annotation::class,
			'mandatory'  => Property\Mandatory_Annotation::class,
			'max_length' => Property\Max_Length_Annotation::class,
			'max_value'  => Property\Max_Value_Annotation::class,
			'min_length' => Property\Min_Length_Annotation::class,
			'min_value'  => Property\Min_Value_Annotation::class,
			'precision'  => Property\Precision_Annotation::class,
			'signed'     => Property\Signed_Annotation::class
		]);
	}

	//-------------------------------------------------------------------------------------- validate
	/**
	 * @param $object          object
	 * @param $only_properties string[] property names if we want to check those properties only
	 * @return boolean
	 */
	public function validate($object, $only_properties = [])
	{
		$this->report = [];
		$this->valid  = true;
		$only_properties = array_flip($only_properties);
		$class = new Reflection_Class($object);

		// properties value validation
		foreach ($class->accessProperties() as $property) {
			if (!$only_properties || isset($only_properties[$property->name])) {
				$property_validator = new Property_Validator($property);
				$validated_property = $property_validator->validate($object);
				if (is_null($validated_property)) {
					return $this->valid = null;
				}
				else {
					$this->report = array_merge($this->report, $property_validator->report);
					$this->valid = $this->valid && $validated_property;
				}
			}
		}

		// object validation
		foreach ($class->getAnnotations() as $annotation) {
			if ($annotation instanceof Template\Object_Validator) {
				$validated_annotation = $annotation->validate($object);
				if (is_null($validated_annotation)) {
					return $this->valid = null;
				}
				else {
					if (!$validated_annotation) {
						$this->report[] = $annotation;
					}
					$this->valid = $this->valid && $validated_annotation;
				}
			}
		}

		return $this->valid;
	}

}
