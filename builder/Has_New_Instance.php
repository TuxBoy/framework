<?php
namespace ITRocks\Framework\Builder;

use ITRocks\Framework\Builder;

/**
 * Provides a newInstance method as a replacement for Builder::create() calls
 */
trait Has_New_Instance
{

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * Builds a new instance of this class
	 *
	 * @param $arguments array some arguments for the constructor, into an array
	 * @return static
	 */
	public static function newInstance($arguments = [])
	{
		return Builder::create(static::class, $arguments);
	}

}
