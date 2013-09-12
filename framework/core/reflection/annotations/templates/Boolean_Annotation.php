<?php
namespace SAF\Framework;

/**
 * A boolean annotation can have true or false value
 * Default value of these annotations are always false.
 * When the annotation is set without value, the value is true.
 * To set the value explicitely to false, annotate @annotation false or @annotation 0.
 */
abstract class Boolean_Annotation extends Annotation
{

	//---------------------------------------------------------------------------------------- $value
	/**
	 * For boolean annotations, values are boolean and not string
	 *
	 * @override
	 * @var boolean
	 */
	public $value;

	//---------------------------------------------------------------------------------------- $value
	/**
	 * Register value as boolean
	 *
	 * If a boolean annotation has no value or is not "false" or zero, annotation's value will be true.
	 *
	 * @param $value string
	 */
	public function __construct($value)
	{
		$this->value = (
			($value !== null) && ($value !== 0) && ($value !== false) && ($value !== "false")
		);
	}

}
