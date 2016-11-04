<?php
namespace ITRocks\Framework\Tools\Tests;

use ITRocks\Framework\Tests\Objects\Quote_Salesman_Same_Set;
use ITRocks\Framework\Tests\Test;
use ITRocks\Framework\Tools\Names;

/**
 * Search parameters parser unit tests
 */
class Names_Tests extends Test
{

	//----------------------------------------------------------------------------------- __construct
	/**
	 * The constructor builds an environment to test parameters parser with some simulated fields
	 */
	public function __construct()
	{
	}

	//------------------------------------------------------------------------------ testParseAndExpr
	/**
	 * Test date parser for a simple AND
	 *
	 * @return boolean
	 */
	public function testClassToSet()
	{
		$check  = Names::classToSet(Quote_Salesman_Same_Set::class);
		$assume = Quote_Salesman_Same_Set::class . 's';
		return $this->assume(__FUNCTION__, $check, $assume, false);
	}

}
