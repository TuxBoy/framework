<?php
namespace SAF\Framework;
use AopJoinpoint;

abstract class Wiki implements Plugin
{

	//------------------------------------------------------------------------------ $dont_parse_wiki
	/**
	 * When > 0, wiki will not be parsed (inside html form components)
	 *
	 * @var integer
	 */
	private static $dont_parse_wiki = 0;

	//----------------------------------------------------------------------------------- noParseZone
	/**
	 * @param $joinpoint AopJoinpoint
	 */
	public static function noParseZone(AopJoinpoint $joinpoint)
	{
		$varname = $joinpoint->getArguments()[1];
		$is_include = substr($varname, 0, 1) == "/";
		if (!$is_include) {
			self::$dont_parse_wiki ++;
		}
		$joinpoint->process();
		if (!$is_include) {
			self::$dont_parse_wiki --;
		}
	}

	//-------------------------------------------------------------------------------------- register
	public static function register()
	{
		Aop::add("around",
			'SAF\Framework\Html_Edit_Template->parseValue()',
			array(__CLASS__, "noParseZone")
		);
		Aop::add("after",
			'SAF\Framework\Reflection_Property_View->formatString()',
			array(__CLASS__, "stringWiki")
		);
	}

	//--------------------------------------------------------------------------- stringMultilineWiki
	/**
	 * Add wiki to strings
	 *
	 * @param $joinpoint AopJoinpoint
	 */
	public static function stringWiki(AopJoinpoint $joinpoint)
	{
		if (!static::$dont_parse_wiki) {
			/** @var $property Reflection_Property */
			$property = $joinpoint->getObject()->property;
			if ($property->getAnnotation("multiline")->value) {
				$joinpoint->setReturnedValue(self::textile($joinpoint->getReturnedValue()));
			}
		}
	}

	//--------------------------------------------------------------------------------------- textile
	public static function textile($string)
	{
		return (new Textile())->TextileThis($string);
	}

}
