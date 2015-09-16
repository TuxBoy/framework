<?php
namespace SAF\Framework\Setting;

use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Target;
use SAF\Framework\Tools\Names;
use SAF\Framework\View;
use SAF\Framework\Widget\Button;

/**
 * Custom settings buttons
 */
class Buttons
{

	//------------------------------------------------------------------------------------ getButtons
	/**
	 * @param $object_class object|string
	 * @param $feature_name string
	 * @param $caption      string custom element caption (eg 'custom list' or 'custom form')
	 * @param $target       string target component name
	 * @return Button[]
	 */
	public function getButtons($caption, $object_class, $feature_name = null, $target = Target::MAIN)
	{
		$link = $feature_name ? View::link($object_class, $feature_name) : View::link($object_class);
		return [
			Feature::F_WRITE => new Button(
				'Save',
				$link,
				'custom_save',
				[$target, '.submit', 'hint' => "Save this view as a $caption"]
			),
		];
	}

}
