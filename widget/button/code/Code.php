<?php
namespace ITRocks\Framework\Widget\Button;

use ITRocks\Framework\Builder;
use ITRocks\Framework\Tools\Stringable;
use ITRocks\Framework\Widget\Button\Code\Command\Parser;

/**
 * Dynamic source code typed in by the user
 *
 * @business
 */
class Code implements Stringable
{

	//-------------------------------------------------------------------------------------- $feature
	/**
	 * @user invisible
	 * @var string
	 */
	public $feature;

	//--------------------------------------------------------------------------------------- $source
	/**
	 * @alias source_code
	 * @max_length 60000
	 * @multiline
	 * @var string
	 */
	public $source;

	//----------------------------------------------------------------------------------------- $when
	/**
	 * @user invisible
	 * @values after, before
	 * @var string
	 */
	public $when;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $source  string
	 * @param $when    string @values after, before
	 * @param $feature string
	 */
	public function __construct($source = null, $when = null, $feature = null)
	{
		if (isset($source)) {
			$this->source = $source;
		}
		if (isset($when)) {
			$this->when = $when;
		}
		if (isset($feature)) {
			$this->feature = $feature;
		}
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return strval($this->source);
	}

	//--------------------------------------------------------------------------------------- execute
	/**
	 * @param $object    object
	 * @param $condition boolean
	 * @return boolean true if all the commands returned a non-empty value
	 */
	public function execute($object, $condition = false)
	{
		$result = true;
		foreach (explode(LF, $this->source) as $command) {
			if ($command = Parser::parse($command, $condition)) {
				// execute() before $result, because each command must be executed
				$more = $command->execute($object);
				$result = $more && $result;
			}
		}
		return $result;
	}

	//------------------------------------------------------------------------------------ fromString
	/**
	 * @param $source string
	 * @return static
	 */
	public static function fromString($source)
	{
		/** @var $code static */
		$code = Builder::create(get_called_class(), [$source]);
		return $code;
	}

}
