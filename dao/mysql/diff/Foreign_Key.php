<?php
namespace SAF\Framework\Dao\Mysql\Diff;

use SAF\Framework\Dao\Mysql;

/**
 * Foreign key diff
 */
class Foreign_Key
{

	//----------------------------------------------------------------------------------------- $from
	/**
	 * @var Mysql\Foreign_Key
	 */
	public $from;

	//------------------------------------------------------------------------------------------- $to
	/**
	 * @var Mysql\Foreign_Key
	 */
	public $to;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Foreign key diff constructor
	 *
	 * @param $from Mysql\Foreign_Key
	 * @param $to   Mysql\Foreign_Key
	 */
	public function __construct(Mysql\Foreign_Key $from = null, Mysql\Foreign_Key $to = null)
	{
		if (isset($from)) $this->from = $from;
		if (isset($to))   $this->to   = $to;
	}

}
