<?php
namespace SAF\Framework;
use AopJoinpoint;
use mysqli;

class Mysql_Maintainer implements Plugin
{

	//----------------------------------------------------------------------------------- createTable
	/**
	 * Create a table in database, using a data class structure
	 *
	 * @param $mysqli mysqli
	 * @param $class_name string
	 */
	private static function createTable(mysqli $mysqli, $class_name)
	{
		$table = Mysql_Table_Builder_Class::build($class_name);
		$mysqli->query((new Sql_Create_Table_Builder($table))->build());
	}

	//---------------------------------------------------------------------------- createImplicitType
	/**
	 * Create a table in database, which has no associated class, using fields names
	 *
	 * @param $mysqli       mysqli
	 * @param $table_name   string
	 * @param $column_names string[]
	 * @return boolean
	 */
	private static function createImplicitTable(mysqli $mysqli, $table_name, $column_names)
	{
		$table = new Mysql_Table($table_name);
		foreach ($column_names as $column_name) {
			$table->addColumn(Mysql_Column_Builder::buildLink($column_name));
		}
		$mysqli->query((new Sql_Create_Table_Builder($table))->build());
		return true;
	}

	//--------------------------------------------------------------------- createTableWithoutContext
	/**
	 * Create table (probably links table) without context
	 *
	 * @param $mysqli     mysqli
	 * @param $table_name string
	 * @param $query      string
	 * @return boolean
	 */
	private static function createTableWithoutContext(mysqli $mysqli, $table_name, $query)
	{
		$alias = "t" . (
			(substr($query, strpos($query, "`" . $table_name . "` t") + strlen($table_name) + 4)) + 0
		);
		$i = 0;
		$column_names = array();
		while (($i = strpos($query, $alias . ".", $i)) !== false) {
			$i += strlen($alias) + 1;
			$field_name = substr($query, $i, strpos($query, " ", $i) - $i);
			$column_names[] = $field_name;
		}
		if (!$column_names) {
			if (substr($query, 0, 11) == "INSERT INTO") {
				$column_names = explode(",", str_replace(array("`", " "), "", mParse($query, "(", ")")));
			}
			elseif (substr($query, 0, 6) == "UPDATE") {
				// @todo create table without context UPDATE columns detection
			}
			elseif (substr($query, 0, 6) == "DELETE") {
				// @todo create table without context DELETE columns detection
			}
		}
		return self::createImplicitTable($mysqli, $table_name, $column_names);
	}

	//--------------------------------------------------------------------------------- onMysqliQuery
	/**
	 * This is called after each mysql query in order to update automatically database structure in case of errors
	 *
	 * @param $joinpoint AopJoinpoint
	 */
	public static function onMysqliQuery(AopJoinpoint $joinpoint)
	{
		/** @var $mysqli Contextual_Mysqli */
		$mysqli = $joinpoint->getObject();
		$errno = $mysqli->errno;
		if ($errno && isset($mysqli->context)) {
			$error = $mysqli->error;
			$retry = false;
			$query = $joinpoint->getArguments()[0];
			$context = is_array($mysqli->context) ? $mysqli->context : array($mysqli->context);
			if ($errno == Mysql_Errors::ER_NO_SUCH_TABLE) {
				$error_table_name = self::parseNameFromError($error);
				foreach ($context as $key => $context_class) {
					$context_table = is_array($context_class) ? $key : Dao::storeNameOf($context_class);
					if ($context_table === $error_table_name) {
						if (!is_array($context_class)) {
							self::createTable($mysqli, $context_class);
						}
						else {
							self::createImplicitTable($mysqli, $context_table, $context_class);
						}
						$retry = true;
					}
				}
				if (!$retry) {
					$retry = self::createTableWithoutContext($mysqli, $error_table_name, $query);
				}
			}
			elseif ($errno == Mysql_Errors::ER_BAD_FIELD_ERROR) {
				foreach ($context as $context_class) {
					if (self::updateTable($mysqli, $context_class)) {
						$retry = true;
					}
				}
			}
			if ($retry) {
				$result = $mysqli->query($query);
				$joinpoint->setReturnedValue($result);
			}
		}
	}

	//---------------------------------------------------------------------------- parseNameFromError
	/**
	 * Gets the first name between '' from a mysqli error message
	 *
	 * ie table name or field name
	 *
	 * @param $error string
	 * @return string
	 */
	private static function parseNameFromError($error)
	{
		$i = strpos($error, "'") + 1;
		$j = strpos($error, "'", $i);
		$name = substr($error, $i, $j - $i);
		if (strpos($name, ".")) {
			$name = substr($name, strrpos($name, ".") + 1);
		}
		if (substr($name, 0, 1) == "`" && substr($name, -1) == "`") {
			$name = substr($name, 1, -1);
		}
		return $name;
	}

	//-------------------------------------------------------------------------------------- register
	public static function register()
	{
		Aop::add(Aop::AFTER, "mysqli->query()", array(__CLASS__, "onMysqliQuery"));
	}

	//----------------------------------------------------------------------------------- updateTable
	/**
	 * Update table structure corresponding to a data class
	 *
	 * @param $mysqli mysqli
	 * @param $class_name string
	 * @return boolean true if an update query has been generated and executed
	 */
	private static function updateTable(mysqli $mysqli, $class_name)
	{
		$class_table = Mysql_Table_Builder_Class::build($class_name);
		$mysql_table = Mysql_Table_Builder_Mysqli::build($mysqli, Dao::storeNameOf($class_name));
		$mysql_columns = $mysql_table->getColumns();
		$builder = new Sql_Alter_Table_Builder($mysql_table);
		foreach ($class_table->getColumns() as $column) {
			if (!isset($mysql_columns[$column->getName()])) {
				$builder->addColumn($column);
			}
			elseif (!$column->equiv($mysql_columns[$column->getName()])) {
				$builder->alterColumn($column->getName(), $column);
			}
		}
		if ($builder->isReady()) {
			$mysqli->query($builder->build());
			return true;
		}
		return false;
	}

}
