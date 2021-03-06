<?php
namespace ITRocks\Framework\Widget\Button;

use ITRocks\Framework\Controller\Feature;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Controller\Target;
use ITRocks\Framework\Html\Parser;
use ITRocks\Framework\View;
use ITRocks\Framework\Widget\Button;
use ITRocks\Framework\Widget\Edit;

/**
 * Action insert controller
 */
class Edit_Controller extends Edit\Edit_Controller
{

	//----------------------------------------------------------------------------- getPropertiesList
	/**
	 * @param $class_name string
	 * @return string[] property names list
	 */
	protected function getPropertiesList($class_name)
	{
		return ['caption', 'class', 'feature', 'target', 'hint', 'conditions', 'code'];
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
		$parameters = parent::getViewParameters($parameters, $form, $class_name);
		$parameters['custom_class_name'] = $parameters[0];
		$parameters['custom_feature']    = $parameters[1];
		$classes = array_flip(explode(DOT, $parameters[2]));
		if (isset($classes['rad'])) {
			unset($classes['rad']);
		}
		$side = 'after';
		foreach (
			['bottom' => 'after', 'left' => 'before', 'right' => 'after', 'top' => 'left']
			as $position => $position_side
		) {
			if (isset($classes["insert-$position"])) {
				$side = $position_side;
				unset($classes["insert-$position"]);
			}
		}
		$parameters['custom_side'] = $side;
		$parameters["custom_{$side}_button"] = key($classes);
		return $parameters;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Add hidden fields needed to add buttons to the standard form output
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array[]
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, array $form, array $files, $class_name)
	{
		/** @var $button Button */
		$button = $parameters->getMainObject($class_name);
		if (!$button->class && !$button->feature) {
			$button->class   = 'submit';
			$button->feature = $parameters->getRawParameter(1);
			if ($button->feature == Feature::F_EDIT) {
				$button->feature = Feature::F_WRITE;
				$button->target  = Target::MESSAGES;
			}
		}

		$parameters = $this->getViewParameters($parameters, $form, $class_name);
		$edit = View::run($parameters, $form, $files, $class_name, Feature::F_OUTPUT);
		$include = View::run($parameters, $form, $files, $class_name, 'edit_more');
		$parser = new Parser($edit);
		$parser->merge('form', trim($include));

		return $parser->buffer;
	}

}
