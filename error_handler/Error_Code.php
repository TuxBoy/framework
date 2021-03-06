<?php
namespace ITRocks\Framework\Error_Handler;

/**
 * An error code storage and translator
 */
class Error_Code
{

	/** Unknown error code message */
	//--------------------------------------------------------------------------------------- UNKNOWN
	const UNKNOWN = 'unknown';

	//------------------------------------------------------------------------------------- $CAPTIONS
	private static $CAPTIONS = [
		E_ALL               => 'all',
		E_COMPILE_ERROR     => 'compile error',
		E_COMPILE_WARNING   => 'compile warning',
		E_CORE_ERROR        => 'core error',
		E_CORE_WARNING      => 'core warning',
		E_DEPRECATED        => 'deprecated',
		E_ERROR             => 'error',
		E_NOTICE            => 'notice',
		E_PARSE             => 'parse',
		E_RECOVERABLE_ERROR => 'recoverable error',
		E_STRICT            => 'strict',
		E_USER_DEPRECATED   => 'user deprecated',
		E_USER_ERROR        => 'user error',
		E_USER_NOTICE       => 'user notice',
		E_USER_WARNING      => 'user warning',
		E_WARNING           => 'warning',
		self::UNKNOWN       => 'unknown'
	];

	//----------------------------------------------------------------------------------------- $code
	/**
	 * @var integer
	 */
	public $code;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $code integer
	 */
	public function __construct($code)
	{
		$this->code = $code;
	}

	//--------------------------------------------------------------------------------------- caption
	/**
	 * @return string
	 */
	public function caption()
	{
		return isset(self::$CAPTIONS[$this->code]) ? self::$CAPTIONS[$this->code] : self::UNKNOWN;
	}

	//--------------------------------------------------------------------------------------- isFatal
	/**
	 * @return boolean
	 */
	public function isFatal()
	{
		return in_array(
			$this->code,
			[E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR]
		);
	}

}
