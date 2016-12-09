<?php
namespace ITRocks\Framework\History;

use ITRocks\Framework\Builder\Class_Builder;
use ITRocks\Framework\Controller\Main;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Generator;
use ITRocks\Framework\Generator\IGenerative;
use ITRocks\Framework\PHP\Reflection_Source;
use ITRocks\Framework\Plugin\Activable;
use ITRocks\Framework\Plugin\Configurable;
use ITRocks\Framework\Plugin\Register;
use ITRocks\Framework\Plugin\Registerable;
use ITRocks\Framework\Reflection\Annotation\Template\Method_Annotation;
use ITRocks\Framework\Reflection\Reflection_Class;
use ITRocks\Framework\Session;
use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\Tools\Paths;
use ITRocks\Framework\Updater\Application_Updater;
use ITRocks\Framework\Widget\Output\Output_Controller;

/**
 * History management plugin
 * Manage which class will support history and which properties to historize
 *
 * If excepted properties are defined, all properties except those one will be historized
 * If only properties are defined, only those properties will be historized
 * If both defined, only "only" properties that are not "unobserved" will be historized
 *
 * Note: only source class name should be declared, no built class name (untested)
 *
 * @example  configuration :
 * Framework\History\Manager::class => [
 *   Vendor\Project\User::class => [
 *     Framework\History\Manager::HIGHLIGHT => ['login'],
 *     Framework\History\Manager::EXCEPTED => ['password'],
 *   ],
 *   Vendor\Project\Contract::class => [
 *     Framework\History\Manager::HIGHLIGHT => ['closing_date', 'status'],
 *     Framework\History\Manager::ONLY => ['closing_date', 'comment', 'signed', 'status'],
 *   ],
 * ],
 */
class Manager implements Registerable, Configurable, Activable, IGenerative
{
	use History_Output;

	//-------------------------------------------------------------------------------------- EXCEPTED
	const EXCEPTED  = 'excepted_properties';

	//------------------------------------------------------------------------------------- HIGHLIGHT
	const HIGHLIGHT = 'highlighted_properties';

	//------------------------------------------------------------------------------------------ ONLY
	const ONLY      = 'only_properties';

	//--------------------------------------------------------------------------- $added_to_generator
	/**
	 * @var boolean
	 */
	private $added_to_generator = false;

	//------------------------------------------------------------------------------ $enabled_classes
	/**
	 * List of class names that are enabled for history
	 *
	 * Note: There are 2 entries per class. 1 with key = source name, 1 with built name if it exists.
	 *       Value is declared class name in both entries.
	 * @example
	 * [
	 *  'ITRocks\Framework\User'              = 'ITRocks\Framework\User',
	 *  'Vendor\Project\Built\Framework\User' = 'ITRocks\Framework\User'
	 * ]
	 *
	 * @var string[] array of class names that will support history
	 */
	protected static $enabled_classes = [];

	//-------------------------------------------------------------------------- $excepted_properties
	/**
	 * Array of properties NOT to observe, by class name
	 *
	 * @var string[][]
	 * @example [User::class => ['password']]
	 * @example [User::class => ['name', 'client_component.little_name]]
	 */
	protected static $excepted_properties = [];

	//----------------------------------------------------------------------- $highlighted_properties
	/**
	 * Array of properties to highlight on display, by class name
	 *
	 * @var string[][]
	 * @example [User::class => ['email]]
	 * @example [User::class => ['name', 'client_component.little_name]]
	 */
	protected static $highlighted_properties = [];

	//------------------------------------------------------------------------------ $only_properties
	/**
	 * Array of only properties to historize, by class name
	 * If a property is a link on object all linked object properties will be historized (if not
	 * listed in excepted)
	 *
	 * @var string[][]
	 * @example [User::class => ['login', 'email]]
	 * @example [User::class => ['name', 'client_component.little_name]]
	 */
	protected static $only_properties = [];

	//----------------------------------------------------------------------- $prototype_class_source
	/**
	 * @var Reflection_Source
	 */
	private static $prototype_class_source;

