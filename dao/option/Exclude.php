<?php
namespace ITRocks\Framework\Dao\Option;

use ITRocks\Framework\Dao\Option;

/**
 * A DAO exclude option, to restrict the action to all properties but the the given list of excluded
 * property names
 */
class Exclude extends Properties
{

	//------------------------------------------------------------------------------------------ have
	/**
	 * Returns true if any of the 'exclude properties' options has the property name
	 *
	 * If $no_property_returns_true is set to false, the function will return false if there is none
	 * If $no_property_returns_true is kept to true, the function will return true if there is none
	 *
	 * @param $options                  Option[]
	 * @param $property                 string
	 * @param $no_property_returns_true boolean if there is no Properties option, returns this value
	 * @return boolean
	 */
	public static function have(array $options, $property, $no_property_returns_true = false)
	{
		return parent::have($options, $property, $no_property_returns_true);
	}

}
