<?php
namespace SAF\Framework\Widget\Data_List;

use SAF\Framework\Builder;
use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameters;
use SAF\Framework\Dao\Option\Count;
use SAF\Framework\Dao\Option\Limit;
use SAF\Framework\Dao;
use SAF\Framework\Locale;
use SAF\Framework\Locale\Loc;
use SAF\Framework\Print_Model;
use SAF\Framework\Reflection\Reflection_Class;
use SAF\Framework\Reflection\Reflection_Property_Value;
use SAF\Framework\Setting\Custom_Settings_Controller;
use SAF\Framework\Tools\Color;
use SAF\Framework\Tools\Names;
use SAF\Framework\Tools\Namespaces;
use SAF\Framework\Tools\String;
use SAF\Framework\View;
use SAF\Framework\Widget\Button;
use SAF\Framework\Widget\Data_List_Setting\Data_List_Settings;
use SAF\Framework\widget\output\Output_Controller;

/**
 * The default list controller is called if no list controller has beed defined for a business object class
 */
class Data_List_Controller extends Output_Controller
{

	//----------------------------------------------------------------- applyParametersToListSettings
	/**
	 * Apply parameters to list settings
	 *
	 * @param $list_settings Data_List_Settings
	 * @param $parameters    array
	 * @param $form          array
	 * @return Data_List_Settings set if parameters did change
	 */
	public function applyParametersToListSettings(
		Data_List_Settings &$list_settings, $parameters, $form = null
	) {
		if (isset($form)) {
			$parameters = array_merge($parameters, $form);
		}
		$did_change = true;
		if (isset($parameters['add_property'])) {
			$list_settings->addProperty(
				$parameters['add_property'],
				isset($parameters['before']) ? 'before' : 'after',
				isset($parameters['before'])
					? $parameters['before']
					: (isset($parameters['after']) ? $parameters['after'] : '')
			);
		}
		elseif (isset($parameters['less'])) {
			if ($parameters['less'] == 20) {
				$list_settings->maximum_displayed_lines_count = 20;
			}
			else {
				$list_settings->maximum_displayed_lines_count = max(
					20, $list_settings->maximum_displayed_lines_count - $parameters['less']
				);
			}
		}
		elseif (isset($parameters['more'])) {
			$list_settings->maximum_displayed_lines_count = round(min(
						1000, $list_settings->maximum_displayed_lines_count + $parameters['more']
					) / 100) * 100;
		}
		elseif (isset($parameters['move'])) {
			if ($parameters['move'] == 'down') {
				$list_settings->start_display_line_number += $list_settings->maximum_displayed_lines_count;
			}
			elseif ($parameters['move'] == 'up') {
				$list_settings->start_display_line_number -= $list_settings->maximum_displayed_lines_count;
			}
			elseif (is_numeric($parameters['move'])) {
				$list_settings->start_display_line_number = $parameters['move'];
			}
		}
		elseif (isset($parameters['remove_property'])) {
			$list_settings->removeProperty($parameters['remove_property']);
		}
		elseif (isset($parameters['property_path'])) {
			if (isset($parameters['property_title'])) {
				$list_settings->propertyTitle($parameters['property_path'], $parameters['property_title']);
			}
		}
		elseif (isset($parameters['reverse'])) {
			$list_settings->reverse($parameters['reverse']);
		}
		elseif (isset($parameters['search'])) {
			$list_settings->search(self::descapeForm($parameters['search']));
		}
		elseif (isset($parameters['sort'])) {
			$list_settings->sort($parameters['sort']);
		}
		elseif (isset($parameters['title'])) {
			$list_settings->title = $parameters['title'];
		}
		else {
			$did_change = false;
		}
		if ($list_settings->start_display_line_number < 1) {
			$list_settings->start_display_line_number = 1;
			$did_change = true;
		}
		if (Custom_Settings_Controller::applyParametersToCustomSettings($list_settings, $parameters)) {
			$did_change = true;
		}
		if ($did_change) {
			$list_settings->save();
		}
		return $did_change ? $list_settings : null;
	}

	//------------------------------------------------------------------------- applySearchParameters
	/**
	 * @param $list_settings Data_List_Settings
	 */
	protected function applySearchParameters(Data_List_Settings $list_settings)
	{
		$search_parameters_parser = Builder::create(
			Search_Parameters_Parser::class,
			[$list_settings->class_name, $list_settings->search]
		);
		return $search_parameters_parser->parse();
	}

	//----------------------------------------------------------------------------------- descapeForm
	/**
	 * @param $form string[]
	 * @return string[]
	 */
	protected function descapeForm($form)
	{
		$result = [];
		foreach ($form as $property_name => $value) {
			$property_name = self::descapePropertyName($property_name);
			$result[$property_name] = $value;
		}
		return $result;
	}

	//--------------------------------------------------------------------------- descapePropertyName
	/**
	 * @param $property_name string
	 * @return string
	 */
	protected function descapePropertyName($property_name)
	{
		$property_name = str_replace(['.id_', '>id_', '>'], DOT, $property_name);
		if (substr($property_name, 0, 3) == 'id_') {
			$property_name = substr($property_name, 3);
		}
		return $property_name;
	}

