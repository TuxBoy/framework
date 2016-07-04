<?php
namespace SAF\Framework\Dao\Mysql\Diff;

use SAF\Framework\Dao\Mysql;

/**
 * Column diff
 */
class Column
{

	//----------------------------------------------------------------------------------------- $from
	/**
	 * @var Mysql\Column
	 */
	public $from;

	//------------------------------------------------------------------------------------------- $to
	/**
	 * @var Mysql\Column
	 */
	public $to;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Column diff constructor
	 *
	 * @param $from Mysql\Column
	 * @param $to   Mysql\Column
	 */
	public function __construct(Mysql\Column $from = null, Mysql\Column $to = null)
	{
		if (isset($from)) $this->from = $from;
		if (isset($to))   $this->to   = $to;
	}

}
