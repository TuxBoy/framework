<?php
namespace SAF\Framework\Dao\Mysql;

use SAF\Framework\Controller\Main;
use SAF\Framework\Plugin\Configurable;
use SAF\Framework\Plugin\Register;
use SAF\Framework\Plugin\Registerable;
use SAF\Framework\Tools\Contextual_Mysqli;

/**
 * A logger for mysql queries
 */
class Logger implements Configurable, Registerable
{

	//------------------------------------------------------------------------------------- $continue
	/**
	 * If true, log will be displayed each time a query is executed.
	 * If false, will be display at script's end.
	 *
	 * @var boolean
	 */
	public $continue = false;

	//---------------------------------------------------------------------------------- $display_log
	/**
	 * Displays queries log. If false, only errors will be displayed
	 *
	 * @var boolean
	 */
	public $display_log = true;

	//----------------------------------------------------------------------------------- $errors_log
	/**
	 * The errors log
	 *
	 * Errors are full text looking like 'errno: Error message [SQL Query]'.
	 *
	 * @var string[]
	 */
	public $errors_log = [];

	//---------------------------------------------------------------------- $main_controller_counter
	/**
	 * Counts Main_Controller->run() recursion, to avoid logging after each sub-call
	 *
	 * @var integer
	 */
	public $main_controller_counter = 0;

	//---------------------------------------------------------------------------------- $queries_log
	/**
	 * The queries log
	 *
	 * All executed queries are logged here.
	 *
	 * @var string[]
	 */
	public $queries_log = [];

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $configuration array
	 */
	public function __construct($configuration = null)
	{
		if (isset($configuration)) {
			if (isset($configuration['continue'])) {
				$this->continue = $configuration['continue'];
				if (isset($configuration['exclude'])) {
					foreach ($configuration['exclude'] as $exclude) {
						if (strpos(SL . $_SERVER['REQUEST_URI'] . SL, SL . $exclude . SL)) {
							$this->continue = false;
							break;
						}
					}
				}
			}
			if (isset($configuration['display_log'])) {
				$this->display_log = $configuration['display_log'];
			}
		}
	}

	//------------------------------------------------------------------------ afterMainControllerRun
	/**
	 * After main controller run, display query log
	 */
	public function afterMainControllerRun()
	{
		$this->main_controller_counter--;
		if ($this->display_log && !$this->main_controller_counter) {
			$this->dumpLog();
		}
	}

	//--------------------------------------------------------------------------------------- dumpLog
	/**
	 * Display query log
	 */
	public function dumpLog()
	{
		echo '<h3>Mysql log</h3>';
		echo '<div class="Mysql logger query">' . LF;
		echo '<pre>' . print_r($this->queries_log, true) . '</pre>' . LF;
		echo ' </div>' . LF;
	}

	//--------------------------------------------------------------------------------------- onQuery
	/**
	 * Called each time before a mysql_query() call is done : log the query
	 *
	 * @param $query string
	 */
	public function onQuery($query)
	{
		if ($this->continue && $this->display_log) {
			echo '<div class="Mysql logger query">' . $query . '</div>' . LF;
		}
		$this->queries_log[] = $query;
	}

	//--------------------------------------------------------------------------------------- onError
	/**
	 * Called each time after a mysql_query() call is done : log the error (if some)
	 *
	 * @param $query  string
	 * @param $object Contextual_Mysqli
	 */
	public function onError($query, Contextual_Mysqli $object)
	{
		$mysqli = $object;
		if ($mysqli->last_errno) {
			$error = $mysqli->last_errno . ': ' . $mysqli->error . '[' . $query . ']';
			echo '<div class="Mysql logger error">' . $error . '</div>' . LF;
			$this->errors_log[] = $error;
		}
	}

	//--------------------------------------------------------------------------- onMainControllerRun
	public function onMainControllerRun()
	{
		$this->main_controller_counter++;
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$aop = $register->aop;
		$aop->beforeMethod([Contextual_Mysqli::class, 'query'], [$this, 'onQuery']);
		$aop->afterMethod([Contextual_Mysqli::class, 'query'], [$this, 'onError']);
		if (!$this->continue) {
			$aop->beforeMethod(
				[Main::class, 'runController'], [$this, 'onMainControllerRun']
			);
			$aop->afterMethod(
				[Main::class, 'runController'], [$this, 'afterMainControllerRun']
			);
		}
	}

}