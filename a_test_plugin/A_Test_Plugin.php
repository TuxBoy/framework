<?php
namespace ITRocks\Framework;

use Exception;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Plugin\Activable;

/**
 * Created by PhpStorm.
 * User: sebastien
 * Date: 21/11/16
 * Time: 11:34
 */
class A_Test_Plugin implements Activable
{

	/**
	 * This method is called each time the class is loaded
	 * = when you need the plugin for the first time during the script execution
	 * @throws Exception
	 */
	public function activate()
	{
		$translation = Loc::tr('yes');
	}
}
