<?php
namespace ITRocks\Framework\History;

use Exception;
use ITRocks\Framework\Builder;
use ITRocks\Framework\Dao;
use ITRocks\Framework\Dao\Data_Link;
use ITRocks\Framework\Dao\Data_Link\Identifier_Map;
use ITRocks\Framework\History;
use ITRocks\Framework\Mapper\Component;
use ITRocks\Framework\Reflection\Annotation;
use ITRocks\Framework\Reflection\Annotation\Property\Link_Annotation;
use ITRocks\Framework\Reflection\Annotation\Property\Store_Annotation;
use ITRocks\Framework\Reflection\Link_Class;
use ITRocks\Framework\Reflection\Reflection_Class;
use ITRocks\Framework\Reflection\Reflection_Property;
use ITRocks\Framework\Tools\Date_Time;
use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\Tools\Stringable;

/**
 * History writer
 *
 * TODO HIGHEST This probably does not record any history if Dao Cache is on !
 */
abstract class Writer
{

	//--------------------------------------------------------------------------------- $before_write
	/**
	 * @var object
	 */
	private static $before_write;

	//-------------------------------------------------------------------------------- $history_dates
	/**
	 * Date to set in history entries for a given class and identifier
	 * @example [User::class][12345] = Date_Time()
	 * @var Date_Time[][]
	 */
	private static $history_dates;

	//------------------------------------------------------------------------------- $linked_classes
	/**
	 * List of processed classes with Link_Class as value or false if not link class
	 *
	 * @var mixed[] [class_name => Link_Class|false]
	 */
	private static $linked_classes;

	//----------------------------------------------------------------------------- $local_properties
	/**
	 * Cache of local properties for link class
	 *
	 * @var string[][] [class_name => [property_name, property_name, ...]]
	 */
	private static $local_properties;

	//------------------------------------------------------------------------------------ afterWrite
	/**
	 * @param $object object
	 * @param $link   Data_Link
	 */
	public static function afterWrite($object, Data_Link $link)
	{
		$class_name = Builder::className(get_class($object));
		if (
			($link instanceof Identifier_Map)
			&& ($identifier = $link->getObjectIdentifier($object))
			&& isset(self::$before_write[$class_name][$identifier])
		) {
			$before_write = self::$before_write[$class_name][$identifier];
			$history_entries = self::createHistory($before_write, $object);
			foreach ($history_entries as $history) {
				Dao::write($history);
			}
			unset(self::$before_write[$class_name][$identifier]);
		}
		// this commit() solves the begin() into beforeWrite()
		Dao::commit();
	}

	//---------------------------------------------------------------------------------- areDifferent
	/**
	 * @param $property  Reflection_Property
	 * @param $old_value mixed
	 * @param $new_value mixed
	 * @return bool
	 */
	private static function areDifferent(Reflection_Property $property, $old_value, $new_value)
	{
		$type = $property->getType();
		$is_datetime = $type->isDateTime();
		if ($is_datetime) {
			$are_different = self::areDifferentDateTime($old_value, $new_value);
		}
		elseif ($type->isClass()) {
			$are_different = self::areDifferentObject($old_value, $new_value);
		}
		elseif ($type->isNumeric()) {
			$are_different = $old_value != $new_value;
		}
		else {
			$are_different = strval($old_value) != strval($new_value);
		}
		return $are_different;
	}

	//-------------------------------------------------------------------------- areDifferentDateTime
	/**
	 * @param $old_value Date_Time
	 * @param $new_value Date_Time
	 * @return bool
	 */
	private static function areDifferentDateTime($old_value, $new_value)
	{
		$old_iso = (isset($old_value) ? $old_value->toISO() : '');
		$new_iso = (isset($new_value) ? $new_value->toISO() : '');
		return $old_iso != $new_iso;
	}

