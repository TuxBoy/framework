<?php
namespace ITRocks\Framework\Webservice\Json;

use ITRocks\Framework\Builder;
use ITRocks\Framework\Controller\Default_Feature_Controller;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Dao;
use ITRocks\Framework\Dao\Func\Logical;
use ITRocks\Framework\Dao\Option;
use ITRocks\Framework\Dao\Option\Limit;
use ITRocks\Framework\Mapper\Map;
use ITRocks\Framework\Reflection\Reflection_Class;
use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\Tools\Search_Array_Builder;

/**
 * A default json controller to output any object or objects collection into json format
 */
class Json_Controller implements Default_Feature_Controller
{

	//-------------------------------------------------------------------------- applyFiltersToSearch
	/**
	 * @param $search  array|object
	 * @param $filters string[] list of filters to apply
	 */
	protected function applyFiltersToSearch(&$search, array $filters)
	{
		if (!(is_object($search) && $search->isAnd())) {
			$search = Dao\Func::andOp($search ? [$search] : []);
		}
		foreach ($filters as $filter_name => $filter_value) {
			$search->arguments[$filter_name] = ($filter_value[0] == '!')
				? Dao\Func::notEqual(substr($filter_value, 1))
				: $filter_value;
		}
		if (count($search->arguments) == 1) {
			reset($search->arguments);
			$search = [key($search->arguments) => current($search->arguments)];
		}
	}

	//------------------------------------------------------------------------------------- buildJson
	/**
	 * @param $objects object[]|object
	 * @return string
	 */
	protected function buildJson($objects)
	{
		if (is_array($objects)) {
			$entries = [];
			foreach($objects as $source_object) {
				$entries[] = new Autocomplete_Entry(
					Dao::getObjectIdentifier($source_object), strval($source_object)
				);
			}
		}
		else {
			$entries = new Autocomplete_Entry(
				Dao::getObjectIdentifier($objects), strval($objects)
			);
		}
		return json_encode($entries);
	}

	//------------------------------------------------------------------------------------------- run
	/**
	 * Run the default json controller
	 *
	 * @param $parameters Parameters
	 * @param $form       array
	 * @param $files      array[]
	 * @param $class_name string
	 * @return string
	 */
	public function run(Parameters $parameters, array $form, array $files, $class_name)
	{
		$parameters = $parameters->getObjects();
		// read all objects corresponding to class name
		if (!$parameters) {
			return json_encode(Dao::readAll(Names::setToClass($class_name, false), Dao::sort()));
		}
		// read object
		$first_parameter = reset($parameters);
		if (is_object($first_parameter)) {
			return json_encode($first_parameter);
		}
		// search objects for autocomplete combo pull-down list
		if (isset($parameters['term'])) {
			return $this->searchObjectsForAutoCompleteCombo($class_name, $parameters);
		}
		// single object for autocomplete pull-down list value
		elseif (isset($parameters['id'])) {
			$element_class_name = Names::setToClass($class_name);
			$source_object      = Dao::read($parameters['id'], $element_class_name);
			return $this->buildJson($source_object);
		}
		return '';
	}

	//---------------------------------------------------------------------------------------- search
	/**
	 * Optimized search : if OR, it's best launching multiple fast searches than one slow one
	 *
	 * @param $what       object|array source object for filter, only set properties will be used for
	 *                    search
	 * @param $class_name string must be set if is $what is a filter array instead of a filter object
	 * @param $options    Option[] some options for advanced search
	 * @return object[] a collection of read objects
	 */
	private function search($what, $class_name, array $options)
	{
		$options[] = Dao::sort();
		if (
			($what instanceof Logical)
			&& ($what->operator == Logical::OR_OPERATOR)
			&& (count($what->arguments) > 1)
		) {
			$objects = new Map();
			foreach ($what->arguments as $argument_key => $argument) {
				$objects->add(Dao::search([$argument_key => $argument], $class_name, $options));
			}
			$objects = $objects->sort();
			foreach ($options as $option) {
				if (($option instanceof Limit) && (count($objects) > $option->count)) {
					$objects = array_slice($objects, $option->from, $option->count, true);
				}
			}
		}
		else {
			$objects = Dao::search($what, $class_name, $options);
		}
		return $objects;
	}

	//------------------------------------------------------------- searchObjectsForAutoCompleteCombo
	/**
	 * @param $set_name   string
	 * @param $parameters array
	 * @return string
	 */
	protected function searchObjectsForAutoCompleteCombo($set_name, array $parameters)
	{
		$element_class_name = Names::setToClass($set_name, false);
		$search             = null;
		if (!empty($parameters['term'])) {
			$search = (new Search_Array_Builder)->buildMultiple(
				new Reflection_Class($element_class_name), $parameters['term'], '', '%'
			);
		}
		if (isset($parameters['filters']) && $parameters['filters']) {
			$this->applyFiltersToSearch($search, $parameters['filters']);
		}
		// first object only
		if (isset($parameters['first']) && $parameters['first']) {
			$objects       = $this->search($search, $element_class_name, [Dao::limit(1)]);
			$source_object = $objects ? reset($objects) : Builder::create($element_class_name);
			return $this->buildJson($source_object);
		}
		// all results from search
		else {
			$search_options = [];
			if (isset($parameters['limit'])) {
				$search_options[] = Dao::limit($parameters['limit']);
			}
			$objects = $this->search($search, $element_class_name, $search_options);
			return $this->buildJson($objects);
		}
	}

}
