<?php
namespace ITRocks\Framework\Widget\Button;

use ITRocks\Framework\Builder;
use ITRocks\Framework\Controller\Feature;
use ITRocks\Framework\Controller\Feature_Controller;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Controller\Uri;
use ITRocks\Framework\Mapper\Object_Builder_Array;
use ITRocks\Framework\View;
use ITRocks\Framework\Widget\Button;
use ITRocks\Framework\Widget\Output_Setting\Output_Setting_Controller;

/**
 * Button write controller
 */
class Write_Controller implements Feature_Controller
{

	//------------------------------------------------------------------- callOutputSettingController
	/**
	 * @param $button Button
	 * @param $form   string[]
	 */
	public function callOutputSettingController(Button $button, array $form)
	{
		/** @var $parameters Parameters */
		$parameters = Builder::create(Parameters::class, [
			new Uri(str_replace(BS, SL, $form['custom_class_name']) . SL . $form['custom_feature'])
		]);
		$parameters->set('add_action', $button);
		if (isset($form['custom_after_button'])) {
			$parameters->set('after', $form['custom_after_button']);
		}
		elseif (isset($form['custom_before_button'])) {
			$parameters->set('before', $form['custom_before_button']);
		}
		$parameters->set(Feature::FEATURE, $form['custom_feature']);
		/** @var $output_setting_controller Output_Setting_Controller */
		$output_setting_controller = Builder::create(Output_Setting_Controller::class);
		$output_setting_controller->run($parameters, [], [], $form['custom_class_name']);
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Adds an action button after/before another action button
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array[]
	 * @return mixed
	 */
	public function run(Parameters $parameters, array $form, array $files)
	{
		/** @var $button Button */
		$button = $parameters->getMainObject(Button::class);
		/** @var $builder Object_Builder_Array */
		$builder = Builder::create(Object_Builder_Array::class);
		$builder->ignore_unknown_properties = true;
		$button = $builder->build($form, $button);
		$this->callOutputSettingController($button, $form);
		return View::run($parameters->getObjects(), $form, $files, get_class($button), 'added');
	}

}