	//---------------------------------------------------------------------------- areDifferentObject
	/**
	 * @param $old_value object|null
	 * @param $new_value object|null
	 * @return bool
	 */
	private static function areDifferentObject($old_value, $new_value)
	{
		return (
			(is_object($old_value) && !is_object($new_value))
			|| (!is_object($old_value) && is_object($new_value))
			|| (
				(is_object($old_value) && is_object($new_value))
				&& (
					(
						($old_value instanceof Stringable)
						&& ($new_value instanceof Stringable)
						&& strval($old_value) != strval($new_value)
					)
					|| (
						(Dao::getObjectIdentifier($old_value) || Dao::getObjectIdentifier($new_value))
						&& !Dao::is($old_value, $new_value)
					)
				)
			)
		);
	}

	//----------------------------------------------------------------------------------- beforeWrite
	/**
	 * @param $object object
	 * @param $link   Data_Link
	 */
	public static function beforeWrite($object, Data_Link $link)
	{
		// this begin() will be solved into afterWrite()
		Dao::begin();
		if (($link instanceof Identifier_Map) && ($identifier = $link->getObjectIdentifier($object))) {
			$class_name = Builder::className(get_class($object));
			self::$before_write[$class_name][$identifier] = $before = $link->read(
				$identifier, $class_name
			);
			self::expand($before, new Reflection_Class($class_name));
		}
	}

	//--------------------------------------------------------------------------------- createHistory
	/**
	 * @param $before object
	 * @param $after  object
	 * @return History[]
	 */
	private static function createHistory($before, $after)
	{
		$history_class = new Reflection_Class(Manager::getHistoryClassName(get_class($after)));
		$history = [];
		$class = new Reflection_Class(get_class($before));
		self::createHistoryForClass($after, $before, $after, $history, $history_class, $class);
		return $history;
	}

	//---------------------------------------------------------------------------- createHistoryEntry
	/**
	 * Create a specific history entry for main object class_name for given property_path
	 * Note: this does not check about property_path validity
	 *
	 * @param $object        object
	 * @param $property_path string @example my_property.mycollection[i].my_sub_property
	 * @param $old_value     string
	 * @param $new_value     string
	 * @return History
	 * @throws Exception
	 */
	public static function createHistoryEntry($object, $property_path, $old_value, $new_value)
	{
		$history_class_name = Manager::getHistoryClassName(get_class($object));
		if ($history_class_name) {
			$history = Builder::create(
				$history_class_name,
				[$object, $property_path, $old_value, $new_value, self::getHistoryDate($object)]
			);
			Dao::write($history);
			return $history;
		}
		throw new Exception("history not supported for " . Names::classToDisplay(get_class($object)));
	}

