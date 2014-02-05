<?php
namespace SAF\AOP;

use SAF\Framework\Reflection_Function;
use SAF\Framework\Reflection_Method;
use SAF\Plugins;

/**
 * Aspect weaver method compiler
 */
class Method_Compiler
{

	const DEBUG = false;

	//--------------------------------------------------------------------------------------- $buffer
	/**
	 * @var string
	 */
	private $buffer;

	//----------------------------------------------------------------------------------- $class_name
	/**
	 * @var string
	 */
	private $class_name;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class_name string
	 * @param $buffer     string
	 */
	public function __construct($class_name, &$buffer)
	{
		$this->buffer =& $buffer;
		$this->class_name = $class_name;
	}

	//---------------------------------------------------------------------------------- codeAssembly
	/**
	 * @param $before_code string[]
	 * @param $advice_code string
	 * @param $after_code  string[]
	 * @return string
	 */
	private function codeAssembly($before_code, $advice_code, $after_code)
	{
		return trim(
			($before_code ? "\n" : "") . join("\n", array_reverse($before_code))
			. "\n" . $advice_code
			. ($after_code ? "\n" : "") . join("\n", $after_code)
		);
	}

	//--------------------------------------------------------------------------------------- compile
	/**
	 * @param $method_name string
	 * @param $advices     array
	 * @return string
	 */
	public function compile($method_name, $advices)
	{
		$class_name = $this->class_name;
		$buffer =& $this->buffer;

		if (self::DEBUG) echo "<h3>Method $class_name::$method_name</h3>";

		$append = "";

		$in_parent = false;
		$source_method = new Reflection_Method($class_name, $method_name);
		$preg_expr = '`(\n\s*)((private|protected|public)\s*)?((static\s*)?function\s+)'
			. '(' . $method_name . ')(\s*\([^\{]*\)[\n*\s*]*\{)`';
		// 0 : "\n\tpublic static function methodName ("
		// 1 : "\n\t"
		// 2 : "public "
		// 3 : "public"
		// 4 : "static function "
		// 5 : "static "
		// 6 : "methodName"
		// 7 : " ($param, &$param2 = CONSTANT) {"
		preg_match($preg_expr, $buffer, $match);
		if (!$match) {
			if (!method_exists($class_name, $method_name)) {
				user_error("$class_name::$method_name not found", E_USER_ERROR);
			}
			else {
				$in_parent = true;
				if (self::DEBUG) echo "- LOAD " . $source_method->getFileName() . "<br>";
				$source_method_buffer = file_get_contents($source_method->getFileName());
				preg_match($preg_expr, $source_method_buffer, $match);
				if (!$match) {
					if (self::DEBUG) echo "<pre>" . htmlentities($buffer) . "</pre>";
					user_error(
						"$class_name::$method_name not found into " . $source_method->class, E_USER_ERROR
					);
				}
			}
		}
		if (self::DEBUG) echo "<pre>" . htmlentities($preg_expr) . "</pre>";
		if (self::DEBUG) echo "<pre>" . print_r($match, true) . "</pre>";
		// $indent = prototype level indentation spaces
		$indent = $match[1];
		$i2 = $indent . "\t";
		$i3 = $i2 . "\t";
		// $parameters = array('parameter_name' => Reflection_Parameter)
		$parameters = $source_method->getParameters();
		// $doc_comment = source method doc comment
		$doc_comment = $source_method->getDocComment();
		// $parameters_names = '$parameter1, $parameter2'
		$parameters_names = $parameters ? ('$' . join(', $', array_keys($parameters))) : '';
		// $prototype = 'public [static] function methodName($parameter1, $parameter2 = "default")'
		$prototype = $match[0] . $i2;
		/** $is_static = '[static]' */
		$is_static = trim($match[5]);
		/** @var $count integer around method counter */
		$count = null;

		// $joinpoint_has_return
		$joinpoint_has_return = strpos($doc_comment, '@return');

		// $pointcut_string
		if ($is_static) {
			$pointcut_string = "array(get_called_class(), '$method_name')";
		}
		else {
			$pointcut_string = 'array($this, ' . "'$method_name'" . ')';
		}

		// $code the generated code starts by the doc comments and prototype
		$after_code  = array();
		$before_code = array();
		$advices_count = count($advices);
		$advice_number = 0;

		if (self::DEBUG && $in_parent) echo "in_parent = true for $class_name::$method_name<br>";

		$call_code = $i2 . ($joinpoint_has_return ? '$result_ = ' : '')
			. ($is_static ? 'self::' : ($in_parent ? 'parent::' : '$this->'))
			. $method_name . ($in_parent ? '' : ('_' . $count))
			. '(' . $parameters_names . ');';

		foreach (array_reverse($advices) as $advice) {
			$advice_number++;
			$type = $advice[0];

			if (self::DEBUG) echo "<h4>$type => " . print_r($advice[1], true) . "</h4>";

			// $advice_object, $advice_method_name, $advice_method, $is_advice_static
			// $advice_string = "array($object_, 'methodName')" | "'functionName'"
			if (is_array($advice)) {
				$advice_function_name = null;
				list($advice_object, $advice_method_name) = $advice[1];
				$advice_method = new Reflection_Method(
					is_object($advice_object) ? get_class($advice_object) : $advice_object,
					$advice_method_name
				);
				if (is_object($advice_object)) {
					$is_advice_static = false;
					$advice_class_name = get_class($advice_object);
					if ($advice_object instanceof Plugins\Plugin) {
						$advice_string = 'array($object_, ' . "'" . $advice_method_name . "'" . ')';
					}
					else {
						trigger_error(
							'Compiler does not how to compile non-plugin objects (' . $advice_class_name . ')',
							E_USER_ERROR
						);
						$advice_string = null;
					}
				}
				else {
					$advice_class_name = $advice_object;
					$advice_string = "array('" . $advice_class_name . "', '" . $advice_method_name . "')";
					$is_advice_static = true;
				}
			}
			else {
				$advice_class_name = null;
				$advice_method_name = null;
				$advice_function_name = $advice[1];
				$advice_method = new Reflection_Function($advice_function_name);
				$advice_string = "'" . $advice_function_name . "'";
				$is_advice_static = false;
			}

			// $advice_has_return, $advice_parameters, $advice_parameters_names, $joinpoint_code
			$advice_has_return = strpos($advice_method->getDocComment(), '@return');
			$advice_parameters = $advice_method->getParameters();
			$joinpoint_code = '';
			if ($advice_parameters) {
				$advice_parameters_string = '$' . join(', $', array_keys($advice_parameters));
				if (isset($advice_parameters['result']) && !isset($parameters['result'])) {
					$advice_parameters_string = str_replace('$result', '$result_', $advice_parameters_string);
				}
				if (isset($advice_parameters['object']) && !isset($parameters['object'])) {
					$advice_parameters_string = str_replace('$object', '$this', $advice_parameters_string);
				}
				if (isset($advice_parameters['joinpoint'])) {
					$advice_parameters_string = str_replace(
						'$joinpoint', '$joinpoint_', $advice_parameters_string
					);
					//$joinpoint_parameters_string = 'array(' . str_replace('$', '&$', $parameters_names) . ')';
					$joinpoint_parameters_string = 'array(';
					foreach (array_keys($parameters) as $key => $name) {
						if ($key) $joinpoint_parameters_string .= ', ';
						$joinpoint_parameters_string .= $key . ' => &$' . $name . ', "' . $name . '" => &$' . $name;
					}
					$joinpoint_parameters_string .= ')';
					switch ($type) {
						case 'after':
							$joinpoint_code = $i2 . '$joinpoint_ = new \SAF\AOP\After_Method_Joinpoint('
								. $i3 . '__CLASS__, ' . $pointcut_string . ', ' . $joinpoint_parameters_string . ', $result_, ' . $advice_string
								. $i2 . ');';
							break;
						case 'around':
							$process_callback = $method_name . '_' . $count;
							$joinpoint_code = $i2 . '$joinpoint_ = new \SAF\AOP\Around_Method_Joinpoint('
								. $i3 . '__CLASS__, ' . $pointcut_string . ', ' . $joinpoint_parameters_string . ', ' . $advice_string . ', ' . "'$process_callback'"
								. $i2 . ');';
							break;
						case 'before':
							$joinpoint_code = $i2 . '$joinpoint_ = new \SAF\AOP\Before_Method_Joinpoint('
								. $i3 . '__CLASS__, ' . $pointcut_string . ', ' . $joinpoint_parameters_string . ', ' . $advice_string
								. $i2 . ');';
							break;
					}
				}
			}
			else {
				$advice_parameters_string = '';
			}

			// $advice_code
			if (is_array($advice)) {
				// static method call
				if ($is_advice_static) {
					$advice_code = $joinpoint_code . $i2 . $advice_class_name . '::' . $advice_method_name
						. '(' . $advice_parameters_string . ');';
				}
				// object method call
				else {
					$advice_code = $i2 . '/** @var $object_ ' . "\\" . $advice_class_name . ' */'
						. $i2 . '$object_ = Session::current()->plugins->get(' . "'$advice_class_name'" . ');'
						. $joinpoint_code
						. $i2 . ($advice_has_return ? '$result_ = ' : '')
						. '$object_->' . $advice_method_name . '(' . $advice_parameters_string . ');';
				}
			}
			// function call
			else {
				$advice_code = $joinpoint_code . '
					' . $advice_function_name . '(' . $advice_parameters_string . ');';
			}

			switch ($type) {
				case 'after':
					if ($advice_has_return) {
						$advice_code = str_replace('$result_ = ', '$result2_ = ', $advice_code);
						$advice_code .= $i2 . 'if (isset($result2_)) $result_ = $result2_;';
					}
					if ($joinpoint_code) {
						$advice_code .= $i2 . 'if ($joinpoint_->stop) return $result_;';
					}
					$after_code[] = $advice_code;
					break;
				case 'around':
					$my_prototype = ($advice_number == $advices_count)
						? $prototype
						: str_replace($method_name, $method_name . '_' . $count , $prototype);
					$append .= $indent . $doc_comment . $my_prototype
						. $this->codeAssembly($before_code, $advice_code, $after_code)
						. ($joinpoint_has_return ? ("\n" . $i2 . 'return $result_;') : '')
						. $indent . "}\n";
					if ($advice_number < $advices_count) {
						$count ++;
					}
					$before_code = array();
					$after_code = array();
					break;
				case 'before':
					if ($advice_has_return) {
						$advice_code .= $i2 . 'if (isset($result_)) return $result_;';
					}
					if ($joinpoint_code) {
						$advice_code .= $i2 . 'if ($joinpoint_->stop)) return $result_;';
					}
					$before_code[] = $advice_code;
					break;
			}
		}
		if ($before_code || $after_code) {
			$append .= $indent . $doc_comment . $prototype
				. $this->codeAssembly($before_code, $call_code, $after_code)
				. ($joinpoint_has_return ? ("\n" . $i2 . 'return $result_;') : '')
				. $indent . "}\n";
		}

		$buffer = preg_replace(
			$preg_expr, $indent . '/* $2*/ private $4' . $method_name . '_' . $count . '$7', $buffer
		);

		return $append;
	}

}