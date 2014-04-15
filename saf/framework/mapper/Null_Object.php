<?php
namespace SAF\Framework\Mapper;

use SAF\Framework\Builder;
use SAF\Framework\Reflection\Reflection_Class;

/**
 * A null object is an object which all properties have a null value
 */
abstract class Null_Object
{

	//---------------------------------------------------------------------------------------- isNull
	/**
	 * Returns true if the object has no set properties (ie was created with Null_Object:create())
	 *
	 * @param $object object
	 * @return boolean
	 */
	public static function isNull($object)
	{
		$is_null = true;
		foreach ((new Reflection_Class(get_class($object)))->accessProperties() as $property) {
			if ((!$property->isStatic()) && ($property->getValue($object) !== null)) {
				$is_null = false;
				break;
			}
		}
		return $is_null;
	}

	//---------------------------------------------------------------------------------------- create
	/**
	 * Returns a new instance of an object, but sets all its properties values to null
	 *
	 * @param $class_name string
	 * @return object
	 */
	public static function create($class_name)
	{
		$object = Builder::create($class_name);
		foreach ((new Reflection_Class($class_name))->accessProperties() as $property) {
			if (!$property->isStatic()) {
				$property->setValue($object, null);
			}
		}
		return $object;
	}

}