	//------------------------------------------------------------------------------- $source_classes
	/**
	 * @var string[] array of source class names that will support history
	 */
	private static $source_classes = [];

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $configuration array|null
	 */
	public function __construct($configuration = null)
	{
		if (isset($configuration)) {
			foreach($configuration as $class_name => $class_configuration)
			{
				// case where there is no class configuration and class name is value instead of key
				if (is_integer($class_name) && is_string($class_configuration)) {
					$class_name = $class_configuration;
					$class_configuration = [];
				}
				if (!$class_configuration) {
					$class_configuration = [];
				}
				// enable class
				self::enableClass($class_name);
				// configure class
				foreach($class_configuration as $configuration_name => $properties) {
					self::setClassConfiguration($class_name, $configuration_name, $properties);
				}
			}
		}

		/** @var $updater Application_Updater */
		$updater = Session::current()->plugins->get(Application_Updater::class);
		if (!$updater->isRunning() && $updater->mustUpdate()) {
			$this->addToGenerator();
		}
	}

	//-------------------------------------------------------------------------------------- activate
	/**
	 * This method is called each time the class is loaded
	 * = when you need the plugin for the first time during the script execution
	 *
	 * Here, for each class to be historized, we add dynamic annotations @before_write, @after_write
	 */
	public function activate()
	{
		foreach (self::$enabled_classes as $class_name) {
			self::activateForClass($class_name);
			if (Class_Builder::isBuilt($class_name)) {
				$source_class_name = self::getSourceClassName($class_name);
				self::activateForClass($source_class_name);
			}
		}
	}

	//------------------------------------------------------------------------------ activateForClass
	/**
	 * Here we add dynamic annotations @before_write, @after_write to given class
	 *
	 * @param $class_name string
	 */
	public static function activateForClass($class_name)
	{
		$class = new Reflection_Class($class_name);

		$before_write_annotation = new Method_Annotation(BS . Writer::class . '::beforeWrite', $class
			, 'before_write');
		$class->addAnnotation('before_write', $before_write_annotation);

		/*$after_read_annotation = new Method_Annotation(BS . Writer::class . '::afterRead', $class
			, 'after_read');
		$class->addAnnotation('after_read', $after_read_annotation);*/

		$after_write_annotation = new Method_Annotation(BS . Writer::class . '::afterWrite', $class
			, 'after_write');
		$class->addAnnotation('after_write', $after_write_annotation);
	}

	//-------------------------------------------------------------------------------- addToGenerator
	/**
	 * Add this generative plugin to the main generator
	 */
	private function addToGenerator()
	{
		if (!$this->added_to_generator) {
			/** @var $generator Generator */
			$generator = Session::current()->plugins->get(Generator::class);
			$generator->addGenerator($this);
			$this->added_to_generator = true;
		}
	}

	//------------------------------------------------------------------------------------- doNothing
	/**
	 * Do nothing
	 */
	public static function doNothing() {}

	/**
	 * Enable a class for history
	 *
	 * @param $class_name string
	 */
	public static function enableClass($class_name) {
		// store enabled class with source and built information
		self::$source_classes[$class_name] = $class_name;
		$built_class_name = (Class_Builder::isBuilt($class_name)) ? $class_name
			: Class_Builder::builtClassName($class_name);
		if ($built_class_name && class_exists($built_class_name)) {
			self::$source_classes[$built_class_name] = $class_name;
			self::$enabled_classes[] = $used_class_name = $built_class_name;
		}
		else {
			self::$enabled_classes[] = $used_class_name = $class_name;
		}
		// init properties configuration for class name
		self::$excepted_properties[$used_class_name] = [];
		self::$highlighted_properties[$used_class_name] = [];
		self::$only_properties[$used_class_name] = [];
	}

