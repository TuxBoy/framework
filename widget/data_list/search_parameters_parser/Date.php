<?php
namespace ITRocks\Framework\Widget\Data_List\Search_Parameters_Parser;

use ITRocks\Framework\Dao\Func;
use ITRocks\Framework\Dao\Option;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Tools\Date_Time;
use ITRocks\Framework\Widget\Data_List\Data_List_Exception;

/**
 * Date search parameters parser
 * - According to locale date format day and month can be inverted d/m <-> m/d
 * - Hours are on 24h format
 * - We can not express numerical month only, minutes only or seconds only, it will be processed
 *   like a day only
 *
 * [d]d/[m]m/yyyy [h]h:[m]m:[s]s
 * [d]d/[m]m/yyyy [h]h:[m]m
 * [d]d/[m]m/yyyy [h]h
 * [d]d/[m]m/yyyy
 * [d]d/[m]m         (means implicit current year)
 * [m]m/yyyy         (means from 01/mm/yyyy to 31!/mm/yyyy) 3-4 chars mandatory
 * yyyy              (means from 01/01/yyyy to 31/12/yyyy) 3-4 chars mandatory
 * [d]d              (means implicit current month and year)
 * "y" [+|-] integer (means from 01/01/yyyy to 31/12/yyyy)
 * "m" [+|-] integer (means from 01/mm/currentyear to 31!/mm/currentyear)
 * "d" [+|-] integer (means implicit current month and year)
 * "h" [+|-] integer (means implicit current day) from 0 minute 0s to 59 minutes 59s
 *
 * Test expressions:
 * 05/03/2015 20:45:57
 * 05/03/2015 20:45
 * 05/03/2015 20
 * 05/03/2015
 * 05/03
 * 03/2015
 * 03/20*
 * 2015/03
 * 20??/03
 * 2005
 * 2*6
 * 05
 * d-1/m-2/y-3 h-1:m-1:s-2
 * d-1
 * m-2
 * y-3
 * h-1
 * 05/03/2001 h-1
 * ?/?/? ?:?:?
 * ?/?/? ?:?
 * ?/?/? ?
 * ?/?/?
 * ?/?
 * ?
 * 0/0/0 0:0:0
 * 00/00/0000 00:00:00
 * 0
 * 05/3/201*
 * 05/3/20*
 * 05/3/2*
 * 05/3/*
 *
 */
abstract class Date
{

	//------------------------------------------------------------------------------------------ DATE
	const DATE                  = 'date';

	//---------------------------------------------------------------------------- DATE_HOURS_MINUTES
	const DATE_HOURS_MINUTES    = 'date_hours_minutes';

	//------------------------------------------------------------------------------- DATE_HOURS_ONLY
	const DATE_HOURS_ONLY       = 'date_hours_only';

	//------------------------------------------------------------------------------------- DATE_TIME
	const DATE_TIME             = 'date_time';

	//------------------------------------------------------------------------------------- DAY_MONTH
	const DAY_MONTH             = 'day_month';

	//-------------------------------------------------------------------------------------- DAY_ONLY
	const DAY_ONLY              = 'day_only';

	//------------------------------------------------------------------------------------ HOURS_ONLY
	const HOURS_ONLY            = 'hours_only';

	//--------------------------------------------------------------------------------- HOURS_MINUTES
	const HOURS_MINUTES         = 'hours_minutes';

	//------------------------------------------------------------------------- HOURS_MINUTES_SECONDS
	const HOURS_MINUTES_SECONDS = 'hours_minutes_seconds';

	//------------------------------------------------------------------------------------ MONTH_ONLY
	const MONTH_ONLY            = 'month_only';

	//------------------------------------------------------------------------------------ MONTH_YEAR
	const MONTH_YEAR            = 'month_year';

	//------------------------------------------------------------------------------------ YEAR_MONTH
	const YEAR_MONTH            = 'year_month';

	//------------------------------------------------------------------------------------- YEAR_ONLY
	const YEAR_ONLY             = 'year_only';

	//------------------------------------------------------------------------------------ DATE_PARTS
	const DATE_PARTS = [
		Date_Time::DAY,
		Date_Time::MONTH,
		Date_Time::YEAR,
		Date_Time::HOUR,
		Date_Time::MINUTE,
		Date_Time::SECOND
	];

	//--------------------------------------------------------------------------------- KIND_OF_DATES
	/**
	 * All kind of date expression we can have. Order is important!
	 */
	const KIND_OF_DATES = [
		self::DATE_TIME,
		self::DATE_HOURS_MINUTES,
		self::DATE_HOURS_ONLY,
		self::DATE,
		self::MONTH_YEAR,
		self::YEAR_MONTH,
		self::DAY_MONTH,
		self::YEAR_ONLY,
		self::DAY_ONLY,
		self::MONTH_ONLY, // can only be a formula like "m-1"
		self::HOURS_ONLY, // can only be a formula like "h-1", implicit current day
		self::HOURS_MINUTES,
		self::HOURS_MINUTES_SECONDS
	];

