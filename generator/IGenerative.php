<?php
namespace ITRocks\Framework\Generator;

use ITRocks\Framework\Generator;

/**
 * All generators of classes should use this interface
 */
interface IGenerative
{

	//-------------------------------------------------------------------------------------- generate
	/**
	 * Generate dynamic classes with sources and send them to main generator
	 *
	 * @param $generator Generator the main generator
	 * @return boolean true if generation process did something, else false
	 */
	public function generate(Generator $generator = null);

}