	//----------------------------------------------------------------------------- getReverseClasses
	/**
	 * @param $list_settings Data_List_Settings
	 * @return string[] key is column number and property path
	 */
	protected function getReverseClasses(Data_List_Settings $list_settings)
	{
		$reverse_classes = [];
		foreach ($list_settings->sort->reverse as $property_path) {
			$reverse_classes[$property_path] = 'reverse';
			$key = array_search($property_path, $list_settings->properties_path);
			if ($key !== false) {
				$reverse_classes[$key] = 'reverse';
			}
		}
		return $reverse_classes;
	}

	//------------------------------------------------------------------------------ getSearchSummary
	/**
	 * @param $list_settings Data_List_Settings
	 * @return string
	 */
	public function getSearchSummary(Data_List_Settings $list_settings)
	{
		if ($list_settings->search) {
			if (Locale::current()) {
				$t = '|';
				$i = '¦';
			}
			else {
				$t = $i = '';
			}
			$class_display = Names::classToDisplay(
				(new Reflection_Class($list_settings->class_name))->getAnnotation('set')->value
			);
			$summary = $t . $i. ucfirst($class_display) . $i . ' filtered by' . $t;
			$first = true;
			foreach ($list_settings->search as $property_path => $value) {
				if ($first) $first = false; else $summary .= ',';
				$summary .= SP . $t . $property_path . $t . ' = ' . DQ . $value . DQ;
			}
			return $summary;
		}
		return null;
	}

	//------------------------------------------------------------------------------- getSearchValues
	/**
	 * @param $list_settings Data_List_Settings
	 * @return Reflection_Property_Value[] key is the property path
	 */
	public function getSearchValues(Data_List_Settings $list_settings)
	{
		$search = array_combine($list_settings->properties_path, $list_settings->properties_path);
		foreach ($list_settings->search as $property_path => $search_value) {
			if (isset($search[$property_path])) {
				$property = new Reflection_Property_Value(
					$list_settings->class_name, $property_path, $search_value, true
				);
				if ($property->getType()->isClass()) {
					$property->value(Dao::read($search_value, $property->getType()->asString()));
				}
				else {
					$property->value($search_value);
				}
				$search[$property_path] = $property;
			}
		}
		return $search;
	}

	//--------------------------------------------------------------------------- getSelectionButtons
	/**
	 * @param $class_name string
	 * @return Button[]
	 */
	protected function getSelectionButtons(
		/** @noinspection PhpUnusedParameterInspection needed for plugins or overriding */
		$class_name
	) {
		return [
			new Button('Print', View::link($class_name, 'print'), 'print', [
				'sub_buttons' => [
					new Button(
						'Models',
						View::link(
							Names::classToSet(Print_Model::class),
							Feature::F_LIST,
							Namespaces::shortClassName($class_name)
						),
						'models',
						'#main'
					)
				]
			])
		];
	}

	//-------------------------------------------------------------------------------- getSortClasses
	/**
	 * @param $list_settings Data_List_Settings
	 * @return string[] key is column number and property path, value is position of the sort property
	 *         (0..n)
	 */
	protected function getSortClasses(Data_List_Settings $list_settings)
	{
		$sort_classes = [];
		$sort_count = 0;
		foreach ($list_settings->sort->getColumns() as $property_path) {
			$sort_classes[$property_path] = ++$sort_count;
			$key = array_search($property_path, $list_settings->properties_path);
			if ($key !== false) {
				$sort_classes[$key] = $sort_count;
			}
		}
		return $sort_classes;
	}

	//---------------------------------------------------------------------------------- getSortLinks
	/**
	 * @param $list_settings Data_List_Settings
	 * @return string[] key is column number and property path, value is 'sort' or 'reverse'
	 */
	protected function getSortLinks(Data_List_Settings $list_settings)
	{
		$sort_links = [];
		foreach ($list_settings->properties_path as $property_path) {
			$sort_links[$property_path] = 'sort';
			$key = array_search($property_path, $list_settings->properties_path);
			if ($key !== false) {
				$sort_links[$key] = 'sort';
			}
		}
		$sort = $list_settings->sort->getColumns();
		if ($sort) {
			$sort_links[reset($sort)] = 'reverse';
			$key = array_search(reset($sort), $list_settings->properties_path);
			if ($key !== false) {
				$sort_links[$key] = 'reverse';
			}
		}
		return $sort_links;
	}

	//-------------------------------------------------------------------------------- getShortTitles
	/**
	 * @param $list_settings Data_List_Settings
	 * @return string[] key is property path, value is short title
	 */
	protected function getShortTitles(Data_List_Settings $list_settings)
	{
		$short_titles = [];
		foreach ($this->getTitles($list_settings) as $property_path => $title) {
			$short_titles[$property_path] = (new String($title))->twoLast();
			$key = array_search($property_path, $list_settings->properties_path);
			if ($key !== false) {
				$sort_titles[$key] = $short_titles[$property_path];
			}
		}
		return $short_titles;
	}