	//------------------------------------------------------------------------- createHistoryForClass
	/**
	 * @param $main                 object
	 * @param $before               object|null
	 * @param $after                object|null
	 * @param $history              History[]
	 * @param $history_class        Reflection_Class
	 * @param $class                Reflection_Class
	 * @param $path_prefix          string
	 * @param $property_name_prefix string
	 */
	private static function createHistoryForClass($main, $before, $after, &$history,
		$history_class, $class, $path_prefix = '', $property_name_prefix = '')
	{
		// we only want to parse accessible properties, not private
		foreach ($class->getProperties([T_EXTENDS, T_USE, Reflection_Class::T_SORT]) as $property) {
			$property_path = $path_prefix . $property->name;
			$property_name = $property_name_prefix . $property->name;
			$type = $property->getType();
			if (self::shouldGoDeeperFor($property)) {
				$sub_class   = $property->getType()->asReflectionClass();
				$sub_before_array = $property->getValue($before);
				$sub_after_array  = $property->getValue($after);
				$sub_before_array = is_null($sub_before_array) ? []
					: ($type->isMultiple() && is_array($sub_before_array) ? $sub_before_array
						: [$sub_before_array]
					);
				$sub_after_array  = is_null($sub_after_array)  ? []
					: ($type->isMultiple() && is_array($sub_before_array) ? $sub_after_array
						: [$sub_after_array]
					);
				$sub_before = reset($sub_before_array);
				$sub_after  = reset($sub_after_array);
				$i = 0;
				while ($sub_before !== false || $sub_after !== false) {
					$i++;
					$index = $type->isMultiple() ? "[$i]" : '';
					self::createHistoryForClass($main, $sub_before, $sub_after, $history,
						$history_class, $sub_class, $property_path . DOT, $property_name . $index . DOT);
					$sub_before = next($sub_before_array);
					$sub_after  = next($sub_after_array);
				}
			}
			elseif (self::shouldBeHistorized(get_class($main), $property, $property_path)) {
				$old_value = $property->getValue($before);
				$new_value = $property->getValue($after);
				// for multiple, format as array even if null
				if ($type->isMultiple() && !$type->isMultipleString()) {
					$old_values = (!$old_value ? [] : $old_value);
					$new_values = (!$new_value ? [] : $new_value);
				}
				// for not multiple, if value is array then join values. format as array
				else {
					$old_values = [(is_array($old_value) ? join(', ', $old_value) : $old_value)];
					$new_values = [(is_array($new_value) ? join(', ', $new_value) : $new_value)];
				}
				// loop on array to create entries
				$old_value = reset($old_values);
				$new_value  = reset($new_values);
				while ($old_value !== false || $new_value !== false) {
					if (self::areDifferent($property, $old_value, $new_value)) {
						$history[] = self::createHistoryEntry($main, $property_path, $old_value, $new_value);
					}
					$old_value = next($old_values);
					$new_value  = next($new_values);
				}
			}
		}
	}

	//---------------------------------------------------------------------------------------- expand
	/**
	 * @param $object object
	 * @param $class  Reflection_Class
	 * @todo optimize expansion by only expanding properties to be historized
	 */
	private static function expand($object, $class)
	{
		// call getter for collections and maps in order to get the full value before write
		// we only want to parse accessible properties, not private
		foreach (($properties = $class->getProperties([T_EXTENDS, T_USE])) as $property) {
			if (self::shouldGoDeeperFor($property)) {
				$type = $property->getType();
				$sub_class = $type->asReflectionClass();
				$value = $property->getValue($object);

				// we want to expand old object properties and sub properties
				// if we have null value, we build a new instance, otherwise when we'll try to access
				// properties after the write (when comparing old and new) the getter will read new value
				// and will put it in this object like if it is the old value
				if (is_null($value)) {
					if ($type->isClass()) {
						if ($type->isDateTime()) {
							$value = Date_Time::min();
						}
						elseif ($type->isMultiple()) {
							$value = [];
						}
						else {
							$value = Builder::create($type->getElementTypeAsString());
							if (isA($value, Component::class)) {
								$value->setComposite($object);
							}
						}
						$property->setValue($object, $value);
					}
				}

				$values = (is_array($value)) ? $value : [$value];
				foreach($values as $value) {
					if ($value) {
						self::expand($value, $sub_class);
					}
				}
			}
		}
	}

	//-------------------------------------------------------------------------------- getHistoryDate
	/**
	 * @param $class_name_or_object string|object
	 * @param $identifier           integer|null
	 * @return Date_Time
	 */
	static public function getHistoryDate($class_name_or_object, $identifier = null)
	{
		if (is_object($class_name_or_object)) {
			$class_name = Manager::getSourceClassName(get_class($class_name_or_object));
			$identifier = Dao::getObjectIdentifier($class_name_or_object);
		} else {
			$class_name = $class_name_or_object;
		}
		if (!isset(self::$history_dates[$class_name][$identifier])) {
			self::$history_dates[$class_name][$identifier] = new Date_Time();
		}
		return self::$history_dates[$class_name][$identifier];
	}

