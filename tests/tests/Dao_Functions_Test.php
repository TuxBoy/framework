<?php
namespace SAF\Tests\Tests;

use SAF\Framework\Dao_Func;
use SAF\Framework\Sql_Select_Builder;
use SAF\Framework\Unit_Tests\Unit_Test;

/**
 * Dao functions unit tests
 */
class Dao_Functions_Test extends Unit_Test
{

	//------------------------------------------------------------------------------------ testLeftOf
	public function testIsGreatest()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			null,
			array("date" => Dao_Func::isGreatest(array("number")))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.*"
			. " FROM `orders` t0 INNER JOIN ("
			. "SELECT t0.`number`, MAX(t0.`date`) AS `date`"
			. " FROM `orders` t0"
			. " GROUP BY t0.`number`"
			. ") t1"
			. " ON t1.`number` = t0.`number` AND t1.`date` = t0.`date`"
		);
	}

	//-------------------------------------------------------------------------------------- testLeft
	public function testLeft()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("number" => Dao_Func::left(4))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT LEFT(t0.`number`, 4) AS `number` FROM `orders` t0"
		);
	}

	//------------------------------------------------------------------------------------ testLeftOf
	public function testLeftMatch()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			null,
			array("number" => Dao_Func::leftMatch("N01181355010"))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.* FROM `orders` t0 WHERE t0.`number` = LEFT(\"N01181355010\", LENGTH(t0.`number`))"
		);
	}

}