<?php
namespace ITRocks\Framework\History;

use ITRocks\Framework\Controller\Parameter;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Dao;
use ITRocks\Framework\History;
use ITRocks\Framework\Html\Parser;
use ITRocks\Framework\Reflection\Annotation\Property\User_Annotation;
use ITRocks\Framework\Reflection\Reflection_Property;
use ITRocks\Framework\View;

/**
 * History output specific properties and methods
 */
trait History_Output
{

	//----------------------------------------------------------------------------- cleanPropertyName
	/**
	 * Remove index parts of property name
	 *
	 * @param $property_name string
	 * @return string
	 */
	public static function cleanPropertyName($property_name)
	{
		return preg_replace('/\[[0-9]*\]/', '', $property_name);
	}

	//---------------------------------------------------------------------------------------- output
	/**
	 * @param $result     string
	 * @param $parameters Parameters
	 * @param $class_name string the main object class name
	 */
	public function output(&$result, Parameters $parameters, $class_name)
	{
		$object = $parameters->getMainObject();
		$parameters->set(Parameter::IS_INCLUDED, true);
		$parameters->set('history_tree', $this->historyTreeByUserDate($object, self::getHistoryClassName($class_name)));
		$after = View::run($parameters->getObjects(), [], [], $class_name, 'history');
		if (strpos($result, 'id="main"')) {
			$parser = new Parser($result);
			$parser->appendTo('section#main', $after);
			$result = $parser->buffer;
		}
		else {
			$result .= $after;
		}
	}

	//------------------------------------------------------------------------- historyTreeByUserDate
	/**
	 * Returns repair history, in a ready-for-display ordered and optimized tree
	 * This is used for user display :
	 * - properties that have @user hidden or @user invisible will be removed from this tree
	 *
	 * @param $object     object the main object on which we have history
	 * @param $class_name string the history entries class name
	 * @return array [$id_user] => ['history' => [$date => [History]], 'user' => $user]
	 */
	private function historyTreeByUserDate($object, $class_name)
	{
		$object_class_name = get_class($object);
		$object_property_name = call_user_func([$class_name, 'getObjectPropertyName']);
		/** @noinspection PhpUndefinedMethodInspection */
		$history_entries = Dao::search(
			[$object_property_name => Dao::getObjectIdentifier($object)],
			$class_name
		);

		/** @noinspection PhpUsageOfSilenceOperatorInspection
		 * Patch to avoid the 'Array was modified by the user comparison function' warning */
		@usort($history_entries, function(History $a, History $b) {
			$date_a = substr($a->date->toISO(), 0, 16);
			$date_b = substr($b->date->toISO(), 0, 16);
			$compare = ($date_a === $date_b)
				? (Dao::getObjectIdentifier($a) - Dao::getObjectIdentifier($b))
				: $a->date->diff($b->date)->compare();
			return $compare;
		});

		// remove undesired entries
		$history_entries = array_filter($history_entries,
			function (History $history) use ($object_class_name) {
				$property_name = $this->cleanPropertyName($history->property_name);
				// not to be historized ? bypass this entry
				if (!Manager::isToBeHistorized($object_class_name, $property_name)) {
					return false;
				}
				$property = new Reflection_Property($object_class_name, $property_name);
				$user_annotation = $property
					? $property->getListAnnotation(User_Annotation::ANNOTATION) : null;
				// hidden or invisible ? bypass this entry
				if ($user_annotation
					&& (
						$user_annotation->has(User_Annotation::HIDDEN)
						|| $user_annotation->has(User_Annotation::INVISIBLE)
					)
				) {
					return false;
				}
				// keep this entry
				return true;
			}
		);

		// build sorted tree
		$history_tree = [];
		$key = -1;
		$user = null;
		array_walk($history_entries, function (History $history) use (&$history_tree, &$key, &$user) {
			if (!Dao::is($history->user, $user)) {
				$key++;
				$user = $history->user;
				$history_tree[$key] = ['user' => $user];
			}
			$date_time = substr($history->date->toISO(), 0, 16);
			$history_tree[$key]['history'][$date_time][] = $history;
		});

		return $history_tree;
	}

}
