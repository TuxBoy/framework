<?php
namespace ITRocks\Framework\Widget\Data_List\Search_Parameters_Parser;

use ITRocks\Framework\Dao\Func;

/**
 * Wildcard search parameters parser
 *
 * @extends Search_Parameter_Parser
 */
abstract class Joker
{

	//----------------------------------------------------------------------------------- applyJokers
	/**
	 * @param $search_value   string
	 * @param $is_range_value boolean  true if we parse a range value
	 * @return string
	 */
	public static function applyJokers($search_value, $is_range_value = false)
	{
		if (is_string($search_value)) {
			//$search = str_replace(['*', '?'], ['%', '_'], $search_value);
			$search = preg_replace(['/[*%]/', '/[?_]/'], ['%', '_'], $search_value, -1, $count);
			if ($count && !$is_range_value) {
				$search = Func::like($search);
			} /*else {
				$search = Func::equal($search);
			}*/
			return $search;
		}
		return $search_value;
	}

	//-------------------------------------------------------------------------------------- hasJoker
	/**
	 * Check if expression has any wildcard
	 *
	 * @param $search_value string
	 * @return boolean
	 */
	public static function hasJoker($search_value)
	{
		return preg_match('/[*?%_]/', $search_value)
			? true
			: false;
	}

}
