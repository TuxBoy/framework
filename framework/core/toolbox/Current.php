<?php
namespace SAF\Framework;

use SAF\Plugins\Plugin;

/**
 * All classes that have a global current value should use this trait to manage the current() method
 *
 * The current() method should be overridden to improve IDE's auto-completion capability, like this
 * @example
 * class A_Class
 * {
 *   use Current { current as private pCurrent; }
 *   // doc-comment here with param $set_current A_Class and return A_Class annotations
 *   public static function current($set_current = null)
 *   {
 *     return self::pCurrent($set_current);
 *   }
 * }
 * @example
 * Then overriden classes should override current too :
 * class Another_Class extends A_Class
 * {
 *   // doc-comment here with param $set_current Another_Class and return Another_Class annotations
 *   public static function current($set_current = null)
 *   {
 *     return parent::current($set_current);
 *   }
 * }
 * @See User::current() for an example of use
 */
trait Current
{

	//-------------------------------------------------------------------------------------- $current
	/**
	 * @var object
	 */
	protected static $current = null;

	//--------------------------------------------------------------------------------------- current
	/**
	 * Gets/sets current environment's object
	 *
	 * @param $set_current mixed
	 * @return Current
	 */
	public static function current($set_current = null)
	{
		if ($set_current) {
			static::$current = $set_current;
			if (!is_a(get_called_class(), Plugin::class, true)) {
				Session::current()->set(
					$set_current, Builder::current()->sourceClassName(get_called_class())
				);
			}
		}
		elseif (!(isset(static::$current) || is_a(get_called_class(), Plugin::class, true))) {
			static::$current = Session::current()->get(
				Builder::current()->sourceClassName(get_called_class())
			);
		}
		return static::$current;
	}

}
