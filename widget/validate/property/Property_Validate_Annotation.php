<?php
namespace SAF\Framework\Widget\Validate\Property;

use SAF\Framework\Reflection;
use SAF\Framework\Reflection\Interfaces\Reflection_Property;

/**
 * Common to all property annotations : the property context
 *
 * @extends \SAF\Framework\Reflection\Annotation
 */
trait Property_Validate_Annotation
{

	//--------------------------------------------------------------------------------------- $object
	/**
	 * The last validated (or not) object
	 *
	 * @var object
	 */
	public $object;

	//------------------------------------------------------------------------------------- $property
	/**
	 * The validated property
	 *
	 * @var Reflection_Property
	 */
	public $property;

	//---------------------------------------------------------------------------------------- $valid
	/**
	 * True if last validation was positive, else false
	 *
	 * Values are Validate::ERROR, Validate::INFORMATION and Validate::WARNING constant value for read
	 * You can write boolean values true or false too
	 * null value is reserved to invalid validation (should never occur)
	 *
	 * @values error, information, warning
	 * @var string|boolean simplified boolean values can be used on Annotation::validate(), but they
	 *                     will be changed into Validate::ERROR for true and Validate::INFORMATION
	 *                     for false immediately after the internal call to Annotation::validate()
	 */
	public $valid;

	//--------------------------------------------------------------------------- mandatoryAnnotation
	/**
	 * @return Mandatory_Annotation
	 */
	protected function mandatoryAnnotation()
	{
		return $this->property->getAnnotation('mandatory');
	}

	//-------------------------------------------------------------------------------------- getValue
	/**
	 * Gets the value of the property from the last validated object
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		$property = $this->property;
		return (isset($this->object) && $property Instanceof Reflection\Reflection_Property)
			? $property->getValue($this->object)
			: null;
	}

}