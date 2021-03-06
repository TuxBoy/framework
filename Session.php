<?php
namespace ITRocks\Framework;

use ITRocks\Framework\Plugin\Manager;
use Serializable;

/**
 * A class to manage variables and objects that are kept for the session time
 */
class Session implements Serializable
{

	//--------------------------------------------------------------------------------------- CURRENT
	/**
	 * A 'current' constant used for array storages / current method dynamic calls
	 */
	const CURRENT = 'current';

	//--------------------------------------------------------------------------------------- PLUGINS
	const PLUGINS = 'plugins';

	//-------------------------------------------------------------------------------------- $stopped
	/**
	 * When true, the session will be closed at the end of the script execution.
	 * This is the case when an action disconnects the action : all session data will be reset,
	 * including the session id.
	 *
	 * Do not set this directly to true : call stop()
	 *
	 * @see stop()
	 * @var boolean
	 */
	public $stopped = false;

	//-------------------------------------------------------------------------------------- $current
	/**
	 * @var object[]|string[]
	 */
	private $current;

	//---------------------------------------------------------------------------------- $environment
	/**
	 * @values development, production, test
	 * @var string
	 */
	public $environment;

	//-------------------------------------------------------------------------------------- $plugins
	/**
	 * @var Manager
	 */
	public $plugins;

	//-------------------------------------------------------------------------- $temporary_directory
	/**
	 * The application temporary directory that you can get using
	 * Application::current()->getTemporaryFilesPath()
	 *
	 * Default will be /tmp/Application_Class_Name
	 *
	 * @var string
	 */
	public $temporary_directory;

	//-------------------------------------------------------------------------------- cloneSessionId
	/**
	 * Returns a cloned session id
	 *
	 * This feature enables session data cloning, and is useful when you want to call scripts using
	 * localhost keeping your actual session opened.
	 *
	 * What is done by cloneSid :
	 * - a new session id is registered
	 * - the new session file is immediately created with the data of the current session
	 *
	 * @return string the cloned session id
	 */
	public static function cloneSessionId()
	{
		$old_id = session_id();
		session_regenerate_id();
		$new_id = session_id();
		file_put_contents(session_save_path() . SL . 'sess_' . $new_id, session_encode());
		session_id($old_id);
		return $new_id;
	}

	//--------------------------------------------------------------------------------------- current
	/**
	 * @param $set_current Session
	 * @return Session
	 */
	public static function current(Session $set_current = null)
	{
		if ($set_current) {
			$_SESSION['session'] = $set_current;
			return $set_current;
		}
		return isset($_SESSION['session']) ? $_SESSION['session'] : null;
	}

	//------------------------------------------------------------------------------------------- get
	/**
	 * Get the object of class $class_name from session
	 *
	 * @param $class_name     string
	 * @param $create_default boolean|callable Create a default object for the class name if does not
	 *        exist. Can be callable that creates the default object
	 * @return object|null
	 */
	public function get($class_name, $create_default = false)
	{
		if (isset($this->current[$class_name])) {
			$current = $this->current[$class_name];
			if (is_array($current)) {
				$current = $current[1];
				$this->current[$class_name] = $current = (
					is_numeric($current)
						? Dao::read($current, $class_name)
						: unserialize($current)
				);
			}
			return $current;
		}
		elseif ($create_default) {
			return $this->current[$class_name] = (
				is_callable($create_default)
					? call_user_func($create_default)
					: Builder::create($class_name)
			);
		}
		else {
			return null;
		}
	}

	//---------------------------------------------------------------------------------------- getAll
	/**
	 * Get all objects from session
	 *
	 * @return object[] index is class name, value is an object
	 */
	public function getAll()
	{
		return $this->current;
	}

	//---------------------------------------------------------------------------------------- getAny
	/**
	 * Get all objects from session having $class_name as class or parent class
	 *
	 * @param $class_name string
	 * @return object[] key is the class name of the object
	 */
	public function getAny($class_name)
	{
		$get = [];
		foreach ($this->getAll() as $key => $value) {
			if (isset(class_parents($key)[$class_name])) {
				$get[$key] = $value;
			}
		}
		return $get;
	}

