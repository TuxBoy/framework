<?php
namespace SAF\Framework;

/**
 * Import execution controller
 */
class Default_Import_Execute_Controller implements Default_Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * This will be called for this controller, always.
	 *
	 * @param $parameters Controller_Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Controller_Parameters $parameters, $form, $files, $class_name)
	{
		//Mysql_Logger::getInstance()->continue = true;
		//Mysql_Logger::getInstance()->display_log = true;

		set_time_limit(900);
		$parameters = $parameters->getObjects();
		$import = Import_Builder_Form::build(
			$form, Session::current()->get('SAF\Framework\Session_Files')->files
		);
		$import->class_name = $class_name;
		foreach ($import->worksheets as $worksheet) {
			$array = $worksheet->file->getCsvContent();
			$import_array = new Import_Array($worksheet->settings);
			$import_array->importArray($array);
		}
		return View::run($parameters, $form, $files, $class_name, "importDone");
	}

}