	//--------------------------------------------------------------------------------- isLinkedClass
	/**
	 * Returns true if property is member of a link class, false otherwise
	 *
	 * @param $property Reflection_Property
	 * @return boolean
	 */
	private static function isLinkedClass($property)
	{
		/*$type = $property->getType();
		if ($type->isClass()) {
			$class_name = $type->getElementTypeAsString();*/
		$class_name = $property->final_class;
			if (!isset(self::$linked_classes[$class_name])) {
				$class = new Reflection_Class($class_name);
				if ($class->getAnnotation(Annotation\Class_\Link_Annotation::ANNOTATION)->class) {
					self::$linked_classes[$class_name] = new Link_Class($class_name);
				}
				else {
					self::$linked_classes[$class_name] = false;
				}
			}
			return self::$linked_classes[$class_name] ? true : false;
		/*}
		return false;*/
	}

	//------------------------------------------------------------------------------- isLocalProperty
	/**
	 * Returns true if property is of a link class and is a local property of this link class
	 *
	 * @param $property Reflection_Property
	 * @return boolean
	 */
	private static function isLocalProperty($property)
	{
		/*$type = $property->getType();
		if ($type->isClass()) {
			$class_name = $type->getElementTypeAsString();*/
		$class_name = $property->final_class;
			if (!isset(self::$local_properties[$class_name])) {
				if (isset(self::$linked_classes[$class_name]) && self::$linked_classes[$class_name]) {
					$link_class = self::$linked_classes[$class_name];
					$local_properties = array_diff(
						array_keys($link_class->getLocalProperties()),
						[$link_class->getCompositeProperty()->name]
					);
					self::$local_properties[$class_name] = $local_properties;
				}
				else {
					self::$local_properties[$class_name] = [];
				}
			}
			if (isset(self::$local_properties[$class_name])
				&& in_array($property->name, self::$local_properties[$class_name])
			) {
				return true;
			}
		/*}*/
		return false;
	}

	//---------------------------------------------------------------------------- shouldBeHistorized
	/**
	 * @param $class_name    string the main object class name
	 * @param $property      Reflection_Property
	 * @param $property_path string
	 * @return boolean
	 */
	private static function shouldBeHistorized($class_name, $property, $property_path)
	{
		// BAD !
		if ($property->name == 'composite_properties') {
			return false;
		}
		$should_be_historized = (
			Manager::isToBeHistorized($class_name, $property_path)
			// property that are not stored or have special storage other than string is not historized
			&& (
				!($store = $property->getAnnotation(Store_Annotation::ANNOTATION)->value)
				|| $store === Store_Annotation::STRING
			)
			// component property itself is not historized (but inner properties could be)
			&& !$property->getAnnotation('component')->value
			// composite property is not historized
			&& (
				!$property->getAnnotation('composite')->value
				|| (self::isLinkedClass($property) && self::isLocalProperty($property))
			)
			// composite and inherited properties of a linked class are not historized
			&& (!self::isLinkedClass($property) || self::isLocalProperty($property))
		);
		return $should_be_historized;
	}

	//----------------------------------------------------------------------------- shouldGoDeeperFor
	/**
	 * @param $property Reflection_Property
	 * @return boolean
	 */
	private static function shouldGoDeeperFor($property)
	{
		// bypass composite_properties
		if ($property->name =='composite_properties') {
			return false;
		}
		// bypass composite, but only if class is not a linked class
		if ($property->getAnnotation('composite')->value && !self::isLinkedClass($property) ) {
			return false;
		}
		// for linked class, bypass the composite property and properties of inherited class
		if (self::isLinkedClass($property) && !self::isLocalProperty($property)) {
			return false;
		}
		$type = $property->getType();
		$should_go_deeper =
			(
				($property->getAnnotation('component')->value && !$type->isMultipleString())
				|| ($property->getAnnotation(Link_Annotation::ANNOTATION)->value
					== Link_Annotation::COLLECTION
				)
			);
		return $should_go_deeper;
	}

}
