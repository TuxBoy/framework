<?php
namespace SAF\Framework\Reflection\Annotation\Property;

use SAF\Framework\Reflection\Annotation;
use SAF\Framework\Reflection\Annotation\Template\Property_Context_Annotation;
use SAF\Framework\Reflection\Interfaces\Reflection_Property;

/**
 * Sets the storage format of the property into the data set
 * - string to store any object as a string.
 * > Uses __toString() and fromString() if Stringable
 * > Stores serialized object if not Stringable (serialize() and unserialize())
 * - hex to use hexadecimal storage functions : same as string, but tells the Dao to store using
 * hexadecimal access.
 *
 * Default values :
 * - string if the property type is Date_Time
 * - no value on all others cases
 */
class Store_Annotation extends Annotation implements Property_Context_Annotation
{

	const ANNOTATION = 'store';
	const FALSE      = 'false';
	const GZ         = 'gz';
	const HEX        = 'hex';
	const STRING     = 'string';

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value    string @values gz, hex, string
	 * @param $property Reflection_Property
	 */
	public function __construct($value, Reflection_Property $property)
	{
		if (empty($value) && $property->getType()->isDateTime()) {
			$value = self::STRING;
		}
		parent::__construct($value);
	}

}