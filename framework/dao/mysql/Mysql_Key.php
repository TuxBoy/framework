<?php
namespace SAF\Framework;

class Mysql_Key implements Dao_Key
{

	//---------------------------------------------------------------------------------- $Cardinality
	private $Cardinality;

	//-------------------------------------------------------------------------------------- $Comment
	private $Comment;

	//------------------------------------------------------------------------------------ $Collation
	private $Collation;

	//---------------------------------------------------------------------------------- $Column_name
	private $Column_name;

	//-------------------------------------------------------------------------------- $Index_comment
	private $Index_comment;

	//----------------------------------------------------------------------------------- $Index_type
	private $Index_type;

	//------------------------------------------------------------------------------------- $Key_name
	private $Key_name;

	//----------------------------------------------------------------------------------- $Non_unique
	private $Non_unique;

	//----------------------------------------------------------------------------------------- $Null
	private $Null;

	//--------------------------------------------------------------------------------------- $Packed
	private $Packed;

	//--------------------------------------------------------------------------------- $Seq_in_index
	private $Seq_in_index;

	//------------------------------------------------------------------------------------- $Sub_part
	private $Sub_part;

}
