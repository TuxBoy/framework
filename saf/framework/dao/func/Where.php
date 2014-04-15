<?php
namespace SAF\Framework\Dao\Func;

use SAF\Framework\Sql\Builder;

/**
 * A Dao where function applies only to conditions : it changes the condition behavior
 */
interface Where extends Dao_Function
{

	//----------------------------------------------------------------------------------------- toSql
	/**
	 * Returns the Dao function as SQL
	 *
	 * @param $builder       Builder\Where the sql query builder
	 * @param $property_path string the property path
	 * @return string
	 */
	public function toSql(Builder\Where $builder, $property_path);

}