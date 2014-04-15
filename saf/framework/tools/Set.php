<?php
namespace SAF\Framework\Tools;

use SAF\Framework\Reflection\Reflection_Class;

/**
 * The default Set class for set of objects
 */
class Set
{

	//------------------------------------------------------------------------------------- $elements
	/**
	 * @var object[]
	 */
	public $elements;

	//--------------------------------------------------------------------------- $element_class_name
	/**
	 * Element class name, with namespace
	 *
	 * @var string
	 */
	public $element_class_name;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Constructs a Set object for given element class name
	 *
	 * @param $element_class_name string   the class name
	 * @param $elements           object[] the set can be initialized with this set of elements
	 */
	public function __construct($element_class_name = null, $elements = [])
	{
		$this->element_class_name = empty($element_class_name)
			? Names::setToClass(get_class($this))
			: $element_class_name;
		$this->elements = $elements;
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Adds an element into the set, if not already present (same key)
	 *
	 * @param $key     string|integer|object identity of the element in the set, or element if is null
	 * @param $element object $element element to add to the set (null if no or in key)
	 */
	public function add($key, $element = null)
	{
		if (isset($element)) {
			$this->elements[$key] = $element;
		}
		else {
			$this->elements[] = $key;
		}
	}

	//------------------------------------------------------------------------- defaultSetClassNameOf
	/**
	 * @param $class_name string
	 * @return string;
	 */
	public static function defaultSetClassNameOf($class_name)
	{
		return
			(substr($class_name, -1) === 'y') ? substr($class_name, 0, -1) . 'ies' : (
			(substr($class_name, -2) === 'an') ? substr($class_name, 0, -2) . 'en' : (
			(substr($class_name, -2) === 'ss') ? $class_name . 'es' : (
				$class_name . 's'
			)));
	}

	//---------------------------------------------------------------------------------- elementClass
	/**
	 * @return Reflection_Class
	 */
	public function elementClass()
	{
		return new Reflection_Class($this->element_class_name);
	}

	//---------------------------------------------------------------------------- elementClassNameOf
	/**
	 * Gets element class name of a given set class name (namespace needed)
	 *
	 * @param $class_name string
	 * @return string
	 */
	public static function elementClassNameOf($class_name)
	{
		if (@is_a($class_name, __CLASS__, true)) {
			$class_name = (new $class_name)->element_class_name;
		}
		elseif (!@class_exists($class_name)) {
			$class_name = Names::setToClass($class_name, false);
		}
		return $class_name;
	}

	//----------------------------------------------------------------------------------------- first
	/**
	 * @return object
	 */
	public function first()
	{
		return reset($this->elements);
	}

	//------------------------------------------------------------------------------------------- get
	/**
	 * @param $key string|integer
	 * @return object
	 */
	public function get($key)
	{
		return isset($this->elements[$key]) ? $this->elements[$key] : null;
	}

	//----------------------------------------------------------------------------------- instantiate
	/**
	 * Instantiates a set class name
	 *
	 * If this class does not exist, this will return a generic Set object constructed with
	 * the matching element class name.
	 *
	 * @param $class_name string
	 * @param $elements   object[]
	 * @return Set
	 */
	public static function instantiate($class_name, $elements = [])
	{
		if (@class_exists($class_name)) {
			return new $class_name($elements);
		}
		else {
			$element_class_name = static::elementClassNameOf($class_name);
			return new Set($element_class_name, $elements);
		}
	}

	//------------------------------------------------------------------------------------------ last
	/**
	 * @return object
	 */
	public function last()
	{
		return end($this->elements);
	}

	//---------------------------------------------------------------------------------------- length
	/**
	 * @return integer
	 */
	public function length()
	{
		return count($this->elements);
	}

	//---------------------------------------------------------------------------------------- object
	/**
	 * Gets the first object, or a Reflection_Class of the object's class if no element
	 *
	 * @return object|Reflection_Class
	 */
	public function object()
	{
		return $this->elements ? reset($this->elements) : $this->elementClass();
	}

}