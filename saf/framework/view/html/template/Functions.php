<?php
namespace SAF\Framework\View\Html\Template;

use SAF\Framework\Locale\Loc;
use SAF\Framework\Mapper\Collection;
use SAF\Framework\Reflection\Annotation\Property\User_Annotation;
use SAF\Framework\Reflection\Integrated_Properties;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\Reflection\Reflection_Method;
use SAF\Framework\Reflection\Reflection_Property;
use SAF\Framework\Reflection\Reflection_Property_Value;
use SAF\Framework\Session;
use SAF\Framework\Tools\Default_List_Data;
use SAF\Framework\Tools\Displayable;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Set;
use SAF\Framework\View\Html\Builder\Property_Select;
use SAF\Framework\View\Html\Dom\Input;
use SAF\Framework\View\Html\Template;
use SAF\Framework\Widget\Edit\Html_Builder_Property;

/**
 * Html template functions : those which are called using {@functionName} into templates
 */
abstract class Functions
{

	//-------------------------------------------------------------------------------- $inside_blocks
	/**
	 * Used by startingBlocks and stoppingBlocks calls
	 *
	 * @var string[] key equals value
	 */
	private static $inside_blocks = [];

	//-------------------------------------------------------------------------------- getApplication
	/**
	 * Returns application name
	 *
	 * @param $template Template
	 * @return string
	 */
	public static function getApplication(
		/** @noinspection PhpUnusedParameterInspection */ Template $template
	) {
		return new Displayable(
			Session::current()->getApplicationName(), Displayable::TYPE_CLASS
		);
	}

	//-------------------------------------------------------------------------------------- getClass
	/**
	 * Returns object's class name
	 *
	 * @param $template Template
	 * @return string
	 */
	public static function getClass(Template $template)
	{
		$object = reset($template->objects);
		return is_object($object)
			? (
					($object instanceof Set)
					? new Displayable(Names::classToSet($object->element_class_name), Displayable::TYPE_CLASS)
					: new Displayable(get_class($object), Displayable::TYPE_CLASS)
				)
			: new Displayable($object, Displayable::TYPE_CLASS);
	}

	//-------------------------------------------------------------------------------------- getCount
	/**
	 * Returns array count
	 *
	 * @param $template Template
	 * @return integer
	 */
	public static function getCount(Template $template)
	{
		return count(reset($template->objects));
	}

	//------------------------------------------------------------------------------------ getDisplay
	/**
	 * Return object's display
	 *
	 * @param $template Template
	 * @return string
	 */
	public static function getDisplay(Template $template)
	{
		$object = reset($template->objects);
		if ($object instanceof Reflection_Property) {
			return Names::propertyToDisplay($object->name);
		}
		elseif ($object instanceof Reflection_Class) {
			return Names::classToDisplay($object->name);
		}
		elseif ($object instanceof Reflection_Method) {
			return Names::methodToDisplay($object->name);
		}
		elseif (is_object($object)) {
			return (new Displayable(get_class($object), Displayable::TYPE_CLASS))->display();
		}
		else {
			return $object;
		}
	}