	//-------------------------------------------------------------------------------------- generate
	/**
	 * Generate dynamic Xxxxx_History classes and send them to main generator
	 *
	 * @param $generator Generator
	 * @return boolean
	 */
	public function generate(Generator $generator = null)
	{
		self::$prototype_class_source = new Reflection_Source(
			Paths::getRelativeFileName(__DIR__ . '/prototype/History.php')
		);

		foreach (self::$enabled_classes as $class_name) {
			$source_class_name = Class_Builder::isBuilt($class_name)
				? self::getSourceClassName($class_name)
				: $class_name;
			list($generated_class_name, $generated_source) = $this->generateClass($source_class_name);
			$generator->addClass($generated_class_name, $generated_source);
		}
		return (boolean)(count(self::$enabled_classes));
	}

	//--------------------------------------------------------------------------------- generateClass
	/**
	 * Dynamically generate a Xxxxx_History class for object of class name Xxxxxx
	 *
	 * @example ITRocks\Framework\User => ITRocks\Framework\User\User_History
	 * @param $object_class_name string
	 * @return string[]
	 */
	private function generateClass($object_class_name)
	{
		$generated_class_name = $this->getHistoryClassName($object_class_name);
		$prototype_source = self::$prototype_class_source->getSource();

		$namespace = $object_class_name;
		$short_class_name = Names::classToProperty($object_class_name);
		$uc_first_short_class_name = ucfirst($short_class_name);
		$object_property = $short_class_name;

		$generated_source = preg_replace('/\/\*\*##REMOVE.+##\*\/\n/isU', '',
			str_replace(
				[
					'/**/namespace ITRocks\Framework\History\Prototype;/**/',
					'main_object_example',
					'Main_object_example',
					'Main_Object_Example',
					'##object_property##',
					'/**/$object_property/**/',
					'/**/$this->object_property/**/',
				],
				[
					"namespace $namespace;",
					$short_class_name,
					$uc_first_short_class_name,
					BS . $object_class_name,
					$object_property,
					'$' . $object_property,
					'$this->' . $object_property,
				],
				$prototype_source
			)
		);
		return [$generated_class_name, $generated_source];
	}

	//---------------------------------------------------------------------------------- getUsedClass
	/**
	 * Returns the enabled (built or source) class name or false if not enabled
	 *
	 * @param $class_name string
	 * @return string|boolean
	 */
	private static function getUsedClass($class_name)
	{
		if (Class_Builder::isBuilt($class_name)) {
			$built_class_name = $class_name;
			$source_class_name = self::getSourceClassName($class_name);
		}
		else {
			$built_class_name = Class_Builder::builtClassName($class_name);
			$source_class_name = $class_name;
		}
		if (in_array($source_class_name, self::$enabled_classes)) {
			return $source_class_name;
		}
		elseif (in_array($built_class_name,	self::$enabled_classes)
		) {
			return $built_class_name;
		}
		return false;
	}

	//--------------------------------------------------------------------------- getHistoryClassName
	/**
	 * Returns the object history class name given an object class name
	 * If given object does not support history, it returns empty string
	 *
	 * @example ITRocks\Framework\User => ITRocks\Framework\User\User_History
	 * @param $object_class_name string
	 * @return string
	 * @see self::getObjectClassName()
	 */
	public static function getHistoryClassName($object_class_name)
	{
		if (self::isEnabled($object_class_name)) {
			$history_class_name = Class_Builder::sourceClassName($object_class_name) . BS . 'History';
			return $history_class_name;
		}
		return '';
	}

	//---------------------------------------------------------------------------- getObjectClassName
	/**
	 * Returns the object source class name given an history class name
	 * If object does not support history, it returns empty string (should not occur)
	 *
	 * @example ITRocks\Framework\User => ITRocks\Framework\User\User_History
	 * @param $history_class_name string
	 * @return string
	 */
	public static function getObjectClassName($history_class_name)
	{
		$source_class_name = substr($history_class_name, 0, strrpos($history_class_name, BS));
		if ($object_class_name = self::getUsedClass($source_class_name)) {
			return $object_class_name;
		}
		return '';
	}

	//---------------------------------------------------------------------------- getSourceClassName
	/**
	 * Returns the source class name given an enabled class
	 *
	 * @param $class_name string
	 * @return string
	 */
	public static function getSourceClassName($class_name)
	{
		//todo wait for #88021 resolved
		//$source_class_name = Class_Builder::sourceClassName($class_name);
		$source_class_name = isset(self::$source_classes[$class_name])
			? self::$source_classes[$class_name]
			: false;
		return $source_class_name;
	}

