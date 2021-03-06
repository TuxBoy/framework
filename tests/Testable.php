<?php
namespace ITRocks\Framework\Tests;

/**
 * Base methods to use in a unit test class
 */
abstract class Testable {

	//------------------------------------------------------------------------------------------- ALL
	const ALL = 'all';

	//---------------------------------------------------------------------------------------- ERRORS
	const ERRORS = 'errors';

	//------------------------------------------------------------------------------------------ NONE
	const NONE = 'none';

	//--------------------------------------------------------------------------------- $errors_count
	/**
	 * @var integer
	 */
	public $errors_count = 0;

	//---------------------------------------------------------------------------------- $tests_count
	/**
	 * @var integer
	 */
	public $tests_count = 0;

	//--------------------------------------------------------------------------------------- $header
	/**
	 * Header content to show if an error comes when $show_when_ok is false
	 * Reset once shown
	 *
	 * @var string
	 */
	public $header;

	//----------------------------------------------------------------------------------------- $show
	/**
	 * @values all, errors, none
	 * @var string
	 */
	public $show = self::ERRORS;

	//----------------------------------------------------------------------------------------- begin
	/**
	 * Begin of a unit test class
	 */
	public function begin()
	{
		$this->show('<h3>' . get_class($this) . '</h3>' . LF . '<ul>' . LF);
	}

	//------------------------------------------------------------------------------------------- end
	/**
	 * End of a unit test class
	 */
	public function end()
	{
		$this->show('</ul>' . LF);
	}

	//---------------------------------------------------------------------------------------- method
	/**
	 * Start test method log
	 *
	 * @param $method_name string
	 */
	public function method($method_name)
	{
		$this->show('<h4>' . $method_name . '</h4>' . LF);
	}

	//------------------------------------------------------------------------------------------ show
	/**
	 * @param $show string
	 */
	protected function show($show)
	{
		if (($this->show === self::ALL) || ($this->errors_count && ($this->show === self::ERRORS))) {
			echo $show;
		}
		elseif ($this->show === self::ERRORS) {
			$this->header .= $show;
		}
	}

	//----------------------------------------------------------------------------------------- flush
	public function flush()
	{
		echo $this->header;
		$this->header = '';
	}

}