	//--------------------------------------------------------------------------------------- getEdit
	/**
	 * Returns an HTML edit widget for current property or List_Data property
	 *
	 * @param $template          Template
	 * @param $name               string
	 * @param $ignore_user        boolean ignore @user annotation, to disable invisible and read-only
	 * @param $can_always_be_null boolean ignore @null annotation and consider this can always be null
	 * @return string
	 */
	public static function getEdit(
		Template $template, $name = null, $ignore_user = false, $can_always_be_null = false
	) {
		if (isset($name)) {
			$name = str_replace(DOT, '>', $name);
		}
		$object = reset($template->objects);
		// find the first next object
		if (!($object instanceof Reflection_Property)) {
			$object = next($template->objects);
			$property_name = reset($template->var_names);
			while (($object !== false) && !is_object($object)) {
				$object        = next($template->objects);
				$property_name = next($template->var_names);
			}
		}
		if ($object instanceof Default_List_Data) {
			$class_name = $object->element_class_name;
			$property_name = prev($template->var_names);
			list($property, $property_path, $value) = self::toEditPropertyExtra(
				$class_name, $property_name
			);
			$property_edit = new Html_Builder_Property($property, $value);
			$property_edit->name = $name ?: $property_path;
			$property_edit->preprop = null;
			if ($ignore_user) {
				$property_edit->readonly = false;
			}
			if ($can_always_be_null) {
				$property_edit->null = true;
			}
			return $property_edit->build();
		}
		if ($object instanceof Reflection_Property_Value) {
			$property_edit = new Html_Builder_Property($object, $object->value());
			$property_edit->name = $name ?: $object->path;
			$property_edit->preprop = null;
			if ($ignore_user) {
				$property_edit->readonly = false;
			}
			if ($can_always_be_null) {
				$property_edit->null = true;
			}
			return $property_edit->build();
		}
		if ($object instanceof Reflection_Property) {
			$property_edit = new Html_Builder_Property($object);
			$property_edit->name = $name ?: $object->path;
			$property_edit->preprop = null;
			if ($ignore_user) {
				$property_edit->readonly = false;
			}
			return $property_edit->build();
		}
		if (is_object($object) && isset($property_name) && is_string($property_name)) {
			$property = new Reflection_Property(get_class($object), $property_name);
			if (isset($property)) {
				if ($template->preprops) {
					$preprop = isset($preprop)
						? ($preprop . '[' . reset($template->preprops) . ']')
						: reset($template->preprops);
					while ($next = next($template->preprops)) {
						/*
						if ($i = strrpos($next, DOT)) {
							$next = substr($next, $i + 1);
						}
						*/
						if ((strpos($next, BS) !== false) && class_exists($next)) {
							$next = Names::classToDisplay($next);
						}
						else {
							$next = str_replace(DOT, '>', $next);
						}
						$preprop .= '[' . $next . ']';
					}
				}
				else {
					$preprop = null;
				}
				$property_edit = new Html_Builder_Property(
					$property, $property->getValue($object), $preprop
				);
				if ($ignore_user) {
					$property_edit->readonly = false;
				}
				if ($can_always_be_null) {
					$property_edit->null = true;
				}
				return $property_edit->build();
			}
		}
		// default html input widget
		$input = new Input();
		$input->setAttribute('name', reset($template->objects));
		return $input;
	}

	//------------------------------------------------------------------------------------- getExpand
	/**
	 * Returns an expanded list of properties. Source element must be a list of Reflection_Property
	 *
	 * @param $template Template
	 * @return Reflection_Property
	 */
	public static function getExpand(Template $template)
	{
		$property = reset($template->objects);
		$expanded = Integrated_Properties::expandUsingProperty(
			$expanded, $property, $template->getParentObject($property->class)
		);
		return $expanded ? $expanded : [$property];
	}

	//------------------------------------------------------------------------------------ getFeature
	/**
	 * Returns template's feature method name
	 *
	 * @param $template Template
	 * @return Displayable
	 */
	public static function getFeature(Template $template)
	{
		return new Displayable($template->getFeature(), Displayable::TYPE_METHOD);
	}

	//---------------------------------------------------------------------------------------- getHas
	/**
	 * Returns true if the element is not empty
	 * (usefull for conditions on arrays)
	 *
	 * @param $template Template
	 * @return boolean
	 */
	public static function getHas(Template $template)
	{
		$object = reset($template->objects);
		return !empty($object);
	}

	//---------------------------------------------------------------------------------------- getKey
	/**
	 * Returns the current key of the current element of the currently read array
	 *
	 * @param Template $template
	 * @return string|integer
	 */
	public static function getKey(Template $template)
	{
		foreach ($template->objects as $key => $array) {
			if (is_array($array)) {
				return $template->var_names[$key - 1];
			}
		}
		return null;
	}

	//---------------------------------------------------------------------------------------- getLoc
	/**
	 * Returns a value with application of current locales
	 *
	 * @param $template Template
	 * @return object
	 */
	public static function getLoc(Template $template)
	{
		foreach ($template->objects as $object) {
			if (is_object($object)) {
				$property= new Reflection_Property(get_class($object), reset($template->var_names));
				return Loc::propertyToLocale($property, reset($template->objects));
				break;
			}
		}
		return reset($object);
	}

	//--------------------------------------------------------------------------------- getEscapeName
	/**
	 * Escape strings that will be used as form names. in HTML DOT will be replaced by '>' as PHP
	 * does not like variables named 'a.b.c'
	 *
	 * @param $template Template
	 * @return string
	 */
	public static function getEscapeName(Template $template)
	{
		return str_replace(DOT, '>', reset($template->objects));
	}

