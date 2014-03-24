<?php
namespace SAF\Framework;

/**
 * Locks access to methods
 */
abstract class MLocks
{

	//------------------------------------------------------------------------------------------ lock
	/**
	 * Locks an object's method call
	 *
	 * @param $object      object
	 * @param $method_name string
	 * @return boolean
	 */
	public static function lock($object, $method_name)
	{
		return Locks::lock('method:' . get_class($object) . '_' . $method_name);
	}

	//---------------------------------------------------------------------------------------- unlock
	/**
	 * Unlocks an object's method call
	 *
	 * @param $object object
	 * @param $method_name string
	 */
	public static function unlock($object, $method_name)
	{
		Locks::unlock('method:' . get_class($object) . '_' . $method_name);
	}

}