	//--------------------------------------------------------------------------------- historyOutput
	/**
	 * @param $result     string
	 * @param $parameters Parameters
	 * @param $class_name string the main object class name
	 */
	public function historyOutput(&$result, Parameters $parameters, $class_name)
	{
		if (self::isEnabled($class_name)) {
			$this->output($result, $parameters, $class_name);
		}
	}

	//------------------------------------------------------------------------------------- isEnabled
	/**
	 * Returns if class name is enabled
	 *
	 * @param $class_name string
	 * @return boolean
	 */
	public static function isEnabled($class_name)
	{
		$used_class_name = self::getUsedClass($class_name);
		return $used_class_name ? true : false;
	}

	//------------------------------------------------------------------------------------ isExcepted
	/**
	 * @param $class_name    string
	 * @param $property_path string
	 * @return boolean
	 */
	protected static function isExcepted($class_name, $property_path)
	{
		$used_class_name = self::getUsedClass($class_name);
		if ($used_class_name && isset(self::$excepted_properties[$used_class_name])) {
			//check all parts of the property path
			$property_path = explode(DOT, $property_path);
			$prefix = '';
			foreach($property_path as $property_name) {
				if (in_array($prefix . $property_name, self::$excepted_properties[$used_class_name])) {
					return true;
				}
				$prefix .= $property_name . DOT;
			}
		}
		return false;
	}

	//------------------------------------------------------------------------------ isToBeHistorized
	/**
	 * @param $class_name    string
	 * @param $property_path string
	 * @return boolean
	 */
	public static function isToBeHistorized($class_name, $property_path)
	{
		$used_class_name  = self::getUsedClass($class_name);
		$to_be_historized =
			$used_class_name
			&& (
				!isset(self::$only_properties[$used_class_name])
				|| self::isOnly($class_name, $property_path)
			)
			&& !self::isExcepted($class_name, $property_path);
		return $to_be_historized;
	}

	//--------------------------------------------------------------------------------- isHighlighted
	/**
	 * @param $class_name    string
	 * @param $property_path string
	 * @return boolean
	 */
	public static function isHighlighted($class_name, $property_path)
	{
		$used_class_name = self::getUsedClass($class_name);
		return ($used_class_name && isset(self::$highlighted_properties[$used_class_name]))
			? in_array($property_path, self::$highlighted_properties[$used_class_name])
			: false;
	}

	//---------------------------------------------------------------------------------------- isOnly
	/**
	 * @param $class_name    string
	 * @param $property_path string
	 * @return boolean
	 */
	protected static function isOnly($class_name, $property_path)
	{
		$used_class_name = self::getUsedClass($class_name);
		if ($used_class_name && isset(self::$only_properties[$used_class_name])) {
			//check all parts of the property path
			$property_path = explode(DOT, $property_path);
			$prefix = '';
			foreach($property_path as $property_name) {
				if (in_array($prefix . $property_name, self::$only_properties[$used_class_name])) {
					return true;
				}
				$prefix .= $property_name . DOT;
			}
		}
		return false;
	}

	/**
	 * @param $class_name         string
	 * @param $configuration_name string self::EXCEPTED|self::HIGHLIGHT|self::ONLY
	 * @param $properties         string[]
	 */
	public static function setClassConfiguration($class_name, $configuration_name, $properties)
	{
		$used_class_name = self::getUsedClass($class_name);
		self::${$configuration_name}[$used_class_name] = $properties;
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Registration code for the plugin
	 *
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$this->addToGenerator();

		// display history after main object
		$register->aop->afterMethod([Output_Controller::class, 'run'], [$this, 'historyOutput']);

		// Here I just want to activate this plugin on each page,
		// This will be done if we have AOP on session. So we simply add AOP method that do nothing.
		// So we don't really need to do anything after the joinpoint method
		$register->aop->afterMethod([Main::class, 'resumeSession'], [self::class, 'doNothing']);
	}

}