	//------------------------------------------------------------------------------------ getIsFirst
	/**
	 * Returns true if the current array element is the first one
	 *
	 * @param $template Template
	 * @return boolean
	 */
	public static function getIsFirst(Template $template)
	{
		$var_name = null;
		foreach ($template->objects as $array) {
			if (is_array($array)) {
				reset($array);
				return (key($array) == $var_name);
			}
			$var_name = isset($var_name) ? next($template->var_names) : reset($template->var_names);
		}
		return null;
	}

	//------------------------------------------------------------------------------------- getIsLast
	/**
	 * Returns true if the current array element is the last one
	 *
	 * @param $template Template
	 * @return boolean
	 */
	public static function getIsLast(Template $template)
	{
		$var_name = null;
		foreach ($template->objects as $array) {
			if (is_array($array)) {
				end($array);
				return (key($array) == $var_name);
			}
			$var_name = isset($var_name) ? next($template->var_names) : reset($template->var_names);
		}
		return null;
	}

	//------------------------------------------------------------------------------------- getObject
	/**
	 * Returns nearest object from templating tree
	 *
	 * After this call, current($template->var_names) will give you the var name of the object
	 *
	 * @param $template Template
	 * @return object
	 */
	public static function getObject(Template $template)
	{
		$object = null;
		reset($template->var_names);
		foreach ($template->objects as $object) {
			if (is_object($object)) {
				break;
			}
			next($template->var_names);
		}
		return $object;
	}

	//-------------------------------------------------------------------------------------- getParse
	/**
	 * Parse vars from the string value
	 *
	 * @param $template Template
	 * @return string
	 */
	public static function getParse(Template $template)
	{
		return $template->parseVars(
			str_replace(['&#123;', '&#125;'], ['{', '}'], reset($template->objects))
		);
	}

	//--------------------------------------------------------------------------------- getProperties
	/**
	 * Returns object's properties, and their display and value
	 *
	 * @param $template Template
	 * @return Reflection_Property_Value[]
	 */
	public static function getProperties(Template $template)
	{
		$object = reset($template->objects);
		$properties_filter = $template->getParameter('properties_filter');
		$class = new Reflection_Class(get_class($object));
		$result_properties = [];
		foreach ($class->accessProperties() as $property_name => $property) {
			if (
				!$property->isStatic()
				&& !$property->getListAnnotation('user')->has(User_Annotation::INVISIBLE)
			) {
				if (!isset($properties_filter) || in_array($property_name, $properties_filter)) {
					$property = new Reflection_Property_Value(
						$property->class, $property->name, $object, false, true
					);
					$property->final_class = $class->name;
					$result_properties[$property_name] = $property;
				}
			}
		}
		return $result_properties;
	}

	//------------------------------------------------------------------------ getPropertiesOutOfTabs
	/**
	 * Returns object's properties, and their display and value, but only if they are not already into a tab
	 *
	 * @param $template Template
	 * @return Reflection_Property_Value[]
	 */
	public static function getPropertiesOutOfTabs(Template $template)
	{
		$properties = [];
		foreach (self::getProperties($template) as $property_name => $property) {
			if (!$property->getAnnotation('group')->value) {
				$properties[$property_name] = $property;
			}
		}
		return $properties;
	}

	//----------------------------------------------------------------------------- getPropertySelect
	/**
	 * @param $template Template
	 * @param $name     string
	 * @return string
	 */
	public static function getPropertySelect(Template $template, $name = null)
	{
		foreach ($template->objects as $property) {
			if ($property instanceof Reflection_Property) {
				break;
			}
		}
		if (isset($property)) {
			return (new Property_Select($property, $name))->build();
		}
		return null;
	}

	//---------------------------------------------------------------------------------- getRootClass
	/**
	 * Returns root class from templating tree
	 *
	 * @param $template Template
	 * @return object
	 */
	public static function getRootClass(Template $template)
	{
		$object = null;
		foreach (array_reverse($template->objects) as $object) {
			if (is_object($object)) {
				break;
			}
		}
		return isset($object) ? get_class($object) : null;
	}

	//--------------------------------------------------------------------------------- getRootObject
	/**
	 * Returns root object from templating tree
	 *
	 * @param $template Template
	 * @return object
	 */
	public static function getRootObject(Template $template)
	{
		$object = null;
		foreach (array_reverse($template->objects) as $object) {
			if (is_object($object)) {
				break;
			}
		}
		return $object;
	}