	//---------------------------------------------------------------------------- getApplicationName
	/**
	 * Gets the current application name without having to unserialize it if serialized
	 * @return Application
	 */
	public function getApplicationName()
	{
		$current = $this->current[Application::class];
		// TODO parse current[1] between '"' and replace array with string if R work well
		$class_name = is_array($current) ? $current[0] : get_class($current);
		$application_name = substr($class_name, 0, strrpos($class_name, BS));
		return strtolower(substr($application_name, strrpos($application_name, BS) + 1));
	}

	//---------------------------------------------------------------------------------------- remove
	/**
	 * Remove an object from session
	 *
	 * @param $object_class string | object
	 */
	public function remove($object_class)
	{
		unset($this->current[is_string($object_class) ? $object_class : get_class($object_class)]);
	}

	//------------------------------------------------------------------------------------- removeAny
	/**
	 * Remove any session variable that has $object_class as class or parent class
	 *
	 * @param $object_class string | object
	 */
	public function removeAny($object_class)
	{
		$class_name = is_string($object_class) ? $object_class : get_class($object_class);
		$this->remove($class_name);
		foreach ($this->getAll() as $key => $value) {
			if (class_exists($key) && isset(class_parents($key)[$class_name])) {
				$this->remove($key);
			}
		}
	}

	//------------------------------------------------------------------------------------- serialize
	/**
	 * @return string
	 */
	public function serialize()
	{
		$data = [
			self::CURRENT                      => [],
			Configuration::ENVIRONMENT         => $this->environment,
			self::PLUGINS                      => $this->plugins,
			Configuration::TEMPORARY_DIRECTORY => $this->temporary_directory
		];
		if (isset($this->current)) {
			foreach ($this->current as $class_name => $object) {
				if (is_object($object)) {
					$object = [$class_name, Dao::getObjectIdentifier($object) ?: serialize($object)];
				}
				$data[self::CURRENT][$class_name] = $object;
			}
		}
		return serialize($data);
	}

	//------------------------------------------------------------------------------------------- set
	/**
	 * Set a session's object
	 *
	 * @param $object object|mixed can be null (then nothing is set)
	 * @param $class_name string if not set, object class is be the object identifier. Can be a free string too
	 */
	public function set($object, $class_name = null)
	{
		if (isset($object)) {
			$this->current[isset($class_name) ? $class_name : get_class($object)] = $object;
		}
	}

	//------------------------------------------------------------------------------------------- sid
	/**
	 * Returns current SID
	 *
	 * @example 'PHPSESSID=6kldcf5gbuk0u34cmihlo9gl22'
	 * @param $prefix string You can prefix your SID with '?' or '&' to append it to an URI or URL
	 * @return string
	 */
	public static function sid($prefix = '')
	{
		return session_id() ? ($prefix . session_name() . '=' . session_id()) : '';
	}

	//------------------------------------------------------------------------------------------ stop
	/**
	 * Stops the current session.
	 *
	 * This will destroy the session data at the end of the script.
	 * The session cookie will be removed so that a new session is created at next click.
	 */
	public function stop()
	{
		$params = session_get_cookie_params();
		if ($_COOKIE[session_name()] == session_id()) {
			setcookie(
				session_name(), '', time() - 42000,
				$params['path'], $params['domain'], $params['secure'], $params['httponly']
			);
		}
		session_destroy();
		$this->stopped = true;
	}

	//----------------------------------------------------------------------------------- unserialize
	/**
	 * @param $serialized string
	 */
	public function unserialize($serialized)
	{
		$data = unserialize($serialized);
		$this->current             = $data[self::CURRENT];
		$this->environment         = $data[Configuration::ENVIRONMENT];
		$this->plugins             = $data[self::PLUGINS];
		$this->temporary_directory = $data[Configuration::TEMPORARY_DIRECTORY];
	}

}
