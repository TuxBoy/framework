<?php
namespace ITRocks\Framework\History;

use ITRocks\Framework\Controller\Parameter;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Dao;
use ITRocks\Framework\History;
use ITRocks\Framework\Html\Parser;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Reflection\Annotation\Property\User_Annotation;
use ITRocks\Framework\Reflection\Reflection_Property;
use ITRocks\Framework\Tools\Date_Time;
use ITRocks\Framework\View;

/**
 * History output specific properties and methods
 */
trait History_Output
{

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
		$property_name = call_user_func([$class_name, 'getObjectPropertyName']);
		/** @noinspection PhpUndefinedMethodInspection */
		$history_entries = Dao::search(
			[$property_name => Dao::getObjectIdentifier($object)],
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
		$history_tree = [];
		$key = -1;
		$user = null;
		foreach ($history_entries as $history_entry) {
			if (!Manager::isToBeHistorized($object_class_name, $history_entry->property_name)) {
				continue;
			}
			$property = new Reflection_Property($object_class_name, $history_entry->property_name);
			$user_annotation = $property
				? $property->getListAnnotation(User_Annotation::ANNOTATION) : null;
			if (!$user_annotation || (
				!$user_annotation->has(User_Annotation::HIDDEN)
				&& !$user_annotation->has(User_Annotation::INVISIBLE)
			)) {
				$this->translateHistoryEntry($property, $history_entry);
				if (!Dao::is($history_entry->user, $user)) {
					$key++;
					$user = $history_entry->user;
					$history_tree[$key] = ['user' => $user];
				}
				$date_time = substr($history_entry->date->toISO(), 0, 16);
				$history_tree[$key]['history'][$date_time][] = $history_entry;
			}
		}
		return $history_tree;
	}

	//------------------------------------------------------------------------- translateHistoryEntry
	/**
	 * @param $property Reflection_Property
	 * @param $history  History the history entry
	 */
	private function translateHistoryEntry($property, History $history)
	{
		if ($property->getType()->isDateTime()) {
			$history->new_value = $history->new_value ? new Date_Time($history->new_value) : null;
			$history->old_value = $history->old_value ? new Date_Time($history->old_value) : null;
		}
		if ($history->new_value) {
			$history->new_value = Loc::propertyToLocale($property, $history->new_value);
		}
		if ($history->old_value) {
			$history->old_value = Loc::propertyToLocale($property, $history->old_value);
		}
	}

}
