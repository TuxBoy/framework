<?php
namespace ITRocks\Framework\User\Group;

use ITRocks\Framework\Tools\Names;

/**
 * User group feature(s) yaml file management class
 */
class Yaml
{

	//---------------------------------------------------------------------------------- DEFAULTS_DIR
	const DEFAULTS_DIR = __DIR__ . SL . 'defaults';

	//-------------------------------------------------------------------------------------- FEATURES
	const FEATURES = 'features';

	//-------------------------------------------------------------------------------------- INCLUDES
	const INCLUDES = 'includes';

	//------------------------------------------------------------------------------------------ NAME
	const NAME = 'name';

	//------------------------------------------------------------------------------------------ PATH
	const PATH = 'path';

	//------------------------------------------------------------------------------------------ YAML
	const YAML = 'yaml';

	//----------------------------------------------------------------------------------------- $data
	/**
	 * The yaml data, stored as an array
	 *
	 * @var array
	 */
	private $data;

	//------------------------------------------------------------------------------------- $filename
	/**
	 * The name of the file where the end-user feature is stored.
	 * - null if not set (the getter sets this on first read)
	 * - string if a file was found by fileMatches()
	 * - true if the file is implicit (then a default raw content has been set)
	 * - false if there is no file nor implicit configuration for this path
	 *
	 * @store false
	 * @var string|boolean
	 */
	public $filename;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * Yaml file constructor
	 *
	 * @param $filename string
	 */
	public function __construct($filename = null)
	{
		if (isset($filename)) {
			$this->data     = yaml_parse_file($filename);
			$this->filename = $filename;
		}
	}

	//------------------------------------------------------------------------------------ addFeature
	/**
	 * Adds a low-level feature
	 *
	 * @param $feature string
	 * @param $options array
	 */
	public function addFeature($feature, array $options = [])
	{
		if (!isset($this->data[self::FEATURES])) {
			$this->data[self::FEATURES] = [];
		}
		$features_data =& $this->data[self::FEATURES];
		if (!isset($features_data[$feature]) && !in_array($feature, $features_data)) {
			if ($options) {
				$features_data[$feature] = $options;
			}
			else {
				$features_data[] = $feature;
			}
		}
	}

	//------------------------------------------------------------------------------- defaultFileName
	/**
	 * Reads the content of the default file, stored into defaults/, for a given end-user feature
	 *
	 * @param $feature string An implicit end-user feature name
	 * @return string The content of the yaml file
	 */
	public static function defaultFileName($feature)
	{
		return self::DEFAULTS_DIR . SL . $feature . DOT . self::YAML;
	}

	//------------------------------------------------------------------------------------ extendYaml
	/**
	 * Extends low-level features and includes lists from the yaml structure
	 * If their value are strings, change them to arrays (comma-separated values)
	 *
	 * @example 'includes: edit, output'
	 * will be read as ['includes' => 'edit, output']
	 * extendsYaml will change it to ['includes' => ['edit', 'output']]
	 */
	public function extendYaml()
	{
		foreach ([self::FEATURES, self::INCLUDES] as $key) {
			if (isset($this->data[$key]) && is_string($this->data[$key])) {
				$value = $this->data[$key];
				$this->data[$key] = [];
				foreach (explode(',', $value) as $k => $v) {
					$this->data[$key][$k] = trim($v);
				}
			}
		}
	}

	//----------------------------------------------------------------------------------- fileMatches
	/**
	 * Returns true if the yaml file matches (contains) the feature path
	 *
	 * The file matches the path if it contains a end-user feature without any path (not set),
	 * or if the path of the end-user feature matches $feature->path.
	 *
	 * If a matching end-user feature if found, sets $feature->raw and returns true
	 *
	 * @param $path string The full path of the atomic end-user feature to match
	 * @return boolean
	 */
	public function fileMatches($path)
	{
		$default_path = lLastParse($path, SL);
		foreach ($this->data as $yaml_path => $feature_data) {
			if (is_string($feature_data) && ($yaml_path === self::PATH)) {
				$default_path = str_replace(BS, SL, $feature_data);
			}
			elseif ($yaml_path === self::FEATURES) {
				$this->extendYaml();
				return true;
			}
			elseif (is_array($feature_data)) {
				$yaml_path = str_replace(BS, SL, $yaml_path);
				if (!empty($yaml_path) && (strpos($yaml_path, SL) === false)) {
					$yaml_path = $default_path . SL . $yaml_path;
				}
				if ($yaml_path === $path) {
					$this->data = $feature_data;
					$this->extendYaml();
					return true;
				}
			}
		}
		return false;
	}

