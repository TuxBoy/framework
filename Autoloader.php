<?php
namespace ITRocks\Framework;

use ITRocks\Framework\AOP\Include_Filter;
use ITRocks\Framework\Builder\Class_Builder;

/**
 * This is the core autoloader : it searches and load PHP scripts containing classes
 */
class Autoloader
{

	//------------------------------------------------------------------------------------- CACHE_DIR
	const CACHE_DIR = 'cache/generated';

	//-------------------------------------------------------------------------------------- autoload
	/**
	 * Includes the php file that contains the given class (must contain namespace)
	 *
	 * @param $class_name string class name (with or without namespace)
	 */
	public function autoload($class_name)
	{
		$result = $this->tryToLoad($class_name);
		// instantiate plugin
		if ($result && class_exists($class_name, false) && is_a($class_name, Plugin::class, true)) {
			if (Session::current()) {
				Session::current()->plugins->get($class_name);
			}
		}
	}

	//----------------------------------------------------------------------------------- getFileName
	/**
	 * Returns the source file name of a class name, including path prefix if file comes from cache
	 *
	 * @param $class_name string
	 * @return boolean|string
	 */
	public static function getFileName($class_name)
	{
		$split_file_name = self::getSplitFileName($class_name);
		return $split_file_name[1] . $split_file_name[0];
	}

	//----------------------------------------------------------------------------- getSimpleFileName
	/**
	 * Returns the existing file name for a class, optionally checking existence of file inside
	 * a path prefix.
	 * It does not include path prefix in the returned file_name.
	 *
	 * @param $class_name  string
	 * @param $path_prefix string
	 * @return string|boolean the matching file name (without its path prefix) or false if not found
	 */
	private static function getSimpleFileName($class_name, $path_prefix = '')
	{
		$path_prefix .= (strlen($path_prefix) && substr($path_prefix, -1) != '/') ? '/' : '';
		if ($i = strrpos($class_name, '\\')) {
			$namespace = strtolower(str_replace('\\', '/', substr($class_name, 0, $i)));
			$short_class_name = substr($class_name, $i + 1);
			// 'A\Class' stored into 'a/class/Class.php'
			$file1 = strtolower($namespace . '/' . $short_class_name) . '/' . $short_class_name . '.php';
			if (file_exists($path_prefix . $file1)) {
				return $file1;
			}
			// 'A\Class' stored into 'a/Class.php'
			else {
				$file2 = strtolower($namespace) . '/' . $short_class_name . '.php';
				if (file_exists($path_prefix . $file2)) {
					return $file2;
				}
			}
		}
		// 'A_Class' stored into 'A_Class.php'
		elseif (file_exists($path_prefix . ($file4 = $class_name . '.php'))) {
			return $file4;
		}
		return false;
	}

	//------------------------------------------------------------------------------ getSplitFileName
	/**
	 * Returns file name and optional path prefix for a given class
	 *
	 * @param $class_name string
	 * @return string[]|boolean [file_name, path_prefix], false if file not found
	 */
	private static function getSplitFileName($class_name)
	{
		$file_name = self::getSimpleFileName($class_name);
		if ($file_name !== false) {
			return [$file_name, ''];
		}
		else {
			$file_name = self::getSimpleFileName($class_name, self::CACHE_DIR);
			if ($file_name !== false) {
				return [$file_name, self::CACHE_DIR . '/'];
			}
		}
		return false;
	}

	//------------------------------------------------------------------------------------- tryToLoad
	/**
	 * Try to load a class.
	 * If source file is not found in project sources, it is searched in the cache of
	 * generated classes files.
	 * For built classes, it is search in compiled files
	 *
	 * @param $class_name  string class name (with or without namespace)
	 * @return integer|boolean 1
	 */
	public function tryToLoad($class_name)
	{
		$split_file_name = self::getSplitFileName($class_name);
		if ($split_file_name !== false) {
			$result = include_once(Include_Filter::file($split_file_name[0], $split_file_name[1]));
		}
		if ((!isset($result) || !$result) && Class_Builder::isBuilt($class_name)) {
			$built_file_name = PHP\Compiler::getCacheDir() . SL . PHP\Compiler::classToPath($class_name);
			if (file_exists($built_file_name)) {
				$result = include_once($built_file_name);
			}
		}
		// class not found
		if (!isset($result)) {
			$result = false;
		}
		return $result;
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * Register autoloader
	 */
	public function register()
	{
		include_once __DIR__ . '/../../vendor/autoload.php';
		spl_autoload_register([$this, 'autoload'], true, true);
	}

}
