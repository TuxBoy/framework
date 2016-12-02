<?php
namespace ITRocks\Framework;

use ITRocks\Framework\Generator\IGenerative;
use ITRocks\Framework\PHP\Cache;
use ITRocks\Framework\PHP\Compiler;
use ITRocks\Framework\PHP\Reflection_Source;
use ITRocks\Framework\Plugin\Register;
use ITRocks\Framework\Plugin\Registerable;
use ITRocks\Framework\Tools\Files;
use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\Tools\Paths;
use ITRocks\Framework\Updater\Application_Updater;
use ITRocks\Framework\Updater\Updatable;

/**
 * Generator of dynamic classes
 * BY default, the generator create the source class on generator cache dir
 */
class Generator extends Cache implements Registerable, Updatable
{

	//-------------------------------------------------------------------------------- CACHE_DIR_NAME
	/**
	 * Name of directory, created in project cache dir, to contain source files of generated classes
	 */
	const CACHE_DIR_NAME = 'generated';

	//------------------------------------------------------------------------- GENERATE_SOURCE_CLASS
	/**
	 * Configuration key to enable/disable generation of source file in cache dir
	 */
	const GENERATE_SOURCE_CLASS = 'generate_source_class';

	//----------------------------------------------------------------------------------- $generators
	/**
	 * @var IGenerative[]
	 */
	private $generators = [];

	//------------------------------------------------------------------------------- $source_classes
	/**
	 * List of the $source to compile
	 *
	 * @var Reflection_Source[]
	 */
	public $source_classes = [];

	//--------------------------------------------------------------------------------- addGenerative
	/**
	 * Plugin that want to register a generative will call this method giving themselves as argument
	 *
	 * @param IGenerative $generator
	 */
	public function addGenerator(IGenerative $generator)
	{
		$this->generators[] = $generator;
	}

	//-------------------------------------------------------------------------------------- addClass
	/**
	 * IGenerative plugins that have been added can call this method to add a class/source to generate
	 *
	 * @param $class_name string
	 * @param $source     string
	 */
	public function addClass($class_name, $source)
	{
		$this->source_classes[$class_name] = $source;
	}

	//-------------------------------------------------------------------------------------- generate
	/**
	 * Generate all dynamic classes
	 *
	 */
	private function generate()
	{
		foreach ($this->source_classes as $class_name => &$source)
		{
			if (is_string($source)) {
				$source = new Reflection_Source($source);
			}
			$this->generateClass($class_name, $source);
		}
	}

	//--------------------------------------------------------------------------------- generateClass
	/**
	 * Generate a dynamic class and give it to the compiler
	 *
	 * @param $class_name string
	 * @param $source     Reflection_Source
	 */
	private function generateClass($class_name, $source)
	{
		$this->generateSourceClass($class_name, $source);

		/** @var $compiler Compiler */
		static $compiler;
		if (!isset($compiler)) {
			$compiler = Session::current()->plugins->get(Compiler::class);
		}
		$compiler->addSource($source);
	}

	//--------------------------------------------------------------------------- generateSourceClass
	/**
	 * Generate a dynamic class on disk
	 *
	 * @param $class_name string
	 * @param $source     Reflection_Source
	 */
	private function generateSourceClass($class_name, $source)
	{
		$cache_dir = $this->getCacheDir();
		$file_path = $cache_dir . SL . Names::classToPath($class_name) . '.php';
		$absolute_file_path = Paths::$file_root . Paths::$project_uri . SL . $file_path;
		Files::mkdir(dirname($absolute_file_path));
		$fd = fopen($absolute_file_path, 'w');
		if ($fd) {
			fputs($fd, $source->getSource());
			fclose($fd);
		}
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Registration code for the plugin
	 *
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		/** @var $application_updater Application_Updater */
		$application_updater = Session::current()->plugins->get(Application_Updater::class);
		$application_updater->addUpdatable($this);
	}

	//---------------------------------------------------------------------------------------- update
	/**
	 * @param $last_time integer
	 */
	public function update($last_time)
	{
		parent::manageCacheDirReset();

		$done_something = false;
		foreach($this->generators as $generator) {
			$done_something |= $generator->generate($this);
		}
		if ($done_something) {
			$this->generate();
		}
	}

}