	//-------------------------------------------------------------------------------------- fromFile
	/**
	 * Gets all available yaml features data from file
	 *
	 * @param $filename string
	 * @return Yaml[]
	 */
	public static function fromFile($filename)
	{
		$default_path = null;
		$result = [];
		$yaml = new Yaml($filename);
		foreach ($yaml->data as $path => $feature_data) {
			if ($path === self::FEATURES) {
				$yaml->extendYaml();
				$result = [$yaml->getPath() => $yaml];
				break;
			}
			elseif (is_array($feature_data)) {
				$path = str_replace(BS, SL, $path);
				if (!empty($path) && (strpos($path, SL) === false)) {
					$path = lLastParse($yaml->getPath(), SL) . SL . $path;
				}
				$result_yaml = new Yaml();
				$result_yaml->data = $feature_data;
				$result_yaml->filename = $filename;
				$result_yaml->extendYaml();
				$result[$path] = $result_yaml;
			}
		}
		return $result;
	}

	//----------------------------------------------------------------------------------- getFeatures
	/**
	 * Gets the low-level features list stored into the yaml file
	 *
	 * @param $default_path string
	 * @return Low_Level_Feature[]
	 */
	public function getFeatures($default_path)
	{
		$features = [];
		if (isset($this->data[self::FEATURES])) {
			foreach ($this->data[self::FEATURES] as $feature => $feature_detail) {
				if (is_string($feature_detail) && !is_string($feature)) {
					if (!strpos($feature_detail, SL)) {
						$feature_detail = $default_path . SL . $feature_detail;
					}
					$features[$feature_detail] = new Low_Level_Feature($feature_detail);
				}
				elseif (is_string($feature) && is_array($feature_detail)) {
					if (!strpos($feature, SL)) {
						$feature = $default_path . SL . $feature;
					}
					$features[$feature] = new Low_Level_Feature($feature, $feature_detail);
				}
				else {
					user_error(
						'Parse of ' . $this->filename . ' features : feature is not allowed ['
						. print_r($feature, true) . ': ' . print_r($feature_detail, true) . ']',
						E_USER_ERROR
					);
				}
			}
		}
		return $features;
	}

	//------------------------------------------------------------------------------- getFilenamePath
	/**
	 * Returns the full path calculated from the name of the file
	 *
	 * @example
	 * a/full/path/class_name/feature.yaml -> a/full/path/class_name/feature
	 * a/full/path/Class_Name_feature.yaml -> a/full/path/Class_Name/feature
	 * @return string
	 */
	private function getFilenamePath()
	{
		$path       = lLastParse($this->filename, SL);
		$file_parts = explode('_', lParse(rLastParse($this->filename, SL), DOT));
		foreach ($file_parts as $key => $file_part) {
			if (ctype_lower($file_part[0])) {
				$path = str_replace(BS, SL, Names::fileToClass($path))
					. SL . join('_', array_splice($file_parts, $key));
				break;
			}
			$path .= $key ? ('_' . $file_part) : (SL . $file_part);
		}
		return $path;
	}

	//----------------------------------------------------------------------------------- getIncludes
	/**
	 * @param $default_path string
	 * @return Feature[]
	 */
	public function getIncludes($default_path)
	{
		$includes = [];
		if (isset($this->data[self::INCLUDES])) {
			foreach ($this->data[self::INCLUDES] as $feature) {
				if (strpos($feature, SL) === false) {
					$feature = lLastParse($default_path, SL) . SL . $feature;
				}
				$includes[$feature] = new Feature($feature);
			}
		}
		return $includes;
	}

	//--------------------------------------------------------------------------------------- getName
	/**
	 * Gets the atomic end-user feature name stored into the yaml file
	 *
	 * @return string
	 */
	public function getName()
	{
		return isset($this->data[self::NAME]) ? $this->data[self::NAME] : null;
	}

	//--------------------------------------------------------------------------------------- getPath
	/**
	 * Gets the full path of the feature stored into the file, or using the name of the file
	 *
	 * @return string
	 */
	private function getPath()
	{
		if (isset($this->data[self::PATH])) {
			$path = $this->data[self::PATH];
			if (!strpos($path, SL)) {
				$path = lLastParse($this->getFilenamePath(), SL) . SL . $path;
			}
		}
		else {
			$path = $this->getFilenamePath();
		}
		return $path;
	}

}
