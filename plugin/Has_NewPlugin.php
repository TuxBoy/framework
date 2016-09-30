<?php

namespace SAF\Framework\Plugin;

use SAF\Framework\Session;

/**
 * Provides a newPlugin method
 */
trait Has_NewPlugin
{

	//------------------------------------------------------------------------------------- newPlugin
	/**
	 * Gets this plugin
	 *
	 * @param $level string|null
	 * @param $register boolean
	 * @param $activate boolean
	 *
	 * @return static
	 */
	public static function newPlugin($level = null, $register = false, $activate = false)
	{
		return Session::current()->plugins->get(static::class, $level, $register, $activate);
	}

}
