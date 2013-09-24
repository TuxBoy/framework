<?php
namespace SAF\Framework;

/**
 * Import preview controller
 */
class Import_Preview_Controller implements Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param $parameters Controller_Parameters
	 * @param $form       array
	 * @param $files      array
	 * @return mixed
	 */
	public function run(Controller_Parameters $parameters, $form, $files)
	{
		/** @var $import Import */
		$import = $parameters->getMainObject('SAF\Framework\Import');
		$parameters = $parameters->getObjects();
		$form = (new File_Builder_Post_Files())->appendToForm($form, $files);
		foreach ($form as $file) {
			if ($file instanceof File) {
				$excel = Excel_File::fileToArray($file->temporary_file_name);
				$worksheet_number = 0;
				foreach ($excel as $temporary_file_name => $worksheet) {
					$class_name = Namespaces::fullClassName(Import_Array::getClassNameFromArray($worksheet));
					$list_settings = List_Controller::getListSettings($class_name);
					$properties_alias = array();
					foreach ($list_settings->properties_title as $property_path => $property_title) {
						$properties_alias[Names::displayToProperty($property_title)] = $property_path;
					}
					$import->worksheets[] = new Import_Worksheet(
						$worksheet_number ++,
						Import_Settings_Builder::buildArray($worksheet, $properties_alias),
						new File($temporary_file_name),
						new Import_Preview($worksheet)
					);
				}
			}
		}
		return View::run($parameters, $form, $files, 'SAF\Framework\Import', "preview");
	}

}
