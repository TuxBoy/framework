<?php
namespace ITRocks\Framework\Setting;

use ITRocks\Framework\Builder;
use ITRocks\Framework\Setting;
use ITRocks\Framework\Tools\Set;

/**
 * Set of settings
 *
 * @override elements @link Collection @var Setting[]
 */
class Settings extends Set
{

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $elements Setting[]
	 */
	public function __construct(array $elements = null)
	{
		$settings = [];
		if (isset($elements)) {
			foreach ($elements as $setting) {
				$settings[$setting->code] = $setting;
			}
		}
		parent::__construct(Builder::className('ITRocks\Framework\Setting'), $settings);
	}

	//------------------------------------------------------------------------------------------- add
	/**
	 * Sets a value for a given setting
	 *
	 * @param $code  string|integer|Setting
	 * @param $value string|Setting
	 * @return Setting
	 */
	public function add($code, $value = null)
	{
		if ($code instanceof Setting) {
			parent::add($code->code, $code);
			$setting = $code;
		}
		elseif ($value instanceof Setting) {
			parent::add($code, $value);
			$setting = $value;
		}
		else {
			$setting = $this->get($code);
			if (isset($setting)) {
				$setting->value = $value;
			}
			else {
				parent::add($code, $setting = new Setting($code, $value));
			}
		}
		return $setting;
	}

	//------------------------------------------------------------------------------------------- get
	/**
	 * @param string $code
	 * @return Setting
	 */
	public function get($code)
	{
		/** @var $setting Setting */
		$setting = parent::get($code);
		return $setting;
	}

}
