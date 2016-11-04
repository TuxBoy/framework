<?php
namespace ITRocks\Framework\Mapper\Abstract_Type;

use ITRocks\Framework\Dao\Mysql\Column;
use ReflectionClass;
use stdClass;

/**
 * Abstract object feature
 *
 * Implements needs to set Abstract_Class|Interface|Trait_Name|object into a property @var
 *
 * @see Column::buildAbstractProperty
 */
class Object
{

	//------------------------------------------------------------------------------------ isAbstract
	/**
	 * Returns true if the class name matches an abstract structure
	 *
	 * Abstract structures are abstract classes, interfaces, traits, object and stdClass
	 * This not "in a php way" : these are abstract for the framework as they must be resolved into
	 * a final class.
	 *
	 * @param $class_name string|object
	 * @return boolean
	 */
	public static function isAbstract($class_name)
	{
		if (in_array($class_name, ['object', stdClass::class])) {
			return true;
		}
		$class = new ReflectionClass($class_name);
		return in_array($class_name, ['object', stdClass::class])
			|| $class->isAbstract()
			|| $class->isInterface()
			|| $class->isTrait();
	}

}
