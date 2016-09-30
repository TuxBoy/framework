<?php

namespace SAF\Framework\Builder;

use SAF\Framework\Builder;

/**
 * Provides a newInstance method
 */
trait Has_NewInstance
{

	//----------------------------------------------------------------------------------- newInstance
	/**
	 * Builds a new instance of this class
	 *
	 * @return static
	 */
	public static function newInstance()
	{
		return Builder::create(static::class);
	}

}
