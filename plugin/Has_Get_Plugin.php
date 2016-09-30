<?php
namespace SAF\Framework\Plugin;

use SAF\Framework\Session;

/**
 * Provides a getPlugin method as a replacement for Session::current->plugins->get() calls
 */
trait Has_Get_Plugin
{

	//------------------------------------------------------------------------------------- getPlugin
	/**
	 * Gets this plugin
	 *
	 * @param $level string|null
	 * @param $register boolean
	 * @param $activate boolean
	 *
	 * @return static
	 */
	public static function getPlugin($level = null, $register = false, $activate = false)
	{
		return Session::current()->plugins->get(static::class, $level, $register, $activate);
	}

}
