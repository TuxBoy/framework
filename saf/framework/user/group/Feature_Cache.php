<?php
namespace SAF\Framework\User\Group;

use SAF\Framework\Application;
use SAF\Framework\Dao;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\Tools\Names;

/**
 * This processing class enables to store the list of features in cache :
 * - scan all the projects tree for business objects and yaml feature files
 *
 * The features cache is the Features set, stored into Dao's data link.
 */
class Feature_Cache
{

	//-------------------------------------------------------------------------------- getClassHeader
	/**
	 * Optimized file read : gets only the top class doc-comment and class definition
	 *
	 * @param $filename string
	 * @return string
	 */
	private function getClassHeader($filename)
	{
		$file = fopen($filename, 'r');
		$buffer = '';
		do {
			$buffer .= fread($file, 512);
		} while ((!strpos($buffer, LF . '{') || !strpos($buffer, LF . SP . '*/')) && !feof($file));
		fclose($file);
		return $buffer;
	}

	//-------------------------------------------------------------------------------- isFeatureClass
	/**
	 * Returns true if the file class header buffer reveals a @feature class
	 *
	 * The class source code must have been correctly set to @feature and correctly indented.
	 *
	 * @param $buffer string A file class header buffer : read by getClassHeader()
	 * @return boolean true if is a business class
	 */
	private function isFeatureClass($buffer)
	{
		return strpos($buffer, LF . SP . '* @feature');
	}

	//----------------------------------------------------------------------------------------- reset
	/**
	 * Resets the feature cache : replace it with the scanned features
	 */
	public function reset()
	{
		$this->saveToCache($this->scanFeatures());
	}

	//---------------------------------------------------------------------------------- scanFeatures
	/**
	 * Scan the application source files for feature classes that may give us final user features
	 *
	 * return Feature[]
	 */
	public function scanFeatures()
	{
		$application = Application::current();
		$files = $application->include_path->getSourceFiles();
		/** @var $php_files_features  Feature[] */
		/** @var $yaml_files_features Feature[] */
		$php_files_features  = [];
		$yaml_files_features = [];
		foreach ($files as $filename) {
			if (substr($filename, -5) === '.yaml') {
				if (substr($filename, 0, 34) !== 'saf/framework/user/group/defaults/')
				$yaml_files_features = array_merge($yaml_files_features, $this->scanYamlFile($filename));
			}
			elseif (substr($filename, -4) === '.php') {
				$php_files_features = array_merge($php_files_features, $this->scanPhpFile($filename));
			}
		}
		return array_merge($php_files_features, $yaml_files_features);
	}

	//----------------------------------------------------------------------------------- saveToCache
	/**
	 * @param $features Feature[]
	 */
	public function saveToCache($features)
	{
		Dao::begin();
		Dao::truncate(Feature::class);
		foreach ($features as $feature) {
			Dao::write($feature);
		}
		Dao::commit();
	}

	//------------------------------------------------------------------------------------- scanClass
	/**
	 * @param $class_name string
	 * @return Feature[]
	 */
	private function scanClass($class_name)
	{
		$class = new Reflection_Class($class_name);
		$feature_annotations = $class->getAnnotations('feature');

		foreach ($feature_annotations as $annotation) {
			if ($annotation->value && ($annotation->value !== true)) {
				$ignore_empty_features = true;
				break;
			}
		}

		/** @var $features Feature[] */
		// class explicit features
		$features = [];
		if (isset($ignore_empty_features)) {
			foreach ($feature_annotations as $annotation) {
				if ($annotation->value) {
					$path = lParse($annotation->value, SP);
					$name = rParse($annotation->value, SP);
					if (!strpos($path, SL)) {
						$path = str_replace(BS, SL, $class_name) . SL . $path;
					}
					$features[$path] = new Feature($path, $name);
				}
			}
		}

		// apply implicit features
		else {
			foreach (Feature::getImplicitFeatures() as $feature) {
				$path = str_replace(BS, SL, $class_name) . SL . $feature;
				$features[$path] = new Feature($path);
			}
		}

		return $features;
	}

	//---------------------------------------------------------------------------------- scanYamlFile
	/**
	 * @param $filename string
	 * @return Feature[]
	 */
	private function scanYamlFile($filename)
	{
		/** @var $features Feature[] */
		$features = [];
		foreach (Yaml::fromFile($filename) as $path => $yaml) {
			$features[$path] = new Feature($path);
			$features[$path]->yaml = $yaml;
		}
		return $features;
	}

	//----------------------------------------------------------------------------------- scanPhpFile
	/**
	 * @param $filename string
	 * @return Feature[]
	 */
	private function scanPhpFile($filename)
	{
		/** @var $features Feature[] */
		$features = [];
		$buffer = $this->getClassHeader($filename);
		if ($this->isFeatureClass($buffer)) {
			$class_name = Names::fileToClass($filename);
			$features = $this->scanClass($class_name);
		}
		return $features;
	}

}
