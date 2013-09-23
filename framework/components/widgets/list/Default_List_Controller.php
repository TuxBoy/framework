<?php
namespace SAF\Framework;

/**
 * The default list controller is called if no list controller has beed defined for a business object class
 */
class Default_List_Controller extends List_Controller
{

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $class_name string object or class name
	 * @param $parameters string[] parameters
	 * @return Button[]
	 */
	protected function getGeneralButtons($class_name, $parameters)
	{
		return array(
			new Button("Add", View::link($class_name, "new"), "add", Color::of("green")),
			new Button("Import", View::link('SAF\Framework\Import', "form"), "import", Color::of("green"))
		);
	}

	//--------------------------------------------------------------------------- getSelectionButtons
	/**
	 * @param $class_name string
	 * @return Button[]
	 */
	protected function getSelectionButtons($class_name)
	{
		return array(
			new Button("Print", View::link($class_name, "print"), "print", array(
				"sub_buttons" => array(
					new Button(
						"Models",
						View::link(
							'SAF\Framework\Print_Models', "list", Namespaces::shortClassName($class_name)
						),
						"models",
						"#main"
					)
				)
			))
		);
	}

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Controller_Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(Controller_Parameters $parameters, $form, $class_name)
	{
		$parameters = $parameters->getObjects();
		$element_class_name = Namespaces::fullClassName(Set::elementClassNameOf($class_name));
		$list_settings = $this->getListSettings($element_class_name);
		$this->applyParametersToListSettings($list_settings, $parameters, $form);
		// read data
		$parameters = array_merge(
			array(
				$element_class_name => Dao::select(
					$element_class_name,
					$list_settings->properties_path,
					$list_settings->search,
					array($list_settings->sort, Dao::limit(20))
				),
				"search"       => $this->getSearchValues($list_settings),
				"sorted"       => $this->getSortClasses($list_settings),
				"reversed"     => $this->getReverseClasses($list_settings),
				"sort_options" => $this->getSortLinks($list_settings),
				"titles"       => $this->getTitles($list_settings),
				"short_titles" => $this->getShortTitles($list_settings)
			),
			$parameters
		);
		// buttons
		$parameters["general_buttons"]   = $this->getGeneralButtons($element_class_name, $parameters);
		$parameters["selection_buttons"] = $this->getSelectionButtons($element_class_name);
		return $parameters;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for default "list-typed" view controller
	 *
	 * @param $parameters Controller_Parameters
	 * @param $form array
	 * @param $files array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Controller_Parameters $parameters, $form, $files, $class_name)
	{
		$parameters = $this->getViewParameters($parameters, $form, $class_name);
		return View::run($parameters, $form, $files, $class_name, "list");
	}

}
