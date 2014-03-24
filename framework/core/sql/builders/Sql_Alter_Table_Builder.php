<?php
namespace SAF\Framework;

/**
 * SQL alter table queries builder
 */
class Sql_Alter_Table_Builder
{

	//---------------------------------------------------------------------------------- $add_columns
	/**
	 * Columns to add
	 *
	 * @var Dao_Column[]
	 */
	private $add_columns = [];

	//-------------------------------------------------------------------------------- $alter_columns
	/**
	 * Columns to alter
	 *
	 * @var Dao_Column[] key is the old name of the column
	 */
	private $alter_columns = [];

	//---------------------------------------------------------------------------------------- $table
	/**
	 * @var Dao_Table
	 */
	private $table;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $table Dao_Table
	 */
	public function __construct(Dao_Table $table)
	{
		$this->table = $table;
	}

	//------------------------------------------------------------------------------------- addColumn
	/**
	 * Adds an added column
	 *
	 * @param $column Dao_Column
	 */
	public function addColumn(Dao_Column $column)
	{
		$this->add_columns[$column->getName()] = $column;
	}

	//------------------------------------------------------------------------------------- addColumn
	/**
	 * Adds an altered column
	 *
	 * @param $old_column_name string
	 * @param $column Dao_Column
	 */
	public function alterColumn($old_column_name, Dao_Column $column)
	{
		$this->alter_columns[$old_column_name] = $column;
	}

	//----------------------------------------------------------------------------------------- build
	/**
	 * Builds the SQL query to alter table
	 *
	 * @return string
	 */
	public function build()
	{
		$sqls = [];
		foreach ($this->add_columns as $add) {
			$sqls[] = 'ADD COLUMN ' . $add->toSql();
		}
		foreach ($this->alter_columns as $column_name => $alter) {
			$sqls[] = ' CHANGE COLUMN ' . BQ . $column_name . BQ . SP . $alter->toSql();
		}
		return 'ALTER TABLE ' . BQ . $this->table->getName() . BQ . SP
			. join(', ', $sqls);
	}

	//--------------------------------------------------------------------------------------- isReady
	/**
	 * Returns true if the builder is ready to build
	 *
	 * The builder is ready if there is something to do
	 *
	 * @return boolean
	 */
	public function isReady()
	{
		return $this->add_columns || $this->alter_columns;
	}

}
