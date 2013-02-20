<?php
namespace SAF\Tests\Tests;
use ReflectionException;
use SAF\Framework\Reflection_Class;
use SAF\Framework\Reflection_Property;
use SAF\Tests\Order;

class Reflection_Test extends \SAF\Framework\Unit_Tests\Unit_Test
{

	//----------------------------------------------------------------------- testAccessProperties
	public function testAccessProperties()
	{
		// does access properties return properties list ?
		$class = Reflection_Class::getInstanceOf('SAF\Tests\Order');
		$test1 = $this->assume(
			__METHOD__ . ".1",
			$properties = $class->accessProperties(),
			array(
				"date"            => Reflection_Property::getInstanceOf('SAF\Tests\Document', "date"),
				"number"          => Reflection_Property::getInstanceOf('SAF\Tests\Document', "number"),
				"client"          => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "client"),
				"delivery_client" => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "delivery_client"),
				"lines"           => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "lines"),
				"salesmen"        => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "salesmen")
			)
		);
		if ($test1) {
			// are properties now accessible ?
			$check = array();
			$test_order = new Order(date("Y-m-d"), "CDE001");
			foreach ($properties as $property) {
				try {
					$check[$property->name] = $property->getValue($test_order);
				}
				catch (ReflectionException $e) {
					$check[$property->name] = null;
				}
			}
			$this->assume(
				__METHOD__ . ".2",
				$check,
				array("date" => date("Y-m-d"), "number" => "CDE001", "client" => null, "lines" => null)
			);
		}
		$class->accessPropertiesDone();
	}

	//------------------------------------------------------------------- testAccessPropertiesDone
	public function testAccessPropertiesDone()
	{
		$test_order = new Order(date("Y-m-d"), "CDE001");
		$class = Reflection_Class::getInstanceOf('SAF\Tests\Order');
		$properties = $class->accessProperties();
		$class->accessPropertiesDone();
		$check = array();
		foreach ($properties as $property) {
			try {
				$check[$property->name] = $property->getValue($test_order);
			}
			catch (ReflectionException $e) {
				$check[$property->name] = null;
			}
		}
		$this->assume(
			__METHOD__,
			$check,
			array(
				"date" => null, "number" => null, "client" => null, "delivery_client" => null,
				"lines" => null, "salesmen" => null
			)
		);
	}

	//-------------------------------------------------------------------------- testGetAllProperties
	public function testGetAllProperties()
	{
		$this->assume(
			__METHOD__,
			Reflection_Class::getInstanceOf('SAF\Tests\Order')->getAllProperties(),
			array(
				"date"            => Reflection_Property::getInstanceOf('SAF\Tests\Document', "date"),
				"number"          => Reflection_Property::getInstanceOf('SAF\Tests\Document', "number"),
				"client"          => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "client"),
				"delivery_client" => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "delivery_client"),
				"lines"           => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "lines"),
				"salesmen"        => Reflection_Property::getInstanceOf('SAF\Tests\Order',    "salesmen")
			)
		);
	}

}
