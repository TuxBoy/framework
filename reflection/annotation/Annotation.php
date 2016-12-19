<?php
namespace ITRocks\Framework\Reflection;

use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\Tools\Namespaces;

/**
 * All annotations classes must inherit from this or any annotation template
 */
class Annotation
{

	//----------------------------------------------------------------------------------------- BLOCK
	const BLOCK = 'block';

	//---------------------------------------------------------------------------------------- $value
	/**
	 * Annotation value
	 *
	 * @var string
	 */
	public $value;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Default annotation constructor receive the full doc text content
	 *
	 * Annotation class will have to parse it ie for several parameters or specific syntax, or if they want to store specific typed or calculated value
	 *
	 * @param $value string
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return strval($this->value);
	}

	//----------------------------------------------------------------------------- getAnnotationName
	/**
	 * Gets annotation name (the displayable root of the annotation class name, when set)
	 *
	 * @return string
	 */
	public function getAnnotationName()
	{
		return Names::classToDisplay(
			lLastParse(Namespaces::shortClassName(get_class($this)), '_Annotation')
		);
	}

	//-------------------------------------------------------------------------------------- getValue
	/**
	 * Get the value
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}
}
