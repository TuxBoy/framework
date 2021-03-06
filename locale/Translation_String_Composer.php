<?php
namespace ITRocks\Framework\Locale;

use ITRocks\Framework\AOP\Joinpoint\Around_Method;
use ITRocks\Framework\Plugin\Register;
use ITRocks\Framework\Plugin\Registerable;

/**
 * Compose translations with dynamic elements with separated translations
 *
 * Enables to prefer multiple translations of single words instead of big sentences translations
 * Enables translations to sort words in another order than original language
 *
 * @example
 * 'A text' is a simple translation string, directly translated without particular work
 * '¦Sales orders¦ list' will be dynamically translated : first 'Sales orders', then '$1 list'
 * '¦Sales¦ ¦orders¦ list' will translate 'Sales' then 'orders' then '$1 $2 list'
 */
class Translation_String_Composer implements Registerable
{

	//----------------------------------------------------------------------------------- onTranslate
	/**
	 * @param $object    Translator
	 * @param $text      string
	 * @param $context   string
	 * @param $joinpoint Around_Method
	 * @return string
	 */
	public function onTranslate(
		Translator $object, $text, $context, Around_Method $joinpoint
	) {
		$context = isset($context) ? $context : '';
		if (strpos($text, '¦') !== false) {
			$translations = $object;
			$elements = [];
			$number = 0;
			$i = 0;
			while (($i = strpos($text, '¦', $i)) !== false) {
				$i += 2;
				$j = strpos($text, '¦', $i);
				if ($j >= $i) {
					$number ++;
					$elements['$' . $number] = $translations->translate(substr($text, $i, $j - $i), $context);
					$text = substr($text, 0, $i - 2) . '$' . $number . substr($text, $j + 2);
					$i += strlen($number) - 1;
				}
			}
			$i = 0;
			while (($i = strpos($text, '!', $i)) !== false) {
				$i++;
				$j = strpos($text, '!', $i);
				if (($j > $i) && (strpos(SP . TAB . CR . LF, $text[$i]) === false)) {
					$number ++;
					$elements['$' . $number] = substr($text, $i, $j - $i);
					$text = substr($text, 0, $i - 1) . '$' . $number . substr($text, $j + 1);
				}
			}
			$translation = str_replace(
				array_keys($elements), $elements, $translations->translate($text, $context)
			);
			return $translation;
		}
		else {
			return $joinpoint->process($text, $context);
		}
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * @param $register Register
	 */
	public function register(Register $register)
	{
		$register->aop->aroundMethod([Translator::class, 'translate'], [$this, 'onTranslate']);
	}

}
