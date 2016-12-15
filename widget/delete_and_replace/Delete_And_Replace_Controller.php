<?php
namespace ITRocks\Framework\Widget\Delete_And_Replace;

use ITRocks\Framework\Controller\Default_Feature_Controller;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Dao;
use ITRocks\Framework\View;
use ITRocks\Framework\Widget\Delete_And_Replace;

/**
 * Default delete-and-replace controller
 */
class Delete_And_Replace_Controller implements Default_Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array[]
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, array $form, array $files, $class_name)
	{
		$replaced = $parameters->getMainObject();
		$objects = $parameters->getObjects();
		if ($id_replace_with = $parameters->getRawParameter('id_replace_with')) {
			$objects['replace_with'] = $replacement = Dao::read($id_replace_with, $class_name);
			Dao::begin();
			if ((new Delete_And_Replace())->deleteAndReplace($replaced, $replacement)) {
				Dao::commit();
				$objects['done'] = true;
			}
			else {
				Dao::rollback();
				$objects['error'] = true;
			}
		}
		return View::run($objects, $form, $files, $class_name, 'deleteAndReplace');
	}

}
