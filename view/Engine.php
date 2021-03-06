<?php
namespace ITRocks\Framework\View;

/**
 * The common interface to all view engines
 */
interface Engine
{

	//------------------------------------------------------------------------------------------ link
	/**
	 * Generates a link for to an object and feature, using parameters if needed
	 *
	 * @param $object     object|string|array linked object or class name
	 *                    Some internal calls may all this with [$class_name, $id]
	 * @param $feature    string linked feature name
	 * @param $parameters string|string[]|object|object[] optional parameters list
	 * @param $arguments  string|string[] optional arguments list
	 * @return string
	 */
	public function link($object, $feature = null, $parameters = null, $arguments = null);

	//-------------------------------------------------------------------------------------- redirect
	/**
	 * Generates a redirection link for to an object and feature, using parameters if needed
	 *
	 * @param $link    string a link generated by self::link()
	 * @param $options array|string Single or multiple options eg Target::MAIN
	 * @return string
	 */
	public function redirect($link, $options);

}
