<?php

use ITRocks\Framework\Builder;

//-------------------------------------------------------------------------------------- class_tree
/**
 * Gets full class names tree, recursively
 *
 * @param $object     object|string object or class name or interface name or trait name
 * @param $classes    boolean get parent classes list
 * @param $traits     boolean get parent traits list
 * @param $interfaces boolean get parent interfaces list
 * @param $self       boolean get the object / class name itself
 * @return string[] keys and values are classes / traits / interfaces names
 */
function classTree($object, $classes = true, $traits = true, $interfaces = true, $self = true)
{
	$class_name = is_object($object) ? get_class($object) : $object;
	$tree = [];
	if ($classes) {
		$parent = get_parent_class($class_name);
		if (isset($parent)) {
			$tree = array_merge($tree, [$parent => $parent]);
		}
	}
	if ($traits) {
		$parents = class_uses($class_name);
		$tree = array_merge($tree, array_combine($parents, $parents));
	}
	if ($interfaces) {
		$parents = class_implements($class_name);
		$tree = array_merge($tree, array_combine($parents, $parents));
	}
	foreach ($tree as $parent) {
		$tree = array_merge($tree, classTree($parent, $classes, $traits, $interfaces, false));
	}
	if ($self) {
		$tree[$class_name] = $class_name;
	}
	return $tree;
}

//-------------------------------------------------------------------------------------------- diff
/**
 * Returns 0 if $v1 === $v2, -1 if $v1 < $v2, 1 if $v1 > $v2 : use it for smaller uasort() callbacks
 *
 * @param $v1     mixed
 * @param $v2     mixed
 * @param $strict boolean true for strict comparison (type must be the same), else false
 * @return integer -1, 0 or 1
 */
function cmp($v1, $v2, $strict = true)
{
	if ($strict ? ($v1 === $v2) : ($v1 == $v2)) {
		return 0;
	}
	return ($v1 < $v2) ? -1 : 1;
}

//--------------------------------------------------------------------------------------------- isA
/**
 * Returns true if an object / class /interface / trait is a class / interface / trait
 *
 * All parent classes, interfaces and traits are scanned recursively
 *
 * @param $object     string|object
 * @param $class_name string|object
 * @return boolean
 */
function isA($object, $class_name)
{
	if (is_string($object)) {
		$object = Builder::className($object);
	}
	elseif (is_object($object)) {
		$object = get_class($object);
	}
	else {
		return false;
	}
	if (is_object($class_name)) {
		$class_name = get_class($class_name);
	}
	if (is_a($object, $class_name, true)) {
		return true;
	}
	if (
		   !class_exists($object)     && !interface_exists($object)     && !trait_exists($object)
		|| !class_exists($class_name) && !interface_exists($class_name) && !trait_exists($class_name)
	) {
		return false;
	}
	$classes = class_parents($object) + class_uses($object);
	while ($classes) {
		$next_classes = [];
		foreach ($classes as $class) {
			if (is_a($class, $class_name, true)) return true;
			$next_classes += class_uses($class);
		}
		$classes = $next_classes;
	}
	return false;
}

//--------------------------------------------------------------------------------- isStrictNumeric
/**
 * Returns true if $value is a strict integer.
 * Same as isStrictNumeric, but :
 * - must not have decimal char
 *
 * @param $value string
 * @return boolean
 */
function isStrictInteger($value)
{
	return isStrictNumeric($value, false);
}

//--------------------------------------------------------------------------------- isStrictNumeric
/**
 * Returns true if $value is a strict numeric.
 * Same as php's is_numeric, but :
 * - must not begin with '+', '0' or '.'
 * - exponential part is not allowed, thus 123.45e6 is not a valid numeric value
 * - if decimal not allowed, must not have '.' or ',' char
 * - if signed not allowed, must not start with '-' char
 *
 * @param $value           string
 * @param $decimal_allowed boolean
 * @param $signed_allowed  boolean
 * @return boolean
 */
function isStrictNumeric($value, $decimal_allowed = true, $signed_allowed = true)
{
	return (is_float($value) || is_integer($value))
		|| (
			is_numeric($value)
			&& (strpos('0+.', $value[0]) === false)
			&& (stripos($value, 'E')     === false)
			&& ($decimal_allowed ?: (strpos($value, '.')    === false))
			&& ($signed_allowed  ?: (strpos($value[0], '-') === false))
		);
}

//--------------------------------------------------------------------------------- isStrictNumeric
/**
 * Returns true iv $value is a strict integer.
 * Same as isStrictNumeric, but :
 * - must not have decimal char
 *
 * @param $value string
 * @return boolean
 */
function isStrictUnsignedInteger($value)
{
	return isStrictNumeric($value, false, false);
}

//------------------------------------------------------------------------------------------ maxSet
/**
 * Returns the maximal value of $arguments
 *
 * @param $arguments float|float[]|integer|integer[]
 * @return integer|null null if there is not any real value into arguments
 */
function maxSet($arguments)
{
	$maximum = null;
	foreach (func_get_args() as $argument) {
		if (is_array($argument)) {
			$argument = call_user_func_array(__FUNCTION__, $argument);
		}
		if (($argument !== false) && !is_null($argument)) {
			$maximum = isset($maximum) ? max($argument, $maximum) : $argument;
		}
	}
	return $maximum;
}

//------------------------------------------------------------------------------------------ minSet
/**
 * Returns the minimal value of $arguments
 *
 * @param $arguments float|float[]|integer|integer[]
 * @return integer|null null if there is not any real value into arguments
 */
function minSet($arguments)
{
	$minimum = null;
	foreach (func_get_args() as $argument) {
		if (is_array($argument)) {
			$argument = call_user_func_array(__FUNCTION__, $argument);
		}
		if (($argument !== false) && !is_null($argument)) {
			$minimum = isset($minimum) ? min($argument, $minimum) : $argument;
		}
	}
	return $minimum;
}

define('_ALL',       65535);
define('_CLASS',     1);
define('_INTERFACE', 2);
define('_TRAIT',     4);

//----------------------------------------------------------------------------------------- parents
/**
 * Returns all parents (classes, interfaces and traits) of the class or object
 *
 * Result order is : classes first, then interfaces and traits from the child to the parent
 *
 * @param $object string|object
 * @param $filter integer _ALL, _CLASS | _INTERFACE | _TRAIT
 * @return string[]
 */
function parents($object, $filter = _ALL)
{
	if (is_object($object)) $object = get_class($object);
	$parents = class_parents($object);
	$classes = [$object] + $parents;
	$result = ($filter & _CLASS) ? $parents : [];
	do {
		$next_classes = [];
		foreach ($classes as $class) {
			if ($filter & _INTERFACE) $next_classes += class_implements($class);
			if ($filter & _TRAIT)     $next_classes += class_uses($class);
		}
		$classes = $next_classes;
		$result += $classes;
	} while($classes);
	return $result;
}
