<?php
namespace SAF\Framework\Widget\Validate\Property;

use SAF\Framework\Reflection;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Widget\Validate\Result;

/**
 * The min length annotation validator
 */
class Min_Length_Annotation extends Reflection\Annotation
{
	use Annotation;

	//--------------------------------------------------------------------------------- reportMessage
	/**
	 * Gets the last validate() call resulting report message
	 *
	 * @return string
	 */
	public function reportMessage()
	{
		if (strlen($this->value)) {
			switch ($this->valid) {
				case Result::INFORMATION:
					return 'length is greater than !' . $this->value . '!';
				case Result::WARNING:
				case Result::ERROR:
					return 'minimal length is !' . $this->value . '!';
			}
		}
		return '';
	}

	//-------------------------------------------------------------------------------------- validate
	/**
	 * Validates the property value within this object context
	 *
	 * @param $object object
	 * @return boolean true if validated, false if not validated, null if could not be validated
	 */
	public function validate($object)
	{
		if ($this->property instanceof Reflection_Property) {
			$value = $this->property->getValue($object);
			return $this->mandatoryAnnotation()->isEmpty($object)
				|| (is_null($value) && $this->property->getAnnotation('null')->value)
				|| (strlen($value) >= $this->value);
		}
		return null;
	}

}