	//--------------------------------------------------------------------------------------- getSort
	/**
	 * Returns the sorted version of the objects collection
	 *
	 * @param $template Template
	 * @return object[] the sorted objects collection
	 */
	public static function getSort(Template $template)
	{
		if (
			is_array($collection = reset($template->objects))
			&& $collection && is_object(reset($collection))
		) {
			return (new Collection($collection))->sort();
		}
		else {
			return reset($template->objects);
		}
	}

	//----------------------------------------------------------------------------- getStartingBlocks
	/**
	 * Returns the block names if current property starts one or several properties blocks
	 * If not, returns an empty string array
	 *
	 * @param $template Template
	 * @return string[]
	 */
	public static function getStartingBlocks(Template $template)
	{
		$blocks = [];
		foreach ($template->objects as $property) if ($property instanceof Reflection_Property) {
			$blocks = array_merge($blocks, self::getPropertyBlocks($property));
		}
		$starting_blocks = [];
		foreach ($blocks as $block) {
			if (!isset(self::$inside_blocks[$block])) {
				$starting_blocks[$block] = $block;
				self::$inside_blocks[$block] = $block;
			}
		}
		foreach (self::$inside_blocks as $block) {
			if (!isset($blocks[$block])) {
				unset(self::$inside_blocks[$block]);
			}
		}
		return $starting_blocks;
	}

	//----------------------------------------------------------------------------- getStoppingBlocks
	/**
	 * Returns the block names if current property stops one or several properties blocks
	 * If not, returns an empty string array
	 *
	 * @param $template Template
	 * @return string[]
	 */
	public static function getStoppingBlocks(Template $template)
	{
		if (self::$inside_blocks) {
			$array_of = null;
			$starting_objects = $template->objects;
			foreach ($template->objects as $object_key => $object) {
				if ($object instanceof Reflection_Property) {
					$array_of = $object;
				}
				elseif ($array_of instanceof Reflection_Property) {
					if (
						!is_array($object) || !is_a(reset($object), Reflection_Property_Value::class)
					) {
						$array_of = null;
					}
					else {
						$properties = $object;
						$next_property = false;
						foreach ($properties as $property) {
							if ($property->path === $array_of->path) {
								$next_property = true;
							}
							elseif ($next_property) {
								array_unshift($starting_objects, $property);
								$blocks = [];
								foreach ($starting_objects as $prop) if ($prop instanceof Reflection_Property) {
									$blocks = array_merge($blocks, self::getPropertyBlocks($prop));
								}
								break 2;
							}
						}
					}
				}
				unset($starting_objects[$object_key]);
			}
			$stopping_blocks = [];
			foreach (self::$inside_blocks as $block) {
				if (!isset($blocks[$block])) {
					$stopping_blocks[$block] = $block;
				}
			}
			return $stopping_blocks;
		}
		return [];
	}

	//---------------------------------------------------------------------------------------- getTop
	/**
	 * Returns template's top object
	 * (use it inside of loops)
	 *
	 * @param $template Template
	 * @return object
	 */
	public static function getTop(Template $template)
	{
		return $template->getObject();
	}

	//----------------------------------------------------------------------------- getPropertyBlocks
	/**
	 * @param $property Reflection_Property
	 * @return array[]
	 */
	private static function getPropertyBlocks(Reflection_Property $property)
	{
		$blocks = [];
		if ($property->getListAnnotation('integrated')->has('block')) {
			$blocks[$property->path] = $property->path;
		}
		foreach ($property->getListAnnotation('block')->values() as $block) {
			$blocks[$block] = $block;
		}
		return $blocks;
	}

	//--------------------------------------------------------------------------- toEditPropertyExtra
	/**
	 * Gets property extra data needed for edit widget
	 *
	 * @param $class_name string
	 * @param $property   Reflection_Property_Value|Reflection_Property|string
	 * @return mixed[] Reflection_Property $property, string $property path, mixed $value
	 */
	private static function toEditPropertyExtra($class_name, $property)
	{
		if ($property instanceof Reflection_Property_Value) {
			$property_path = $property->path;
			$value = $property->value();
		}
		elseif ($property instanceof Reflection_Property) {
			$property_path = $property->name;
			$value = '';
		}
		else {
			$property_path = $property;
			$value = '';
			$property = new Reflection_Property($class_name, $property);
		}
		return [$property, $property_path, $value];
	}

}