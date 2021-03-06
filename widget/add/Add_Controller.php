<?php
namespace ITRocks\Framework\Widget\Add;

use ITRocks\Framework\Controller\Feature;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Controller\Target;
use ITRocks\Framework\Reflection\Reflection_Class;
use ITRocks\Framework\Setting\Custom_Settings;
use ITRocks\Framework\Tools\Color;
use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\View;
use ITRocks\Framework\Widget\Button;
use ITRocks\Framework\Widget\Edit\Edit_Controller;
use ITRocks\Framework\Widget\Output_Setting\Output_Settings;

/**
 * The default new controller is the same as an edit controller, that accepts no object
 */
class Add_Controller extends Edit_Controller
{

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $object     object|string object or class name
	 * @param $parameters array parameters
	 * @param $settings   Custom_Settings|Output_Settings
	 * @return Button[]
	 */
	public function getGeneralButtons($object, array $parameters, Custom_Settings $settings = null)
	{
		$buttons = parent::getGeneralButtons($object, $parameters, $settings);

		$close_link = View::link(Names::classToSet(get_class($object)));
		list($close_link) = $this->prepareThen($object, $parameters, $close_link);

		return array_merge($buttons, [
			Feature::F_CLOSE => new Button(
				'Close', $close_link, Feature::F_CLOSE, [new Color('close'), Target::MAIN]
			),
		]);
	}

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(Parameters $parameters, array $form, $class_name)
	{
		$object = $parameters->getMainObject($class_name);
		foreach ((new Reflection_Class($class_name))->accessProperties() as $property) {
			$property->setValue($object, $property->getDefaultValue());
		}
		$objects = $parameters->getObjects();
		if (count($objects) > 1) {
			foreach (array_slice($objects, 1) as $property_name => $value) {
				$object->$property_name = $value;
			}
		}
		return parent::getViewParameters($parameters, $form, $class_name);
	}

}