	//------------------------------------------------------------------------------ $currentDateTime
	/**
	 * @var Date_Time
	 */
	protected static $currentDateTime;

	//----------------------------------------------------------------------------------- $currentDay
	/**
	 * @var string|integer
	 */
	protected static $currentDay;

	//---------------------------------------------------------------------------------- $currentHour
	/**
	 * @var string|integer
	 */
	protected static $currentHour;

	//------------------------------------------------------------------------------- $currentMinutes
	/**
	 * @var string|integer
	 */
	protected static $currentMinutes;

	//--------------------------------------------------------------------------------- $currentMonth
	/**
	 * @var string|integer
	 */
	protected static $currentMonth;

	//------------------------------------------------------------------------------- $currentSeconds
	/**
	 * @var string|integer
	 */
	protected static $currentSeconds;

	//---------------------------------------------------------------------------------- $currentYear
	/**
	 * @var string|integer
	 */
	protected static $currentYear;

	//---------------------------------------------------------------------------- applyDateFormatted
	/**
	 * transform expression of a date to suitable Func
	 *
	 * @param $expression string
	 * @param $range_side integer @values Range::MAX, Range::MIN, Range::NONE
	 * @return mixed|boolean false
	 * @throws Data_List_Exception
	 */
	protected static function applyDateFormatted($expression, $range_side)
	{
		if (preg_match('/^ \\s* [0]+ \\s* $/x', $expression)) {
			return Func::isNull();
		}

		$kind_of_date = self::getKindOfDate($expression);
		if (!$kind_of_date) {
			throw new Data_List_Exception(
				$expression, Loc::tr('invalid date expression')
			);
		}

		$date_parts = self::getParts($expression, $kind_of_date);
		/**
		 * created by extract() as references on $date_parts values
		 * @var $day    string
		 * @var $month  string
		 * @var $year   string
		 * @var $hour   string
		 * @var $minute string
		 * @var $second string
		 */
		extract($date_parts, EXTR_OVERWRITE|EXTR_REFS);

		if (self::isEmptyParts($date_parts)) {
			return Func::isNull();
		}

		foreach (self::DATE_PARTS as $date_part) {
			if (!self::computePart($date_parts[$date_part], $date_part)) {
				throw new Data_List_Exception(
					$expression, Loc::tr('invalid date expression') . '(' . Loc::tr($date_part) . ')'
				);
			};
		}

		$has_wildcard = self::onePartHasWildcard($date_parts);
		if ($has_wildcard) {
			if ($range_side != Range::NONE) {
				throw new Data_List_Exception(
					$expression, Loc::tr('You can not have wildcard on a range value of a date expression')
				);
			}
			$has_formula = self::onePartHasFormula($date_parts);
			if ($has_formula) {
				throw new Data_List_Exception(
					$expression, Loc::tr('You can not combine wildcard and formula in a date expression')
				);
			}
			self::fillEmptyPartsWithWildcard($date_parts);
			self::padParts($date_parts);
			$date = Func::like("$year-$month-$day $hour:$minute:$second");
		}
		else {
			switch ($kind_of_date) {
				case self::DATE_TIME:
					$date_begin = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
					break;
				case self::DATE_HOURS_MINUTES:
					$date_begin = date('Y-m-d H:i:s', mktime($hour, $minute, 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime($hour, $minute, 59, $month, $day, $year));
					break;
				case self::DATE_HOURS_ONLY:
					$date_begin = date('Y-m-d H:i:s', mktime($hour, 0 , 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime($hour, 59, 59, $month, $day, $year));
					break;
				case self::DATE:
					$date_begin = date('Y-m-d H:i:s', mktime(0 , 0 , 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year));
					break;
				case self::MONTH_YEAR:
				case self::YEAR_MONTH:
					$date_begin = date('Y-m-d H:i:s', mktime(0, 0, 0 ,      $month    , 1, $year));
					$date_end   = date('Y-m-d H:i:s', mktime(0, 0, -1, (int)$month + 1, 1, $year));
					break;
				case self::DAY_MONTH:
					$year = self::$currentYear;
					$date_begin = date('Y-m-d H:i:s', mktime(0 , 0 , 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year));
					break;
				case self::YEAR_ONLY:
					$date_begin = date('Y-m-d H:i:s', mktime(0 , 0 , 0 , 1 , 1 , $year));
					$date_end   = date('Y-m-d H:i:s', mktime(23, 59, 59, 12, 31, $year));
					break;
				case self::DAY_ONLY:
					$year  = self::$currentYear;
					$month = self::$currentMonth;
					$date_begin = date('Y-m-d H:i:s', mktime(0 , 0 , 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime(23, 59, 59, $month, $day, $year));
					break;
				case self::MONTH_ONLY: // can only be a formula like "m-1"
					$year = self::$currentYear;
					$date_begin = date('Y-m-d H:i:s', mktime(0, 0, 0 , $month, 1, $year));
					$date_end   = date('Y-m-d H:i:s', mktime(0, 0, -1, (int)$month + 1, 1, $year));
					break;
				case self::HOURS_ONLY: // can only be a formula like "h-1", implicit current day
					$year  = self::$currentYear;
					$month = self::$currentMonth;
					$day   = self::$currentDay;
					$date_begin = date('Y-m-d H:i:s', mktime($hour, 0 , 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime($hour, 59, 59, $month, $day, $year));
					break;
				case self::HOURS_MINUTES:
					$year  = self::$currentYear;
					$month = self::$currentMonth;
					$day   = self::$currentDay;
					$date_begin = date('Y-m-d H:i:s', mktime($hour, $minute, 0 , $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime($hour, $minute, 59, $month, $day, $year));
					break;
				case self::HOURS_MINUTES_SECONDS:
					$year  = self::$currentYear;
					$month = self::$currentMonth;
					$day   = self::$currentDay;
					$date_begin = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
					$date_end   = date('Y-m-d H:i:s', mktime($hour, $minute, $second, $month, $day, $year));
					break;
			}
			/** @noinspection PhpUndefinedVariableInspection */
			$date = self::buildDateOrPeriod($date_begin, $date_end, $range_side);
		}
		return $date;
	}

	//-------------------------------------------------------------------------------- applyDateValue
	/**
	 * @param $expression string
	 * @param $range_side integer @values Range::MAX, Range::MIN, Range::NONE
	 * @return mixed|boolean false
	 */
	public static function applyDateValue($expression, $range_side = Range::NONE)
	{
		return self::applyDateSingleWildcard($expression)
			?: self::applyDateWord($expression, $range_side)
			?: Words::applyEmptyWord($expression)
			?: self::applyDateFormatted($expression, $range_side);
		  /*
			?: self::applyDayMonthYear($expression, $range_side)
			?: self::applyMonthYear($expression, $range_side)
			?: self::applyDayMonth($expression, $range_side)
			?: self::applyYearOnly($expression, $range_side)
			?: self::applyDayOnly($expression, $range_side)
			?: self::applySingleFormula($expression, $range_side, Date_Time::YEAR)
			?: self::applySingleFormula($expression, $range_side, Date_Time::MONTH)
			?: self::applySingleFormula($expression, $range_side, Date_Time::DAY);
		  */
	}

	//--------------------------------------------------------------------------- applyDateRangeValue
	/**
	 * @param $expression string|Option
	 * @param $range_side      integer @values Range::MAX, Range::MIN, Range::NONE
	 * @return mixed
	 * @throws Data_List_Exception
	 */
	public static function applyDateRangeValue($expression, $range_side)
	{
		if (Wildcard::hasWildcard($expression)) {
			throw new Data_List_Exception(
				$expression, Loc::tr('You can not have a wildcard on a range value')
			);
		}
		return self::applyDateValue($expression, $range_side);
	}

	//----------------------------------------------------------------------- applyDateSingleWildcard
	/**
	 * If expression is a single wildcard or series of wildcard chars, convert to corresponding date
	 *
	 * @param $expression string
	 * @return boolean|mixed false
	 */
	private static function applyDateSingleWildcard($expression)
	{
		if (is_string($expression) && preg_match('/^ \\s* [*%?_]+ \\s* $/x', $expression)) {
			//return Func::like("____-__-__ __:__:__");
			// Optimization by replacing LIKE by IS NOT NULL
			return Func::notNull();
		}
		return false;
	}

	//--------------------------------------------------------------------------------- applyDateWord
	/**
	 * If expression is a date word, convert to corresponding date
	 * @param $expression string
	 * @param $range_side integer @values Range::MAX, Range::MIN, Range::NONE
	 * @return mixed|boolean false
	 */
	private static function applyDateWord($expression, $range_side)
	{
		$word = Words::getCompressedWords([$expression])[0];

		if (in_array($word, self::getDateWordsToCompare(Date_Time::YEAR))) {
			// we convert a current year word in numeric current year period
			$date_begin = date(
				'Y-m-d H:i:s', mktime(0, 0, 0, 1, 1, self::$currentYear)
			);
			$date_end = date(
				'Y-m-d H:i:s', mktime(23, 59, 59, 12, 31, self::$currentYear)
			);
		}
		elseif (in_array($word, self::getDateWordsToCompare(Date_Time::MONTH))) {
			//we convert a current year word in numeric current month / current year period
			$date_begin = date(
				'Y-m-d H:i:s', mktime(0, 0, 0, self::$currentMonth, 1, self::$currentYear)
			);
			$date_end = date(
				'Y-m-d H:i:s', mktime(0, 0, -1, self::$currentMonth + 1, 1, self::$currentYear)
			);
		}
		elseif (in_array($word, self::getDateWordsToCompare(Date_Time::DAY))) {
			//we convert a current day word in numeric current day period
			$date_begin = date(
				'Y-m-d H:i:s', mktime(0, 0, 0, self::$currentMonth, self::$currentDay, self::$currentYear)
			);
			$date_end = date(
				'Y-m-d H:i:s',
				mktime(23, 59, 59, self::$currentMonth, self::$currentDay, self::$currentYear)
			);
		}
		elseif (in_array($word, self::getDateWordsToCompare('yesterday'))) {
			//we convert a current day word in numeric current day period
			$date_begin = date(
				'Y-m-d H:i:s', mktime(0, 0, 0, self::$currentMonth, (int)self::$currentDay-1, self::$currentYear)
			);
			$date_end = date(
				'Y-m-d H:i:s',
				mktime(23, 59, 59, self::$currentMonth, (int)self::$currentDay-1, self::$currentYear)
			);
		}
		if (isset($date_begin) && isset($date_end)) {
			$date = self::buildDateOrPeriod($date_begin, $date_end, $range_side);
			return $date;
		}
		return false;
	}

	//----------------------------------------------------------------------------- buildDateOrPeriod
	/**
	 * Builds the correct Dao object for given begin and end date according to what we want
	 *
	 * @param $date_min   string
	 * @param $date_max   string
	 * @param $range_side integer @values Range::MAX, Range::MIN, Range::NONE
	 * @return Func\Range|string
	 */
	private static function buildDateOrPeriod($date_min, $date_max, $range_side)
	{
		if ($range_side == Range::MIN || ($date_min == $date_max)) {
			$date = $date_min;
		}
		elseif ($range_side == Range::MAX) {
			$date = $date_max;
		}
		else {
			$date = Range::buildRange($date_min, $date_max);
		}
		return $date;
	}

	//------------------------------------------------------------------------- checkDateWildcardExpr
	/**
	 * Check an expression (part of a datetime) contains wildcards and correct it, if necessary
	 * @param &$expression string
	 * @param $date_part   string Date_Time::DAY, Date_Time::MONTH, Date_Time::YEAR, Date_Time::HOUR,
	 *                            Date_Time::MINUTE, Date_Time::SECOND
	 * @return boolean
	 */
	public static function checkDateWildcardExpr(&$expression, $date_part)
	{
		$expression = str_replace(['*', '?'], ['%', '_'], $expression);
		$nchar = ($date_part == Date_Time::YEAR ? 4 : 2);
		if ($c = preg_match_all("/^[0-9_%]{1,$nchar}$/", $expression)) {
			self::correctDateWildcardExpr($expression, $date_part);
			return true;
		}
		return false;
	}

	//------------------------------------------------------------------------------ checkNumericExpr
	/**
	 * Check an expression is numeric
	 *
	 * @param $expression string
	 * @return boolean
	 */
	private static function checkNumericExpr(&$expression)
	{
		return is_numeric($expression) && (string)((int)$expression) == $expression;
	}

	//----------------------------------------------------------------------------------- computePart
	/**
	 * Compute a date part expression to get a string suitable to build a Date
	 *
	 * @param &$expression string numeric or with widlcard or formula d+1 | m+3 | y-2 | h+1 | i+3...
	 * @param $date_part   string Date_Time::DAY, Date_Time::MONTH, Date_Time::YEAR, Date_Time::HOUR,
	 *                            Date_Time::MINUTE, Date_Time::SECOND
	 * @return boolean
	 */
	protected static function computePart(&$expression, $date_part)
	{
		$expression = trim($expression);
		// empty expression
		if (!strlen($expression)) {
			return true;
		}
		// numeric expr
		if (self::checkNumericExpr($expression)) {
			return true;
		}
		// expression with wildcards
		if (self::checkDateWildcardExpr($expression, $date_part)) {
			return true;
		}
		// expression with formula
		if (self::computeFormula($expression, $date_part)) {
			return true;
		}
		return false;
	}

	//-------------------------------------------------------------------------------- computeFormula
	/**
	 * Compile a formula and compute value for a part of date
	 *
	 * @param &$expression string formula
	 * @param $date_part   string Date_Time::DAY, Date_Time::MONTH, Date_Time::YEAR, Date_Time::HOUR,
	 *                            Date_Time::MINUTE, Date_Time::SECOND
	 * @return boolean true if formula found
	 */
	private static function computeFormula(&$expression, $date_part)
	{
		$pp = '[' . self::getDateLetters($date_part) . ']';
		if (preg_match(
			"/^ \\s* $pp \\s* (?:(?<sign>[-+]) \\s* (?<operand>\\d+))? \\s* $/x", $expression, $matches
		)) {
			/**
			 * Notice : We take care to keep computed values as computed even if above limits
			 * (eg for a month > 12 or < 1) because we'll give result to mktime in order
			 * it may change year and/or day accordingly
			 * eg current month is 12 and formula is m+1 => mktime(0,0,0,20,13,2016) for 20/01/2017
			 */
			$f = [
				Date_Time::YEAR   => 'Y',
				Date_Time::MONTH  => 'm',
				Date_Time::DAY    => 'd',
				Date_Time::HOUR   => 'h',
				Date_Time::MINUTE => 'i',
				Date_Time::SECOND => 's'
			];
			$value = (int)(self::$currentDateTime->format($f[$date_part]));
			if (isset($matches['sign']) && isset($matches['operand'])) {
				$sign = $matches['sign'];
				$operand = (int)($matches['operand']);
				$expression = (string)($sign == '+' ? $value + $operand : $value - $operand);
			}
			else {
				$expression = $value;
			}
			return true;
		}
		return false;
	}

	//----------------------------------------------------------------------- correctDateWildcardExpr
	/**
	 * Correct a date expression containing SQL wildcard in order to build a Date string
	 *
	 * @param &$expression string
	 * @param $date_part   string Date_Time::DAY, Date_Time::MONTH, Date_Time::YEAR, Date_Time::HOUR,
	 *                            Date_Time::MINUTE, Date_Time::SECOND
	 */
	private static function correctDateWildcardExpr(&$expression, $date_part)
	{
		/**
		 * eg. for a month or day (or hour, minutes, seconds), it's simple since we have 2 chars only
		 *
		 * %% => __
		 * %  => __
		 * 1% => 1_
		 * %2 => _2
		 * _  => __
		 * So we simply have to replace % by _ and if a single _ then __
		 */
		if ($date_part != Date_Time::YEAR) {
			$expression = str_replace('%', '_', $expression);
			if ($expression == '_') {
				$expression = '__';
			}
		}
		/**
		 * eg. for a year, it's a bit more complex. All possible combinations => correction
		 *
		 * %%%% => ____
		 * %%%  => ____
		 * %%   => ____
		 * %    => ____    use pattern #1#
		 *
		 * 2%%% => 2___
		 * 2%%  => 2___
		 * 2%   => 2___    use pattern #2#
		 *
		 * 20%% => 20__
		 * 20%  => 20__    use pattern #3#
		 *
		 * %%%6 => ___6
		 * %%6  => ___6
		 * %6   => ___6    use pattern #4#
		 *
		 * %%16 => __16
		 * %16  => __16    use pattern #5#
		 *
		 * 2%%6 => 2__6
		 * 2%6  => 2__6    use pattern #6#
		 *
		 * %016 => _016    direct replace % by _
		 * 2%16 => 2_16    direct replace % by _
		 * 20%6 => 20_6    direct replace % by _
		 * 201% => 201_    direct replace % by _
		 *
		 * %0%6 => _0_6    direct replace % by _
		 * %01% => _01_    direct replace % by _
		 * 2%1% => 2_1_    direct replace % by _
		 */
		static $patterns = [
			/* #1# */ '/^[%]{1,4}$/',
			/* #2# */ '/^([0-9_])[%]{1,3}$/',
			/* #3# */ '/^([0-9_][0-9_])[%]{1,2}$/',
			/* #4# */ '/^[%]{1,3}([0-9_])$/',
			/* #5# */ '/^[%]{1,2}([0-9_][0-9_])$/',
			/* #6# */ '/^([0-9_])[%]{1,2}([0-9_])$/'
		];
		static $replacements = [
			/* #1# */ '____',
			/* #2# */ '${1}___',
			/* #3# */ '${1}__',
			/* #4# */ '___${1}',
			/* #5# */ '__${1}',
			/* #6# */ '${1}__${2}'
		];
		$expression = preg_replace($patterns, $replacements, $expression);
		$expression = str_replace('%', '_', $expression);
	}

	//-------------------------------------------------------------------------------- getDateLetters
	/**
	 * Gets the letters that can be used in formula for a part of a date
	 *
	 * @param $date_part null|string Date_Time::DAY, Date_Time::MONTH, Date_Time::YEAR,
	 *                               Date_Time::HOUR, Date_Time::MINUTE, Date_Time::SECOND
	 * @return string
	 */
	private static function getDateLetters($date_part = null)
	{
		static $letters;
		if (!isset($letters)) {
			$letters = explode('|', Loc::tr('d|m|y') . '|' . Loc::tr('h|m|s'));
			$ipUp = function($letter) { return isset($letter) ? ($letter . strtoupper($letter)) : ''; };
			$letters = [
				Date_Time::DAY     => 'dD' . $ipUp($letters[0] != 'd' ? $letters[0] : null),
				Date_Time::MONTH   => 'mM' . $ipUp($letters[1] != 'm' ? $letters[1] : null),
				Date_Time::YEAR    => 'yY' . $ipUp($letters[2] != 'y' ? $letters[2] : null),
				Date_Time::HOUR    => 'hH' . $ipUp($letters[3] != 'h' ? $letters[3] : null),
				Date_Time::MINUTE  => 'mM' . $ipUp($letters[4] != 'm' ? $letters[4] : null),
				Date_Time::SECOND  => 'sS' . $ipUp($letters[5] != 's' ? $letters[5] : null)
			];
		}
		if (!isset($date_part)) {
			return implode('', $letters);
		}
		return $letters[$date_part];
	}

	//-------------------------------------------------------------------------- getDatePatternsArray
	/**
	 * @param $sub_patterns string[]
	 * @return string[]
	 */
	private static function getDatePatternsArray($sub_patterns)
	{
		/**
		 * @var $day        string
		 * @var $month      string
		 * @var $month_only string
		 * @var $year       string
		 * @var $year_only  string
		 * @var $hours      string
		 * @var $hours_only string
		 * @var $minutes    string
		 * @var $seconds    string
		 */
		extract($sub_patterns);
		$patterns = [];
		if (Loc::date()->format == 'd/m/Y') {
			$patterns[self::DATE_TIME]     = "(?:$day\\/$month\\/$year \\s $hours\\:$minutes\\:$seconds)";
			$patterns[self::DATE_HOURS_MINUTES]  = "(?:$day\\/$month\\/$year \\s $hours\\:$minutes)";
			$patterns[self::DATE_HOURS_ONLY]     = "(?:$day\\/$month\\/$year \\s $hours)";
			$patterns[self::DATE]                = "(?:$day\\/$month\\/$year)";
			$patterns[self::DAY_MONTH]           = "(?:$day\\/$month)";
		}
		else {
			$patterns[self::DATE_TIME]     = "(?:$month\\/$day\\/$year \\s $hours\\:$minutes\\:$seconds)";
			$patterns[self::DATE_HOURS_MINUTES]  =	"(?:$month\\/$day\\/$year \\s $hours\\:$minutes)";
			$patterns[self::DATE_HOURS_ONLY]     = "(?:$month\\/$day\\/$year \\s $hours)";
			$patterns[self::DATE]                = "(?:$month\\/$day\\/$year)";
			$patterns[self::DAY_MONTH]           = "(?:$month\\/$day)";
		}
		$patterns[self::MONTH_YEAR]            = "(?:$month\\/$year)";
		$patterns[self::YEAR_MONTH]            = "(?:$year\\/$month)";
		$patterns[self::YEAR_ONLY]             = "$year_only";
		$patterns[self::DAY_ONLY]              = "$day";
		$patterns[self::MONTH_ONLY]            = "$month_only";
		$patterns[self::HOURS_ONLY]            = "$hours_only";
		$patterns[self::HOURS_MINUTES]         = "(?:$hours\\:$minutes)";
		$patterns[self::HOURS_MINUTES_SECONDS] = "(?:$hours\\:$minutes\\:$seconds)";
		return $patterns;
	}

	//-------------------------------------------------------------------------------- getDatePattern
	/**
	 * Gets the PCRE Pattern of a date that may contain formula in its part
	 *
	 * e.g 1/m-1 | 1/m+2/y-1 | d-7 | ...
	 * Note: this is not the complete pattern, you should surround by delimiters
	 * and add whatever else you want
	 *
	 * @param $kind_of_date null|string value of self::KIND_OF_DATE
	 * @return string
	 */
	public static function getDatePattern($kind_of_date = null)
	{
		static $big_pattern = null;
		static $named_patterns = null;
		if (!isset($big_pattern)) {
			$y_letters = self::getDateLetters(Date_Time::YEAR);
			$m_letters = self::getDateLetters(Date_Time::MONTH);
			$d_letters = self::getDateLetters(Date_Time::DAY);
			$h_letters = self::getDateLetters(Date_Time::HOUR);
			$i_letters = self::getDateLetters(Date_Time::MINUTE);
			$s_letters = self::getDateLetters(Date_Time::SECOND);

			// pattern for a date part : digits with optional wildcards or formula
			$day        = '(?:[' . $d_letters . '](?:[-+]\d+)?) | (?:[0-3*?%_]?[0-9*?%_])';
			$month      = '(?:[' . $m_letters . '](?:[-+]\d+)?) | (?:[0-1*?%_]?[0-9*?%_])';
			$month_only = '(?:[' . $m_letters . '](?:[-+]\d+)?)';
			// formula | 4 digits | 3 to 4 digit with wildcard if preceded by '/'
			$year       = '(?:[' . $y_letters . '](?:[-+]\d+)?) | [0-9]{4} | (?<=\\/)[0-9*?%_]{3,4} '
				// | 3 to 4 digit with wildcard if followed by '/'
				. '| [0-9*?%_]{3,4}(?=\\/) '
				// | 1 to 4 wildcards | 1 to 4 '0' only if preceded by '/'
				. '| [*?%_]{1,4} | (?<=\\/)0{1,4}';
			// formula | 3 to 4 digit with wildcard
			$year_only  = '(?:[' . $y_letters . '](?:[-+]\d+)?) | [0-9*?%_]{3,4}';
			$hours      = '(?:[' . $h_letters . '](?:[-+]\d+)?) | (?:[0-2*?%_]?[0-9*?%_])';
			$hours_only = '(?:[' . $h_letters . '](?:[-+]\d+)?)';
			$minutes    = '(?:[' . $i_letters . '](?:[-+]\d+)?) | (?:[0-5*?%_]?[0-9*?%_])';
			$seconds    = '(?:[' . $s_letters . '](?:[-+]\d+)?) | (?:[0-5*?%_]?[0-9*?%_])';

			//build the named patterns that helps to split an expression in many parts
			$named = [];
			$named['day']          = "(?P<" . Date_Time::DAY    . "> $day )";
			$named['month']        = "(?P<" . Date_Time::MONTH  . "> $month )";
			$named['month_only']   = "(?P<" . Date_Time::MONTH  . "> $month_only )";
			$named['year']         = "(?P<" . Date_Time::YEAR   . "> $year )";
			$named['year_only']    = "(?P<" . Date_Time::YEAR   . "> $year_only )";
			$named['hours']        = "(?P<" . Date_Time::HOUR   . "> $hours )";
			$named['hours_only']   = "(?P<" . Date_Time::HOUR   . "> $hours_only )";
			$named['minutes']      = "(?P<" . Date_Time::MINUTE . "> $minutes )";
			$named['seconds']      = "(?P<" . Date_Time::SECOND . "> $seconds )";
			$named_patterns = self::getDatePatternsArray($named);

			// build unnamed patterns for a big pattern (we can not have same name twice in a pattern)
			$unnamed = [];
			$unnamed['day']        = "(?: $day )";
			$unnamed['month']      = "(?: $month )";
			$unnamed['month_only'] = "(?: $month_only )";
			$unnamed['year']       = "(?: $year )";
			$unnamed['year_only']  = "(?: $year_only )";
			$unnamed['hours']      = "(?: $hours )";
			$unnamed['hours_only'] = "(?: $hours_only )";
			$unnamed['minutes']    = "(?: $minutes )";
			$unnamed['seconds']    = "(?: $seconds )";
			$unnamed_patterns = self::getDatePatternsArray($unnamed);

			//build the big pattern that check if expression is a date and can get kind of date
			$big_pattern_parts = [];
			foreach (self::KIND_OF_DATES as $kind) {
				$big_pattern_parts[$kind] = "(?P<" . $kind . "> " . $unnamed_patterns[$kind] . ")";
			}
			$big_pattern  = "(?: " . LF . TAB . SP . SP . implode(LF . TAB . '| ', $big_pattern_parts) . LF . " )";
		}

		if (isset($kind_of_date)) {
			return $named_patterns[$kind_of_date];
		}
		/** You wanna debug? copy this regexp : /^ \s* $big_pattern \s* $/gmx
		 * into https://regex101.com/ and try your dates
		 */
		return $big_pattern;
	}

	//------------------------------------------------------------------------- getDateWordsToCompare
	/**
	 * get the words to compare with a date word in search expression
	 *
	 * @param $date_part string Date_Time::DAY, Date_Time::MONTH, Date_Time::YEAR, Date_Time::HOUR
	 * @return array
	 */
	private static function getDateWordsToCompare($date_part)
	{
		static $all_words_references = [
			Date_Time::DAY   => ['current day', 'today'],
			Date_Time::MONTH => ['current month'],
			Date_Time::YEAR  => ['current year'],
			Date_Time::HOUR  => ['current hour'],
			'yesterday'      => ['yesterday']
		];
		static $all_words_localized = [];
		if (!$all_words_localized) {
			foreach($all_words_references as $dp => $words_references) {
				$all_words_localized[$dp] = [];
				foreach($words_references as $word) {
					$all_words_localized[$dp][] = Loc::tr($word);
				}
			}
		}
		$words_references = $all_words_references[$date_part];
		$words_localized  = $all_words_localized[$date_part];
		return Words::getCompressedWords(array_merge($words_references, $words_localized));
	}

	//--------------------------------------------------------------------------------- getKindOfDate
	/**
	 * Return matches of regexp cutting date expression in multiple parts
	 *
	 * @param $expression string
	 * @return null|string value of self::KIND_OF_DATE
	 */
	private static function getKindOfDate($expression)
	{
		$pattern = "/^ \\s* " . self::getDatePattern() . " \\s* $/x";
		if (preg_match($pattern, $expression, $matches)) {
			foreach(self::KIND_OF_DATES as $kind_of_date) {
				if (isset($matches[$kind_of_date]) && !empty($matches[$kind_of_date])) {
					return $kind_of_date;
				}
			}
		}
		return null;
	}

	//-------------------------------------------------------------------------------------- getParts
	/**
	 * @param $expression   string
	 * @param $kind_of_date string value of self::KIND_OF_DATE
	 * @return array
	 */
	private static function getParts($expression, $kind_of_date)
	{
		$pattern = "/^ \\s* " . self::getDatePattern($kind_of_date) . " \\s* $/x";
		if (preg_match($pattern, $expression, $matches)) {
			$parts = [];
			foreach (self::DATE_PARTS as $date_part) {
				$parts[$date_part] = (isset($matches[$date_part]) ? $matches[$date_part] : '');
			}
			return $parts;
		}
		return null;
	}

	//-------------------------------------------------------------------- fillEmptyPartsWithWildcard
	/**
	 * @param $date_parts string[]
	 */
	private static function fillEmptyPartsWithWildcard(&$date_parts)
	{
		foreach ($date_parts as $date_part => $part) {
			if (!strlen($part)) {
				$date_parts[$date_part] = $date_part == Date_Time::YEAR ? '____' : '__';
			}
		}
	}

	//------------------------------------------------------------------------------------- initDates
	/**
	 * Init dates constants
	 *
	 * @param $date Date_Time|null
	 */
	public static function initDates($date = null)
	{
		if (!isset($date)) {
			$date = Date_Time::now();
		}
		self::$currentDateTime = $date;
		self::$currentYear     = self::$currentDateTime->format('Y');
		self::$currentMonth    = self::$currentDateTime->format('m');
		self::$currentDay      = self::$currentDateTime->format('d');
		self::$currentHour     = self::$currentDateTime->format('H');
		self::$currentMinutes  = self::$currentDateTime->format('i');
		self::$currentSeconds  = self::$currentDateTime->format('s');
	}

	//-------------------------------------------------------------------------- isASingleDateFormula
	/**
	 * Check if expression if a single date containing a formula
	 *
	 * @param $expression string
	 * @return boolean
	 */
	public static function isASingleDateFormula($expression)
	{
		// we check if $expr is a single date containing formula
		// but it may be a range with 2 dates containing formula, what should return false
		// so the use of /^ ... $/
		$kind_of_date = self::getKindOfDate($expression);
		$is = isset($kind_of_date) ? true	: false;
		return $is;
	}

	//---------------------------------------------------------------------------------- isEmptyParts
	/**
	 * @param $date_parts string[]
	 * @return boolean
	 */
	private static function isEmptyParts($date_parts)
	{
		foreach ($date_parts as $date_part => $part) {
			if (strlen($part) && !preg_match('/^ \\s* [0]+ \\s* $/x', $part)) {
				return false;
			}
		}
		return true;
	}

	//----------------------------------------------------------------------------- onePartHasFormula
	/**
	 * @param $date_parts string[]
	 * @return boolean
	 */
	private static function onePartHasFormula($date_parts)
	{
		$letters = self::getDateLetters();
		foreach ($date_parts as $date_part => $part) {
			if (strpbrk($part, $letters) !== false) {
				return true;
			}
		}
		return false;
	}

	//---------------------------------------------------------------------------- onePartHasWildcard
	/**
	 * @param $date_parts string[]
	 * @return boolean
	 */
	private static function onePartHasWildcard($date_parts)
	{
		foreach ($date_parts as $date_part => $part) {
			if (Wildcard::hasWildcard($part)) {
				return true;
			}
		}
		return false;
	}

	//-------------------------------------------------------------------------------------- padParts
	/**
	 * Pad the date parts to have left leading 0
	 * Note: if $hours is given so $minutes and $seconds should be given too!
	 *
	 * @param $date_parts string[]
	 */
	private static function padParts(&$date_parts)
	{
		foreach ($date_parts as $date_part => &$part) {
			$length = ($date_part == Date_Time::YEAR) ? 4 : 2;
			$part = str_pad($part, $length, '0', STR_PAD_LEFT);
		}
	}

}
