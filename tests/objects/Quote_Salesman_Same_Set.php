<?php
namespace SAF\Framework\Tests\Objects;

/**
 * A salesman with specific behavior but having same storage table (same set)
 *
 */
class Quote_Salesman_Same_Set extends Quote_Salesman
{

	//------------------------------------------------------------------------------ $additional_text
	/**
	 * @store false
	 * @var string
	 */
	public $additional_text;

}
