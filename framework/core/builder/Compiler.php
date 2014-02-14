<?php
namespace SAF\Framework\Builder;

use SAF\Framework\Application;
use SAF\Framework\Builder;
use SAF\Framework\Class_Builder;
use SAF\Framework\Files;

/**
 * Built classes compiler
 */
class Compiler
{

	//--------------------------------------------------------------------------------- $replacements
	/**
	 * @var string[]
	 */
	private $replacements;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $replacements string[]
	 */
	public function __construct(&$replacements)
	{
		$this->replacements =& $replacements;
	}

	//--------------------------------------------------------------------------------------- compile
	public function compile()
	{
		foreach ($this->replacements as $class_name => $replacement) {
			if (is_array($replacement)) {
				$built_name = null;
				foreach (Class_Builder::build($class_name, $replacement, true) as $built_name => $source) {
					$source = '<?php' . "\n" . $source;

					$path = array_slice(explode('\\', $built_name), 1);
					$file_name = array_pop($path) . '.php';
					$path = strtolower(join('/', $path));
					Files::mkdir($path);

					file_put_contents($path . '/' . $file_name, $source);
				}
				$this->replacements[$class_name] = $built_name;
			}
		}
	}

}
