<?php
namespace SAF\Framework;

/**
 * Getter default methods are common getters for Dao linked objects
 */
abstract class Getter
{

	//---------------------------------------------------------------------------------------- getAll
	/**
	 * Generic getter for getting all objects of a given class
	 *
	 * @param $collection
	 * @param $element_class
	 * @return object[]
	 */
	public static function getAll($collection, $element_class)
	{
		if (!isset($collection)) {
			$collection = Dao::readAll($element_class, Dao::sort());
		}
		return $collection;
	}

	//--------------------------------------------------------------------------------- getCollection
	/**
	 * Generic getter for a collection of objects
	 *
	 * @param $collection      Component[] Actual value of the property (will be returned if not null)
	 * @param $element_class   string Class for each collection's object
	 * @param $parent          object Parent object
	 * @param $parent_property string|Reflection_Property Parent property (or property name). Recommended but can be ommited if foreign class is a Component
	 * @return object[]
	 */
	public static function getCollection(
		$collection, $element_class, $parent, $parent_property = null
	) {
		if (!isset($collection)) {
			if (Dao::getObjectIdentifier($parent)) {
				$search_element = Search_Object::create($element_class);
				$is_component = class_uses_trait($search_element, 'SAF\Framework\Component');
				if (isset($parent_property)) {
					if (!$parent_property instanceof Reflection_Property) {
						$parent_property = Reflection_Property::getInstanceOf($parent, $parent_property);
					}
					$property_name = $parent_property->getAnnotation("foreign")->value;
					$dao = ($dao = $parent_property->getAnnotation("dao")->value)
						? Dao::get($dao) : Dao::current();
				}
				else {
					$dao = Dao::current();
					$property_name = null;
				}
				if ($is_component) {
					/** @var $search_element Component */
					$search_element->setComposite($parent, $property_name);
					/** @var Component[] $collection */
					$collection = $dao->search($search_element, null, Dao::sort());
				}
				elseif (!empty($property_name)) {
echo "-- IS THIS DEAD CODE Getter line " . __LINE__ . " ? --";
					$property = Reflection_Property::getInstanceOf($search_element, $property_name);
					$accessible = $property->isPublic();
					if (!$accessible) {
						$property->setAccessible(true);
					}
					$property->setValue($search_element, $parent);
					if (!$accessible) {
						$property->setAccessible(false);
					}
					/** @var Component[] $collection */
					$collection = $dao->search($search_element);
				}
				else {
					user_error(
						"getCollection() must be called for a component foreign type"
						. " or with a parent property name"
					);
				}
			}
			if (!isset($collection)) {
				$collection = array();
			}
		}
		return $collection;
	}

	//---------------------------------------------------------------------------------------- getMap
	/**
	 * Generic getter for mapped objects
	 *
	 * @param $map      Component[] actual value of the property (will be returned if not null)
	 * @param $property string|Reflection_Property the source property (or name) for map reading
	 * @param $parent   object the parent object
	 * @return object[]
	 */
	public static function getMap($map, $property, $parent)
	{
		if (!isset($map)) {
			if (Dao::getObjectIdentifier($parent)) {
				if (!($property instanceof Reflection_Property)) {
					$property = Reflection_Property::getInstanceOf($parent, $property);
				}
				$dao = ($dao = $property->getAnnotation("dao")->value) ? Dao::get($dao) : Dao::current();
				$map = $dao->search(
					array(get_class($parent) . "->" . $property->name => $parent),
					$property->getType()->getElementTypeAsString(),
					Dao::sort()
				);
			}
			else {
				$map = array();
			}
		}
		return $map;
	}

	//------------------------------------------------------------------------------------- getObject
	/**
	 * Generic getter for an object
	 *
	 * @param $object     mixed actual value of the object, or identifier to an object
	 * @param $class_name string the object class name
	 * @param $parent     object the parent object
	 * @param $property   string|Reflection_Property the parent property
	 * @return object will be $object if aleady an object, or the read object, or null if not found
	 */
	public static function getObject($object, $class_name, $parent = null, $property = null)
	{
		if (!is_object($object)) {
			if ($property instanceof Reflection_Property) {
				$property_name = $property->name;
			}
			elseif (is_string($property)) {
				$property_name = $property;
				$property = Reflection_Property::getInstanceOf($object, $property_name);
			}
			if (is_object($parent) && isset($property_name)) {
				$id_property_name = "id_" . $property_name;
				if (isset($parent->$id_property_name)) {
					$object = $parent->$id_property_name;
				}
			}
			if (isset($object)) {
				$object = (isset($property) && ($dao = $property->getAnnotation("dao")->value))
					? Dao::get($dao)->read($object, $class_name)
					: Dao::read($object, $class_name);
			}
		}
		return $object;
	}

}
