<?php

namespace ITRocks\Framework\Asynchronous\Request;

use ITRocks\Framework\Controller\Default_Feature_Controller;
use ITRocks\Framework\Controller\Parameters;
use ITRocks\Framework\Dao;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Tools\Date_Time;
use ITRocks\Framework\Tools\Names;
use ITRocks\Framework\View;

/**
 */
class List_Controller implements Default_Feature_Controller
{

	//------------------------------------------------------------------------------------------- run
	/**
	 * @param Parameters $parameters
	 * @param array      $form
	 * @param array      $files
	 * @param string     $class_name
	 * @return mixed
	 */
	public function run(Parameters $parameters, array $form, array $files, $class_name)
	{
		$sort = Dao::sort([Dao::reverse('creation'), Dao::reverse('id')]);
		$month_limit = (new Date_Time())->toMonth()->add(-1, Date_Time::MONTH);
		$elements = Dao::search(
			['creation' => Dao\Func::greaterOrEqual($month_limit)],
			$class_name,
			[$sort, Dao::limit(15)]
		);
		$parameters->set('elements', $elements);
		$parameters->set('title', Loc::tr(ucfirst(Names::classToDisplay($class_name))));
		$parameters->set('uri', View::link($class_name, 'list'));
		$parameters->getMainObject();
		$parameters = $parameters->getRawParameters();
		return View::run($parameters, $form, $files, $class_name, 'list');
	}

}
