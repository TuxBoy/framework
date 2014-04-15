<?php
namespace SAF\Framework;

use SAF\Framework\Tools\OS;

/**
 * Include path manager
 */
class Include_Path
{

	//---------------------------------------------------------------------------- $application_class
	/**
	 * @var string
	 */
	private $application_class;

	//-------------------------------------------------------------------------- $origin_include_path
	/**
	 * The original PHP include_path is kept here
	 *
	 * @var string
	 */
	private static $origin_include_path;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $application_class string
	 */
	public function __construct($application_class)
	{
		$this->application_class = $application_class;
	}

	//-------------------------------------------------------------------------------- getDirectories
	/**
	 * This is called by getSourceDirectories() for recursive directories reading
	 *
	 * @param $path string base path
	 * @return string[] an array of directories names
	 */
	private function getDirectories($path)
	{
		$directories = [$path];
		$dir = dir($path);
		while ($entry = $dir->read()) if ($entry[0] != DOT) {
			if (is_dir($path . SL . $entry) && ($entry != 'vendor') && ($entry != 'cache')) {
				$directories = array_merge($directories, $this->getDirectories($path . SL . $entry));
			}
		}
		$dir->close();
		return $directories;
	}

	//-------------------------------------------------------------------------------- getIncludePath
	/**
	 * @return string
	 */
	public function getIncludePath()
	{
		if (!isset($this->include_path)) {
			$include_path = join(OS::$include_separator, $this->getSourceDirectories(true));
			$this->include_path = self::getOriginIncludePath() . OS::$include_separator . $include_path;
			return $this->include_path;
		}
		return $this->include_path;
	}

	//-------------------------------------------------------------------------- getOriginIncludePath
	/**
	 * Returns PHP origin include path
	 *
	 * @return string
	 */
	public static function getOriginIncludePath()
	{
		if (!self::$origin_include_path) {
			self::$origin_include_path = get_include_path();
		}
		return self::$origin_include_path;
	}

	//-------------------------------------------------------------------------- getSourceDirectories
	/**
	 * Returns the full directory list for the application, including parent's applications directory
	 *
	 * Directory names are sorted from higher-level application to basis SAF 'framework' directory
	 * Inside an application, directories are sorted randomly (according to how the php
	 * Directory->read() call works)
	 *
	 * Paths are relative to the SAF index.php base script position
	 *
	 * @param $include_subdirectories boolean
	 * @param $application_class     string
	 * @return string[]
	 */
	public function getSourceDirectories($include_subdirectories = false, $application_class = null)
	{
		if (!isset($application_class)) {
			$application_class = $this->application_class;
		}
		$app_dir = $this->getSourceDirectory($application_class);
		$directories = [];
		if ($application_class != Application::class) {
			$extends = get_parent_class($application_class);
			if ($extends) {
				$directories = $this->getSourceDirectories($include_subdirectories, $extends);
			}
		}
		/*
		// todo LOWEST multiple applications extends management
		foreach ($this->applications as $application) {
			$directories += $this->getSourceDirectories($application);
		}
		*/
		return $include_subdirectories
			? array_merge($this->getDirectories($app_dir), $directories)
			: array_merge([$app_dir], $directories);
	}

	//---------------------------------------------------------------------------- getSourceDirectory
	/**
	 * @param $application_class string
	 * @return string
	 */
	public function getSourceDirectory($application_class = null)
	{
		if (!isset($application_class)) {
			$application_class = $this->application_class;
		}
		return strtolower(
			str_replace(BS, SL, substr($application_class, 0, strrpos($application_class, BS)))
		);
	}

	//-------------------------------------------------------------------------------- getSourceFiles
	/**
	 * Returns the full files list for the application, including parent's applications directory
	 *
	 * File names are sorted from higher-level application to basis SAF 'framework' directory
	 * Inside an application, files are sorted randomly (according to how the php Directory->read() call works)
	 *
	 * Paths are relative to the SAF index.php base script position
	 *
	 * @return string[]
	 */
	public function getSourceFiles()
	{
		$files = [];
		foreach ($this->getSourceDirectories(true) as $directory) {
			$directory_slash = $directory . SL;
			if (strpos($directory_slash, '/webshop/templates/') === false) {
				$dir = dir($directory);
				while ($entry = $dir->read()) if ($entry[0] != DOT) {
					$file_path = $directory . SL . $entry;
					if (is_file($file_path)) {
						$files[] = $file_path;
					}
				}
				$dir->close();
			}
		}
		return $files;
	}

}