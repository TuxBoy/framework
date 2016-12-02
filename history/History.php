<?php
namespace ITRocks\Framework;

use ITRocks\Framework\History\Manager;
use ITRocks\Framework\History\Prototype;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Tools\Date_Time;

/**
 * Every _History class should extend this
 *
 * You must @override object @var Class_Name into the final class
 * Or create another property with @replaces object
 *
 * You must override constant OBJECT_PROPERTY_NAME in inherited classes!
 *
 * BE CAREFUL: After a Dao search, try not to use $object or replaced property in a loop
 * because it will read object from database on each step of the loop
 *
 * @representative object, date, property_name, old_value, new_value
 * @set History
 * @sort date
 */
abstract class History
{

	//-------------------------------------------------------------------------- OBJECT_PROPERTY_NAME
	/**
	 * Name of the property linking to main object
	 * You must override this constant in inherited classes!
	 * @see Prototype\History
	 */
	const OBJECT_PROPERTY_NAME = 'object';

	//----------------------------------------------------------------------------------------- $date
	/**
	 * @default Date_Time::now
	 * @link DateTime
	 * @var Date_Time
	 */
	public $date;

	//------------------------------------------------------------------------------------ $highlight
	/**
	 * @getter
	 * @readonly
	 * @user invisible
	 * @store false
	 * @var string
	 */
	public $highlight;

	//--------------------------------------------------------------------------------------- $object
	/**
	 * You must @override object @var Class_Name into the final class
	 * Or create another property with @replaces object
	 *
	 * @link Object
	 * @mandatory
	 * @var object
	 */
	public $object;

	//------------------------------------------------------------------------------------ $new_value
	/**
	 * @var string|mixed
	 */
	public $new_value;

	//------------------------------------------------------------------------------------ $old_value
	/**
	 * @var string|mixed
	 */
	public $old_value;

	//-------------------------------------------------------------------------------- $property_name
	/**
	 * @var string
	 */
	public $property_name;

	//----------------------------------------------------------------------------------------- $user
	/**
	 * @default User::current
	 * @link Object
	 * @var User
	 */
	public $user;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $object        object
	 * @param $property_name string
	 * @param $old_value     mixed
	 * @param $new_value     mixed
	 * @param $date          Date_Time|null
	 */
	public function __construct(
		$object = null, $property_name = null, $old_value = null, $new_value = null, $date = null
	) {
		if (isset($object) && isset($property_name)) {
			$this->object = $object;
			$this->property_name = $property_name;
			$this->old_value = (is_object($old_value) && Dao::getObjectIdentifier($old_value))
				? Dao::getObjectIdentifier($old_value)
				: strval($old_value);
			$this->new_value = (is_object($new_value) && Dao::getObjectIdentifier($old_value))
				? Dao::getObjectIdentifier($new_value)
				: strval($new_value);
		}
		if (isset($date)) {
			$this->date = $date;
		}
		elseif (is_null($this->date)) {
			$this->date = Date_Time::now();
		}
		if (is_null($this->user)) {
			$this->user = User::current();
		}
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return empty($this->date) ? '' : Loc::dateToLocale($this->date);
	}

	//---------------------------------------------------------------------------------- getHighlight
	/* @noinspection PhpUnusedPrivateMethodInspection @getter */
	/**
	 * @return string
	 */
	public function getHighlight()
	{
		if ($object_class_name = Manager::getObjectClassName(get_class($this))) {
			return Manager::isHighlighted($object_class_name, $this->property_name) ? 'highlight' : '';
		}
		return '';
	}

	//------------------------------------------------------------------------- getObjectPropertyName
	/**
	 * Returns the name of the property linking to main object
	 *
	 * WARNING : this method is used with a dynamic class name call, like for example
	 * call_user_func([$class_name, 'getObjectPropertyName'])
	 * So even if there is no usage found in your favorite editor, do not remove or refactor this
	 * method without taking care of string occurrences of method name!
	 *
	 * @return string
	 */
	public static function getObjectPropertyName()
	{
		return static::OBJECT_PROPERTY_NAME;
	}

	//-------------------------------------------------------------------------------- isNewValueVoid
	/**
	 * @return boolean
	 */
	public function isNewValueVoid()
	{
		return !strlen($this->new_value);
	}

	//-------------------------------------------------------------------------------- isOldValueVoid
	/**
	 * @return boolean
	 */
	public function isOldValueVoid()
	{
		return !strlen($this->old_value);
	}

}