	//------------------------------------------------------------------------------------- getTitles
	/**
	 * @param $list_settings Data_List_Settings
	 * @return string[] key is property path, value is title
	 */
	protected function getTitles(Data_List_Settings $list_settings)
	{
		$locale = Locale::current();
		$titles = [];
		foreach ($list_settings->properties_path as $property_path) {
			$titles[$property_path] = isset($list_settings->properties_title[$property_path])
				? $list_settings->properties_title[$property_path]
				: (isset($locale) ? Loc::tr($property_path) : $property_path);
			$key = array_search($property_path, $list_settings->properties_path);
			if ($key !== false) {
				$titles[$key] = $titles[$property_path];
			}
		}
		return $titles;
	}

	//----------------------------------------------------------------------------- getGeneralButtons
	/**
	 * @param $class_name string object or class name
	 * @param $parameters string[] parameters
	 * @return Button[]
	 */
	protected function getGeneralButtons($class_name, $parameters)
	{
		return [
			'add' => new Button(
				'Add', View::link($class_name, Feature::F_ADD), 'add',
				['#main', new Color('green')]
			),
			'import' => new Button(
				'Import', View::link($class_name, 'import'), 'import',
				['#main', new Color('green')]
			),
			'save' => new Button(
				'Save', View::link($class_name, Feature::F_LIST), 'custom_save',
				['#main', new Color('green'), '.submit', 'title' => 'save this view as a custom list']
			),
			'delete' => new Button(
				'Delete', View::link($class_name, Feature::F_LIST, null, ['delete_name' => true]),
				'custom_delete',
				['#main', new Color('red'), '.submit', 'title' => 'delete this custom list']
			)
		];
	}

	//----------------------------------------------------------------------------- getViewParameters
	/**
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $class_name string
	 * @return mixed[]
	 */
	protected function getViewParameters(Parameters $parameters, $form, $class_name)
	{
		$class_name = $parameters->getMainObject()->element_class_name;
		$parameters = $parameters->getObjects();
		$list_settings = Data_List_Settings::current($class_name);
		$list_settings->cleanup();
		$this->applyParametersToListSettings($list_settings, $parameters, $form);
		$search = $this->applySearchParameters($list_settings);
		$customized_list_settings = $list_settings->getCustomSettings();
		$count = new Count();
		$limit = new Limit(
			$list_settings->start_display_line_number,
			$list_settings->maximum_displayed_lines_count
		);
		$data = Dao::select(
			$class_name,
			$list_settings->properties_path,
			$search,
			[$list_settings->sort, $limit, $count]
		);
		if (($data->length() < $limit->count) && ($limit->from > 1)) {
			$limit->from = max(1, $count->count - $limit->count + 1);
			$list_settings->start_display_line_number = $limit->from;
			$list_settings->save();
			$data = Dao::select(
				$class_name,
				$list_settings->properties_path,
				$search,
				[$list_settings->sort, $limit, $count]
			);
		}
		$displayed_lines_count = min($data->length(), $list_settings->maximum_displayed_lines_count);
		$less_twenty = $displayed_lines_count > 20;
		$more_hundred = ($displayed_lines_count < 1000) && ($displayed_lines_count < $count->count);
		$more_thousand = ($displayed_lines_count < 1000) && ($displayed_lines_count < $count->count);
		$parameters = array_merge(
			[$class_name => $data],
			$parameters,
			[
				'customized_lists'      => $customized_list_settings,
				'displayed_lines_count' => $displayed_lines_count,
				'less_twenty'           => $less_twenty,
				'more_hundred'          => $more_hundred,
				'more_thousand'         => $more_thousand,
				'reversed'              => $this->getReverseClasses($list_settings),
				'rows_count'            => $count->count,
				'search'                => $this->getSearchValues($list_settings),
				'search_summary'        => $this->getSearchSummary($list_settings),
				'settings'              => $list_settings,
				'short_titles'          => $this->getShortTitles($list_settings),
				'sort_options'          => $this->getSortLinks($list_settings),
				'sorted'                => $this->getSortClasses($list_settings),
				'display_start'         => $list_settings->start_display_line_number,
				'title'                 => $list_settings->title(),
				'titles'                => $this->getTitles($list_settings)
			]
		);
		// buttons
		$parameters['general_buttons']   = $this->getGeneralButtons($class_name, $parameters);
		$parameters['selection_buttons'] = $this->getSelectionButtons($class_name);
		if (!isset($customized_list_settings[$list_settings->name])) {
			unset($parameters['general_buttons']['delete']);
		}
		return $parameters;
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Default run method for default 'list-typed' view controller
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array
	 * @param $class_name string
	 * @return mixed
	 */
	public function run(Parameters $parameters, $form, $files, $class_name)
	{
		$parameters = $this->getViewParameters($parameters, $form, $class_name);
		return View::run($parameters, $form, $files, $class_name, Feature::F_LIST);
	}

}