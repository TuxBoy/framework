<?php
namespace ITRocks\Framework\Reflection\Annotation\Property;

use ITRocks\Framework\Reflection\Annotation\Template\List_Annotation;

/**
 * User annotation : a list of parameters concerning the accessibility of the property for the user
 */
class User_Annotation extends List_Annotation
{

	//------------------------------------------------------------------------------------ ANNOTATION
	const ANNOTATION = 'user';

	//---------------------------------------------------------------------------------------- HIDDEN
	const HIDDEN = 'hidden';

	//------------------------------------------------------------------------------------ HIDE_EMPTY
	const HIDE_EMPTY = 'hide_empty';

	//-------------------------------------------------------------------------------------- IF_EMPTY
	const IF_EMPTY = 'if_empty';

	//------------------------------------------------------------------------------------- INVISIBLE
	const INVISIBLE = 'invisible';

	//---------------------------------------------------------------------------------------- NO_ADD
	const NO_ADD = 'no_add';

	//------------------------------------------------------------------------------------- NO_DELETE
	const NO_DELETE = 'no_delete';

	//-------------------------------------------------------------------------------------- READONLY
	const READONLY = 'readonly';

	//---------------------------------------------------------------------------------------- $value
	/**
	 * Annotation value
	 * - hidden : the property will be generated into lists or output forms, but with a 'hidden' class
	 * - hide_empty : the property will not be displayed into output views if value is empty,
	 *   but will be still visible into edit views
	 * - invisible : the property will not be displayed (nor exist) into lists, output forms, property
	 *   selector, etc. any user template
	 * - readonly : the property will be displayed but will not be accessible for modification
	 * Value that only works for basic type (not supported on collection and map)
	 * - if_empty : the property will be displayed editable if value is empty, read_only otherwise
	 *   notes: incompatible with @user_default, incompatible with "hide_empty" value.
	 * Value that only works for collection/map
	 * - no_add : forbids user to add a new element
	 * - no_delete : forbids user to delete any element
	 *
	 * @todo readonly should be implicitly set when @read_only is enabled
	 * @todo readonly should be replaced by two-words read_only
	 * @values hidden, hide_empty, if_empty, invisible, no_add, no_delete, readonly
	 * @var string[]
	 */
	public $value;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value string
	 */
	public function __construct($value)
	{
		parent::__construct($value);
		$this->validate();
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Adds a value to the annotation list of values
	 *
	 * @param $value string
	 * @return boolean
	 */
	public function add($value)
	{
		parent::add($value);
		return $this->validate();
	}

	//---------------------------------------------------------------------------------------- remove
	/**
	 * Remove a value and return true if the value was here and removed, false if the value
	 * already was not here
	 *
	 * @param $value string
	 * @return boolean
	 */
	public function remove($value)
	{
		return parent::remove($value) && $this->validate();
	}

	//-------------------------------------------------------------------------------------- validate
	/**
	 * Check that values list are valid
	 *
	 * @return boolean
	 */
	protected function validate()
	{
		if ($this->has(self::HIDE_EMPTY) && $this->has(self::IF_EMPTY)) {
			trigger_error(
				self::IF_EMPTY . ' and ' . self::HIDE_EMPTY . ' values are incompatible',
				E_USER_ERROR
			);
			return false;
		}
		return true;
	}

}
