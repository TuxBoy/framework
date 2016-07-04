<?php
namespace SAF\Framework\Dao\Mysql\Diff;

use SAF\Framework\Dao\Mysql;

/**
 * Table diff
 */
class Table
{

	//-------------------------------------------------------------------------------------- $columns
	/**
	 * @getter getColumns
	 * @var Column[]
	 */
	public $columns;

	//----------------------------------------------------------------------------------------- $from
	/**
	 * @setter reset
	 * @var Mysql\Table
	 */
	public $from;

	//--------------------------------------------------------------------------------- $foreign_keys
	/**
	 * @getter getForeignKeys
	 * @var Foreign_Key[]
	 */
	public $foreign_keys;

	//------------------------------------------------------------------------------------------- $to
	/**
	 * @setter reset
	 * @var Mysql\Table
	 */
	public $to;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Table diff constructor
	 *
	 * @param $from Mysql\Table
	 * @param $to   Mysql\Table
	 */
	public function __construct(Mysql\Table $from = null, Mysql\Table $to = null)
	{
		if (isset($from)) $this->from = $from;
		if (isset($to))   $this->to   = $to;
	}

	//------------------------------------------------------------------------------------ getColumns
	/** @noinspection PhpUnusedPrivateMethodInspection @getter */
	/**
	 * $this->columns getter
	 * Compares columns of the $this->from and the $this->to tables and returns a diffs array
	 *
	 * @return Column[]
	 */
	private function getColumns()
	{
		if (!isset($this->columns) && $this->from && $this->to) {
			$this->columns = [];
			$from_columns = $this->from->getColumns();
			foreach ($this->to->getColumns() as $column_name => $to_column) {
				// add
				if (!isset($from_columns[$column_name])) {
					$this->columns[$column_name] = new Column(null, $to_column);
				}
				// alter
				elseif (!$to_column->equiv($from_columns[$column_name])) {
					$this->columns[$column_name] = new Column($from_columns[$column_name], $to_column);
				}
			}
			foreach ($from_columns as $column_name => $from_column) {
				// delete
				if (!isset($to_columns[$column_name])) {
					$this->columns[$column_name] = new Column($from_column, null);
				}
			}
		}
		return $this->columns;
	}

	//-------------------------------------------------------------------------------- getForeignKeys
	/** @noinspection PhpUnusedPrivateMethodInspection @getter */
	/**
	 * @return Foreign_Key[]
	 */
	private function getForeignKeys()
	{
		if (!isset($this->foreign_keys) && $this->from && $this->to) {
			$this->foreign_keys = [];
		}
		return $this->foreign_keys;
	}

	//----------------------------------------------------------------------------------------- reset
	/** @noinspection PhpUnusedPrivateMethodInspection @setter */
	/**
	 * Reset comparisons (called when $from or $to change)
	 */
	private function reset()
	{
		$this->columns      = null;
		$this->foreign_keys = null;
	}

}
