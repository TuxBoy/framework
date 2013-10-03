<?php
namespace SAF\Tests\Tests;

use SAF\Framework\Search_Object;
use SAF\Framework\Sql_Select_Builder;
use SAF\Framework\Unit_Tests\Unit_Test;
use SAF\Tests\Client;
use SAF\Tests\Item;

/**
 * Sql select builder tests
 */
class Sql_Select_Builder_Test extends Unit_Test
{

	//--------------------------------------------------------------------------- testArrayWhereQuery
	public function testArrayWhereQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines" => array(array("number" => 2)))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id WHERE t0.`number` = 1 AND t1.`number` = 2"
		);
	}

	//--------------------------------------------------------------------------- testArrayWhereQuery
	public function testArrayWhereDeepQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines" => array(array("number" => 2, "item" => array("code" => 1))))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `items` t2 ON t2.id = t1.id_item WHERE t0.`number` = 1 AND t1.`number` = 2 AND t2.`code` = 1"
		);
	}

	//--------------------------------------------------------------------- testArrayWhereQueryObject
	public function testArrayWhereDeepQueryObject()
	{
		$item = new Item();
		$item->code = 1;
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines" => array(array("number" => 2, "item" => $item)))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `items` t2 ON t2.id = t1.id_item WHERE t0.`number` = 1 AND t1.`number` = 2 AND t2.`code` = 1"
		);
	}

	//--------------------------------------------------------------------------- testArrayWhereQuery
	public function testArrayWhereDeepQueryShort()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines" => array("number" => 2, "item" => array("code" => 1)))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `items` t2 ON t2.id = t1.id_item WHERE t0.`number` = 1 AND t1.`number` = 2 AND t2.`code` = 1"
		);
	}

	//-------------------------------------------------------------------------- testArrayWhereQuery2
	public function testArrayWhereDeepQuery2()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines" => array(array("number" => 2, "item" => array("code" => 1, "cross_selling" => array(array("code" => 3))))))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `items` t2 ON t2.id = t1.id_item LEFT JOIN `items_items` t3 ON t3.id_item = t2.id LEFT JOIN `items` t4 ON t4.id = t3.id_cross_selling WHERE t0.`number` = 1 AND t1.`number` = 2 AND t2.`code` = 1 AND t4.`code` = 3"
		);
	}

	//-------------------------------------------------------------------------- testArrayWhereQuery2
	public function testArrayWhereDeepQuery2Short()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines" => array("number" => 2, "item" => array("code" => 1, "cross_selling" => array("code" => 3))))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `items` t2 ON t2.id = t1.id_item LEFT JOIN `items_items` t3 ON t3.id_item = t2.id LEFT JOIN `items` t4 ON t4.id = t3.id_cross_selling WHERE t0.`number` = 1 AND t1.`number` = 2 AND t2.`code` = 1 AND t4.`code` = 3"
		);
	}

	//----------------------------------------------------------------------- testCollectionJoinQuery
	public function testCollectionJoinQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number", "lines.number", "lines.quantity")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`, t1.`number` AS `lines.number`, t1.`quantity` AS `lines.quantity`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id"
		);
	}

	//-------------------------------------------------------------------------- testComplexJoinQuery
	public function testComplexJoinQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("number", "client.number", "client.client.number", "client.name")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`number` AS `number`, t1.`number` AS `client.number`, t2.`number` AS `client.client.number`, t1.`name` AS `client.name`"
			. " FROM `orders` t0 INNER JOIN `clients` t1 ON t1.id = t0.id_client LEFT JOIN `clients` t2 ON t2.id = t1.id_client"
		);
	}

	//------------------------------------------------------------------------ testComplexObjectQuery
	public function testComplexObjectQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Client',
			array("number", "name", "Order_Line->client.order")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`number` AS `number`, t0.`name` AS `name`, t2.`date` AS `Order_Line->client.order:date`, t2.`number` AS `Order_Line->client.order:number`, t2.`id_client` AS `Order_Line->client.order:client`, t2.`id_delivery_client` AS `Order_Line->client.order:delivery_client`, t2.id AS `Order_Line->client.order:id`"
			. " FROM `clients` t0 LEFT JOIN `orders_lines` t1 ON t1.id_client = t0.id INNER JOIN `orders` t2 ON t2.id = t1.id_order"
		);
	}

	//--------------------------------------------------------------------------------- testJoinQuery
	public function testJoinQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order_Line',
			array("order.date", "order.number", "number", "quantity")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t1.`date` AS `order.date`, t1.`number` AS `order.number`, t0.`number` AS `number`, t0.`quantity` AS `quantity`"
			. " FROM `orders_lines` t0 INNER JOIN `orders` t1 ON t1.id = t0.id_order"
		);
	}

	//-------------------------------------------------------------------------- testLinkedClassQuery
	public function testLinkedClassQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Quote_Salesman',
			array("name", "percentage"),
			array("name" => "Robert", "percentage" => 100)
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t1.`name` AS `name`, t0.`percentage` AS `percentage`"
			. " FROM `quotes_salesmen` t0 INNER JOIN `salesmen` t1 ON t1.id = t0.id_salesman"
			. " WHERE t1.`name` = \"Robert\" AND t0.`percentage` = 100"
		);
	}

	//------------------------------------------------------------- testLinkedClassQueryWithTwoLevels
	public function testLinkedClassQueryWithTwoLevels()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Quote_Salesman_Additional',
			array("name", "percentage", "additional_text"),
			array("name" => "Robert", "percentage" => 100)
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t2.`name` AS `name`, t1.`percentage` AS `percentage`, t0.`additional_text` AS `additional_text`"
			. " FROM `quotes_salesmen_additional` t0"
			. " INNER JOIN `quotes_salesmen` t1 ON t1.id = t0.id_quote_salesman"
			. " INNER JOIN `salesmen` t2 ON t2.id = t1.id_salesman"
			. " WHERE t2.`name` = \"Robert\" AND t1.`percentage` = 100"
		);
	}

	//--------------------------------------------------------------------------------- testLinkQuery
	public function testLinkQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number", "salesmen.name")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`, t2.`name` AS `salesmen.name`"
			. " FROM `orders` t0 LEFT JOIN `orders_salesmen` t1 ON t1.id_order = t0.id LEFT JOIN `salesmen` t2 ON t2.id = t1.id_salesman"
		);
	}

	//------------------------------------------------------------------------- testObjectObjectQuery
	public function testObjectQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order_Line',
			array("number", "quantity", "order")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`number` AS `number`, t0.`quantity` AS `quantity`, t1.`date` AS `order:date`, t1.`number` AS `order:number`, t1.`id_client` AS `order:client`, t1.`id_delivery_client` AS `order:delivery_client`, t1.id AS `order:id`"
			. " FROM `orders_lines` t0 INNER JOIN `orders` t1 ON t1.id = t0.id_order"
		);
	}

	//-------------------------------------------------------------------------- testReverseJoinQuery
	public function testReverseJoinQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number", "Order_Line->order.number", "Order_Line->order.quantity")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`, t1.`number` AS `Order_Line->order.number`, t1.`quantity` AS `Order_Line->order.quantity`"
			. " FROM `orders` t0 LEFT JOIN `orders_lines` t1 ON t1.id_order = t0.id"
		);
	}

	//------------------------------------------------------------------------------- testSimpleQuery
	public function testSimpleQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0"
		);
	}

	//------------------------------------------------------------------------- testWhereComplexQuery
	public function testWhereComplexQuery()
	{
		$client = Search_Object::create('SAF\Tests\Client');
		$client->number = 1;
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number", "lines"),
			array("OR" => array("lines.client.number" => $client->number, "number" => 2))
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`, t1.`id_client` AS `lines:client`, t1.`id_item` AS `lines:item`, t1.`number` AS `lines:number`, t1.`id_order` AS `lines:order`, t1.`quantity` AS `lines:quantity`, t1.id AS `lines:id`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `clients` t2 ON t2.id = t1.id_client WHERE (t2.`number` = 1 OR t0.`number` = 2)"
		);
	}

	//---------------------------------------------------------------------------- testWhereDeepQuery
	public function testWhereDeepQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1, "lines.number" => 2)
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id WHERE t0.`number` = 1 AND t1.`number` = 2"
		);
	}

	//-------------------------------------------------------------------------- testWhereObjectQuery
	public function testWhereObjectQuery()
	{
		/** @var $client Client */
		$client = Search_Object::create('SAF\Tests\Client');
		$client->number = 1;
		$client->name = "Roger%";
		$properties = array("number", "name", "client");
		$builder = new Sql_Select_Builder('SAF\Tests\Client', $properties, $client);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`number` AS `number`, t0.`name` AS `name`, t1.`number` AS `client:number`, t1.`name` AS `client:name`, t1.`id_client` AS `client:client`, t1.id AS `client:id`"
			. " FROM `clients` t0 LEFT JOIN `clients` t1 ON t1.id = t0.id_client WHERE t0.`number` = 1 AND t0.`name` LIKE \"Roger%\""
		);
	}

	//----------------------------------------------------------------------- testWhereSubObjectQuery
	public function testWhereSubObjectQuery()
	{
		$client = Search_Object::create('SAF\Tests\Client');
		$client->number = 1;
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number", "lines"),
			array("lines.client" => $client, "number" => 2)
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`, t1.`id_client` AS `lines:client`, t1.`id_item` AS `lines:item`, t1.`number` AS `lines:number`, t1.`id_order` AS `lines:order`, t1.`quantity` AS `lines:quantity`, t1.id AS `lines:id`"
			. " FROM `orders` t0 INNER JOIN `orders_lines` t1 ON t1.id_order = t0.id LEFT JOIN `clients` t2 ON t2.id = t1.id_client WHERE t2.`number` = 1 AND t0.`number` = 2"
		);
	}

	//-------------------------------------------------------------------------------- testWhereQuery
	public function testWhereQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number"),
			array("number" => 1)
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`"
			. " FROM `orders` t0 WHERE t0.`number` = 1"
		);
	}

	//--------------------------------------------------------------------- testWhereReverseJoinQuery
	public function testWhereReverseJoinQuery()
	{
		$builder = new Sql_Select_Builder(
			'SAF\Tests\Order',
			array("date", "number", "Order_Line->order.number", "Order_Line->order.quantity"),
			array("Order_Line->order.number" => "2")
		);
		$this->assume(
			__METHOD__,
			$builder->buildQuery(),
			"SELECT t0.`date` AS `date`, t0.`number` AS `number`, t1.`number` AS `Order_Line->order.number`, t1.`quantity` AS `Order_Line->order.quantity`"
			. ' FROM `orders` t0 LEFT JOIN `orders_lines` t1 ON t1.id_order = t0.id WHERE t1.`number` = "2"'
		);
	}

}
