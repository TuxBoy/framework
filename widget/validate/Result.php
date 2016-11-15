<?php
namespace ITRocks\Framework\Widget\Validate;

/**
 * Validate result constants
 */
abstract class Result
{

	//----------------------------------------------------------------------------------------- ERROR
	const ERROR = 'error';

	//----------------------------------------------------------------------------------- INFORMATION
	const INFORMATION = 'information';

	//----------------------------------------------------------------------------------- ARE_INVALID
	/**
	 * List of results that means valid. Order is important
	 */
	const INVALID_RESULTS = [self::WARNING, self::ERROR];

	//------------------------------------------------------------------------------------------ NONE
	const NONE = null;

	//----------------------------------------------------------------------------------------- VALID
	const VALID = true;

	//------------------------------------------------------------------------------------- ARE_VALID
	/**
	 * List of results that means valid. Order is important
	 */
	const VALID_RESULTS = [self::VALID, self::NONE, self::INFORMATION];

	//--------------------------------------------------------------------------------------- WARNING
	const WARNING = 'warning';

	//------------------------------------------------------------------------------------- andResult
	/**
	 * @param $result     string|null|true
	 * @param $and_result string|null|true
	 * @return string|null|true
	 */
	public static function andResult($result, $and_result)
	{
		$levels = array_merge(self::VALID_RESULTS, self::INVALID_RESULTS);
		$result_level     = array_search($result, $levels, true);
		$and_result_level = array_search($and_result, $levels, true);
		return ($result_level > $and_result_level) ? $result : $and_result;
	}

	//--------------------------------------------------------------------------------------- isValid
	/**
	 * @param $result           string @values self::const
	 * @param $warning_is_valid boolean
	 * @return boolean
	 */
	public static function isValid($result, $warning_is_valid = false)
	{
		return $warning_is_valid
			? in_array($result, array_merge(self::VALID_RESULTS, [self::WARNING]), true)
			: in_array($result, self::VALID_RESULTS, true);
	}

}
