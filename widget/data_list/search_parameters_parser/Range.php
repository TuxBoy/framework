<?php
namespace ITRocks\Framework\Widget\Data_List\Search_Parameters_Parser;

use ITRocks\Framework\Dao\Func;
use ITRocks\Framework\Dao\Option;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Reflection\Reflection_Property;
use ITRocks\Framework\Reflection\Type;
use ITRocks\Framework\Tools\Date_Time;
use ITRocks\Framework\Widget\Data_List\Data_List_Exception;

/**
 * Word search parameters parser
 *
 * @extends Search_Parameter_Parser
 */
abstract class Range
{

	//------------------------------------------------------------------------------------------- MAX
	const MAX = 1;

	//------------------------------------------------------------------------------------------- MIN
	const MIN = -1;

	//------------------------------------------------------------------------------------------ NONE
	const NONE = 0;

	//------------------------------------------------------------------------------------ applyRange
	/**
	 * Apply a range expression on search string. The range is supposed to exist !
	 *
	 * @param $expression string|Option
	 * @param $property   Reflection_Property
	 * @return Func\Range
	 * @throws Data_List_Exception
	 */
	public static function applyRange($expression, Reflection_Property $property)
	{
		$range    = self::getRangeParts($expression, $property);
		$range[0] = self::applyRangeValue($range[0], $property, self::MIN);
		$range[1] = self::applyRangeValue($range[1], $property, self::MAX);
		if ($range[0] === false || $range[1] === false) {
			throw new Data_List_Exception(
				$expression, Loc::tr('Error in range expression or range must have 2 parts only')
			);
		}
		return self::buildRange($range[0], $range[1]);
	}

	//------------------------------------------------------------------------------- applyRangeValue
	/**
	 * @param $expression string|Option
	 * @param $property     Reflection_Property
	 * @param $range_side      integer  Range::MIN | Range::MAX | Range::NONE
	 * @return mixed
	 */
	protected static function applyRangeValue($expression, Reflection_Property $property,	$range_side)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// Date_Time type
			case Date_Time::class:
				$search = Date::applyDateRangeValue($expression, $range_side);
				break;
			// Float | Integer | String types
			//case in_array($type_string, [Type::FLOAT, Type::INTEGER, Type::STRING]): {
			default:
				$search = Scalar::applyScalar($expression, $property, true);
				break;
		}
		return $search;
	}

	//------------------------------------------------------------------------------------ buildRange
	/**
	 * @param $min mixed
	 * @param $max mixed
	 * @return Func\Range
	 */
	public static function buildRange($min, $max)
	{
		return new Func\Range($min, $max);
	}

	//--------------------------------------------------------------------------------- getRangeParts
	/**
	 * Apply a range expression on search string. The range is supposed to exist !
	 *
	 * @param $expression string|Option
	 * @param $property   Reflection_Property
	 * @return array
	 * @throws Data_List_Exception
	 */
	protected static function getRangeParts($expression, Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// Date_Time type
			case Date_Time::class:
				$range = [];
				if (!Date::isASingleDateExpression($expression)) {
					// Take care of char of formulas on expr like 'm-3-m', '01/m-2/2015-01/m-2/2016'...
					// pattern of a date that may contain formula
					$pattern = Date::getDatePattern(false);
					// We should analyse 1st the right pattern to solve cases like 1/5/y-1/7/y
					// We should parse like min=1/5/y and max=1/7/y
					// and not parse like min=1/5/y-1 and max=/7/y
					$pattern_right = "/[-](\\s* $pattern \\s* )$/x";
					$found = preg_match($pattern_right, $expression, $matches);
					if ($found) {
						$max = trim($matches[1]);
						$min = trim(substr($expression, 0, -(strlen($matches[0]))));
						// We check that left part is a date expression
						if (Date::isASingleDateExpression($min)) {
							$range = [$min, $max];
						}
						else {
							throw new Data_List_Exception(
								$expression, Loc::tr('Error in left part of range expression')
							);
						}
					}
					else {
						throw new Data_List_Exception(
							$expression, Loc::tr('Error in range expression or range must have 2 parts only')
						);
					}
				}
				break;
			// Float | Integer | String types
			// case in_array($type_string, [Type::FLOAT, Type::INTEGER, Type::STRING]): {
			default:
				$range = explode('-', $expression, 2);
				// Check we have only two parts in the range!
				if (implode('-', $range) !== $expression) {
					throw new Data_List_Exception($expression, Loc::tr('Range must have 2 parts only'));
				}
				break;
		}
		return $range;
	}

	//--------------------------------------------------------------------------------- supportsRange
	/**
	 * Checks if a property has right to have range in search string
	 *
	 * @param $property Reflection_Property
	 * @return boolean true if range supported and authorized
	 */
	public static function supportsRange(Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		return ($property->getAnnotation('search_range')->value !== false)
		&& in_array($type_string, [Date_Time::class, Type::FLOAT, Type::INTEGER, Type::STRING]);
	}

	//--------------------------------------------------------------------------------------- isRange
	/**
	 * Check if expression is a range expression
	 *
	 * @param $expression string
	 * @param $property   Reflection_Property
	 * @return boolean
	 */
	public static function isRange($expression, Reflection_Property $property)
	{
		$type_string = $property->getType()->asString();
		switch ($type_string) {
			// Date_Time type
			case Date_Time::class: {
				$is_date_expression = Date::isASingleDateExpression($expression);
				if (
					is_string($expression)
					// take care of formula that may contains char '-'
					&& !$is_date_expression
					&& (strpos($expression, '-') !== false)
				) {
					return true;
				}
				break;
			}
			default: {
				if (is_string($expression) && (strpos($expression, '-') !== false)) {
					return true;
				}
				break;
			}
		}
		return false;
	}

}
