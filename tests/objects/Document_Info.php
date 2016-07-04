<?php
namespace SAF\Framework\Tests\Objects;

/**
 * Document info test class
 * Used to test abstract properties
 */
class Document_Info
{

	//----------------------------------------------------------------------------------------- $name
	/**
	 * @var string
	 */
	public $name;

	//------------------------------------------------------------------------------------- $abstract
	/**
	 * @link Object
	 * @var Document
	 */
	public $abstract;

	//------------------------------------------------------------------------------------ $interface
	/**
	 * @link Object
	 * @var Interfaced
	 */
	public $interface;

	//--------------------------------------------------------------------------------------- $object
	/**
	 * @link Object
	 * @var object
	 */
	public $object;

	//---------------------------------------------------------------------------------------- $trait
	/**
	 * @link Object
	 * @var Has_Counter
	 */
	public $trait;

}
