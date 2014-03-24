<?php
namespace SAF\AOP;

use SAF\Framework\Reflection_Parameter;

/**
 * Aspect weaver properties compiler
 */
class Properties_Compiler
{
	use Compiler_Toolbox;

	const DEBUG = false;

	//-------------------------------------------------------------------------------------- $actions
	/**
	 * @var string[] key is the original method name, value is the 'rename' or 'trait' action
	 */
	private $actions;

	//---------------------------------------------------------------------------------------- $class
	/**
	 * @var Php_Class
	 */
	private $class;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $class Php_Class
	 */
	public function __construct($class)
	{
		$this->class = $class;
	}

	//--------------------------------------------------------------------------------------- compile
	/**
	 * @param $advices array
	 * @return string[]
	 */
	public function compile($advices)
	{
		$this->actions = [];
		$methods = [];
		if ($this->class->type !== 'trait') {
			$methods['__construct'] = $this->compileConstruct($advices);
			if ($methods['__construct']) {
				$methods['__aop']   = $this->compileAop($advices);
				$methods['__get']   = $this->compileGet($advices);
				$methods['__isset'] = $this->compileIsset($advices);
				$methods['__set']   = $this->compileSet($advices);
				$methods['__unset'] = $this->compileUnset($advices);
			}
		}
		foreach ($advices as $property_name => $property_advices) {
			$methods['_' . $property_name . '_read'] = $this->compileRead(
				$property_name, $property_advices
			);
			$methods['_' . $property_name . '_write'] = $this->compileWrite(
				$property_name, $property_advices
			);
		}
		$this->executeActions();
		return $methods;
	}

	//--------------------------------------------------------------------------------- compileAdvice
	/**
	 * @param $property_name string
	 * @param $type          string
	 * @param $advice        string[]|object[]|string
	 * @param $init          string[]
	 * @return string
	 */
	private function compileAdvice($property_name, $type, $advice, &$init)
	{
		$class_name = $this->class->name;

		/** @var $advice_class_name string */
		/** @var $advice_method_name string */
		/** @var $advice_function_name string */
		/** @var $advice_parameters Reflection_Parameter[] */
		/** @var $advice_string string [$object_, 'methodName') | 'functionName' */
		/** @var $advice_has_return boolean */
		/** @var $is_advice_static boolean */
		list(
			$advice_class_name, $advice_method_name, $advice_function_name,
			$advice_parameters, $advice_string, $advice_has_return, $is_advice_static
			) = $this->decodeAdvice($advice, $class_name);

		// $advice_parameters_string, $joinpoint_code
		$joinpoint_code = '';
		if ($advice_parameters) {
			$advice_parameters_string = '$' . join(', $', array_keys($advice_parameters));
			if (isset($advice_parameters[$property_name])) {
				$advice_parameters_string = str_replace(
					'$' . $property_name, '$value', $advice_parameters_string
				);
			}
			if (isset($advice_parameters['property_name'])) {
				$advice_parameters_string = str_replace(
					'$property_name', Q . $property_name . Q, $advice_parameters_string
				);
			}
			if (isset($advice_parameters['result'])) {
				$advice_parameters_string = str_replace('$result', '$value', $advice_parameters_string);
			}
			if (isset($advice_parameters['object']) && !isset($parameters['object'])) {
				$advice_parameters_string = str_replace('$object', '$this', $advice_parameters_string);
			}
			if (isset($advice_parameters['stored']) || isset($advice_parameters['joinpoint'])) {
				$init['1.stored'] = '$stored =& $this->' . $property_name . ';';
			}
			if (isset($advice_parameters['joinpoint'])) {
				$pointcut_string = '[$this, ' . Q . $property_name . Q . ']';
				$init['2.joinpoint'] = '$joinpoint = new \SAF\AOP' . BS . ucfirst($type) . '_Property_Joinpoint('
					. LF . TAB . TAB . '__CLASS__, ' . $pointcut_string . ', $value, $stored, ' . $advice_string
					. ');';
			}
			if (
				isset($advice_parameters['property']) || isset($advice_parameters['type'])
				|| isset($advice_parameters['element_type']) || isset($advice_parameters['type_name'])
				|| isset($advice_parameters['element_type_name']) || isset($advice_parameters['class_name'])
			) {
				$init['3.property'] = '$property = new \SAF\Framework\Reflection_Property(get_class($this), '
					. Q . $property_name . Q . ');';
			}
			if (
				isset($advice_parameters['type']) || isset($advice_parameters['type_name'])
				|| isset($advice_parameters['element_type_name']) || isset($advice_parameters['class_name'])
			) {
				$init['4.type'] = '$type = $property->getType();';
			}
			if (isset($advice_parameters['element_type'])) {
				$init['5.element_type'] = '$element_type = $property->getElementType();';
			}
			if (isset($advice_parameters['type_name'])) {
				$init['6.type_name'] = '$type_name = $type->asString();';
			}
			if (isset($advice_parameters['element_type_name'])) {
				$init['7.element_type_name'] = '$element_type_name = $type->getElementTypeAsString();';
			}
			if (isset($advice_parameters['class_name'])) {
				$init['7.class_name'] = '$class_name = $type->getElementTypeAsString();';
			}
		}
		else {
			$advice_parameters_string = '';
		}

		return $this->generateAdviceCode(
			$advice, $advice_class_name, $advice_method_name, $advice_function_name,
			$advice_parameters_string, $advice_has_return, $is_advice_static, $joinpoint_code,
			LF . TAB . TAB, '$value'
		);
	}

	//------------------------------------------------------------------------------------ compileAop
	/**
	 * @param $advices array
	 * @return string
	 */
	private function compileAop($advices)
	{
		$parent_code = '';
		$begin_code = '
	/** AOP */
	protected function __aop($init = true)
	{
		if ($init) $this->_ = [];';
		$code = '';
		foreach ($advices as $property_name => $property_advices) {
			if (!isset($advices[$property_name]['override'])) {
				$code .= '
		';
				if (!isset($advices[$property_name]['replaced'])) {
					$code .= '
		$this->' . $property_name . '_ = isset($this->' . $property_name . ')'
						. ' ? $this->' . $property_name . ' : null;';
				}
				$code .= '
		unset($this->' . $property_name . ');
		$this->_[' . Q . $property_name . Q . '] = true;';
			}
		}
		// todo this check only getters, links and setters. This should check AOP links too.
		if (($parent = $this->class->getParent()) && ($parent->type == 'class')) {
			foreach ($parent->getProperties(['traits', 'inherited']) as $property) {
				$expr = '%'
					. '\n\s+\*\s+'               // each line beginning by '* '
					. '@(getter|link|setter)'    // 1 : AOP annotation
					. '(?:\s+(?:([\\\\\w]+)::)?' // 2 : class name
					. '(\w+)?)?'                 // 3 : method or function name
					. '%';
				preg_match($expr, $property->documentation, $match);
				if ($match) {
					$parent_code = '

		parent::__aop(false);';
					break;
				}
			}
		}
		return $begin_code . $code . $parent_code . '
	}
';
	}

	//------------------------------------------------------------------------------ compileConstruct
	/**
	 * Compile __construct if there is at least one property declared in this class / traits
	 *
	 * @param $advices array
	 * @return string
	 */
	private function compileConstruct($advices)
	{
		// only if at least one property is declared here
		foreach ($advices as $property_advices) {
			if (isset($property_advices['implements'])) {
				$over = $this->overrideMethod('__construct', false);
				return
	$over['prototype'] . '
		if (!isset($this->_)) $this->__aop();' . ($over['call'] ? (LF . TAB . TAB . $over['call']) : '') . '
	}
';
			}
		}
		return '';
	}

	//------------------------------------------------------------------------------------ compileGet
	/**
	 * @param $advices array
	 * @return string
	 */
	private function compileGet($advices)
	{
		$over = $this->overrideMethod('__get', true, $advices);
		$code =
	$over['prototype'] . '
		if (!isset($this->_) || !isset($this->_[$property_name])) {
			' . $over['call'] . '
		}';
		if ($over['cases']) {
			$switch = true;
			$code .= '
		switch ($property_name) {';
		}
		foreach ($advices as $property_name => $property_advices) {
			if (isset($property_advices['replaced'])) {
				if (!isset($switch)) {
					$switch = true;
					$code .= '
		switch ($property_name) {';
				}
				$code .= '
			case ' . Q . $property_name . Q . ': $value =& $this->' . $property_advices['replaced'] . '; return $value;';
				if (isset($over['cases'][$property_name])) {
					unset($over['cases'][$property_name]);
					if (count($over['cases']) == 1) {
						$over['cases'] = [];
					}
				}
			}
			elseif (isset($property_advices['implements']['read'])) {
				if (!isset($switch)) {
					$switch = true;
					$code .= '
		switch ($property_name) {';
				}
				$code .= '
			case ' . Q . $property_name . Q . ': return $this->_' . $property_name . '_read();';
			}
		}
		if (isset($switch)) {
			$code .= join('', $over['cases']) . '
		}';
		}
		return $code . '
		$property_name .= \'_\';
		return $this->$property_name;
	}
';
	}

	//---------------------------------------------------------------------------------- compileIsset
	/**
	 * @param $advices array
	 * @return string
	 */
	private function compileIsset($advices)
	{
		$over = $this->overrideMethod('__isset');
		$code =
	$over['prototype'] . '
		if (!isset($this->_) || !isset($this->_[$property_name])) {
			' . $over['call'] . '
		}';
		foreach ($advices as $property_name => $property_advices) {
			if (isset($property_advices['replaced'])) {
				if (!isset($switch)) {
					$switch = true;
					$code .= '
		switch ($property_name) {';
				}
				$code .= '
			case ' . Q . $property_name . Q . ': return isset($this->' . $property_advices['replaced'] . ');';
			}
		}
		if (isset($switch)) {
			unset($switch);
			$code .= '
		}';
		}
		return $code . '
		$property_name .= \'_\';
		return isset($this->$property_name);
	}
';
	}

	//----------------------------------------------------------------------------------- compileRead
	/**
	 * @param $property_name string
	 * @param $advices       array
	 * @return string
	 */
	private function compileRead($property_name, $advices)
	{
		$code = '';
		$init = [];
		$last = '';
		foreach ($advices as $key => $aspect) if (is_numeric($key)) {
			if ($aspect[0] === 'write') {
				$last = '$last = ';
				break;
			}
		}
		foreach ($advices as $key => $aspect) if (is_numeric($key)) {
			list($type, $advice) = $aspect;
			if ($type == 'read') {
				if (!isset($prototype)) {
					$prototype = '
	/** AOP */
	private function & _' . $property_name . '_read()
	{
		unset($this->_[' . Q . $property_name . Q . ']);
		' . $last . '$value = $this->' . $property_name . ' =& $this->' . $property_name . '_;
';
				}
				$code .= $this->compileAdvice($property_name, 'read', $advice, $init);
				if ($last) {
					$code .= '
		if ($this->' . $property_name . ' !== $last) {
			$this->_' . $property_name . '_write($this->' . $property_name . ');
			$last = $this->' . $property_name . ';
		}';
				}
			}
		}
		if (isset($prototype)) {
			// todo missing call of setters if value has been changed
			return $prototype . $this->initCode($init) . $code . '

		unset($this->' . $property_name . ');
		$this->_[' . Q . $property_name . Q . '] = true;
		return $value;
	}
';
		}
		return '';
	}

	//------------------------------------------------------------------------------------ compileSet
	/**
	 * @param $advices array
	 * @return string
	 */
	private function compileSet($advices)
	{
		$over = $this->overrideMethod('__set', true, $advices);
		$code =
	$over['prototype'] . '
		if (!isset($this->_) || !isset($this->_[$property_name])) {
			' . $over['call'] . '
		}';
		if ($over['cases']) {
			$switch = true;
			$code .= '
		switch ($property_name) {';
		}
		foreach ($advices as $property_name => $property_advices) {
			if (isset($property_advices['replaced'])) {
				if (!isset($switch)) {
					$switch = true;
					$code .= '
		switch ($property_name) {';
				}
				$code .= '
			case ' . Q . $property_name . Q . ': $this->' . $property_advices['replaced'] . ' = $value; return;';
				if (isset($over['cases'][$property_name])) {
					unset($over['cases'][$property_name]);
					if (count($over['cases']) == 1) {
						$over['cases'] = [];
					}
				}
			}
			elseif (isset($property_advices['implements']['write'])) {
				if (!isset($switch)) {
					$switch = true;
					$code .= '
		switch ($property_name) {';
				}
				$code .= '
			case ' . Q . $property_name . Q . ': $this->_' . $property_name . '_write($value); return;';
			}
		}
		if (isset($switch)) {
			$code .= join('', $over['cases']) . '
		}';
		}
		return $code . '
		$property_name .= \'_\';
		$this->$property_name = $value;
	}
';
	}

	//---------------------------------------------------------------------------------- compileUnset
	/**
	 * @param $advices array
	 * @return string
	 */
	private function compileUnset($advices)
	{
		$over = $this->overrideMethod('__unset');
		$code =
	$over['prototype'] . '
		if (!isset($this->_) || !isset($this->_[$property_name])) {
			' . $over['call'] . '
		}';
		foreach ($advices as $property_name => $property_advices) {
			if (isset($property_advices['replaced'])) {
				if (!isset($switch)) {
					$switch = true;
					$code .= '
		switch ($property_name) {';
				}
				$code .= '
			case ' . Q . $property_name . Q . ': unset($this->' . $property_advices['replaced'] . '); return;';
			}
		}
		if (isset($switch)) {
			unset($switch);
			$code .= '
		}';
		}
		return $code . '
		$property_name .= \'_\';
		$this->$property_name = null;
	}
';
	}

	//---------------------------------------------------------------------------------- compileWrite
	/**
	 * @param $property_name string
	 * @param $advices       array
	 * @return string
	 */
	private function compileWrite($property_name, $advices)
	{
		$code = '';
		$init = [];
		foreach ($advices as $key => $aspect) if (is_numeric($key)) {
			list($type, $advice) = $aspect;
			if ($type == 'write') {
				if (!isset($prototype)) {
					$prototype = '
	/** AOP */
	private function _' . $property_name . '_write($value)
	{
		if (isset($this_[' . Q . $property_name . Q . '])) {
			unset($this->_[' . Q . $property_name . Q . ']);
			$this->' . $property_name . ' = $this->' . $property_name . '_;
			$writer = true;
		}
';
				}
				$code .= $this->compileAdvice($property_name, 'read', $advice, $init);
			}
		}
		if (isset($prototype)) {
			return $prototype . $this->initCode($init) . $code . '

		if (isset($writer)) {
			$this->' . $property_name . '_ = $this->' . $property_name . ';
			unset($this->' . $property_name . ');
			$this->_[' . Q . $property_name . Q . '] = true;
		}
	}
';
		}
		return '';
	}

	//-------------------------------------------------------------------------------- executeActions
	private function executeActions()
	{
		foreach ($this->actions as $method_name => $action) {
			if ($action == 'rename') {
				$regexp = Php_Method::regex($method_name);
				$this->class->source = preg_replace(
					$regexp,
					LF . TAB . '$2' . LF . TAB . '/* $4 */ private $5 function $6$7_0$8$9',
					$this->class->source
				);
			}
			else {
				trigger_error(
					'Don\'t know how to ' . $action . SP . $this->class->name . '::' . $method_name,
					E_USER_ERROR
				);
			}
		}
	}

	//-------------------------------------------------------------------------------------- initCode
	/**
	 * @param $init string[]
	 * @return string
	 */
	private function initCode($init)
	{
		if (isset($init['7.element_type_name']) && isset($init['7.class_name'])) {
			$init['7.class_name_element_type_name'] = '$class_name = ' . $init['7.element_type_name'];
			unset($init['7.class_name']);
			unset($init['7.element_type_name']);
		}
		ksort($init);
		return $init ? (LF . TAB . TAB . join(LF . TAB . TAB, $init) . LF) : '';
	}

	//-------------------------------------------------------------------------------- overrideMethod
	/**
	 * Override a public method
	 *
	 * @param $method_name  string
	 * @param $needs_return boolean if false, call will not need return statement
	 * @param $advices      array
	 * @return array action (rename, trait), call, Php_Method method, prototype
	 */
	private function overrideMethod($method_name, $needs_return = true, $advices = null)
	{
		$over = ['cases' => []];
		$parameters = '';
		// the method exists into the class
		$methods = $this->class->getMethods();
		if (isset($methods[$method_name])) {
			$method = $methods[$method_name];
			$over['action'] = 'rename';
			$over['call']   = '$this->';
		}
		else {
			// the method exists into a trait of the class
			$methods = $this->class->getMethods(['traits']);
			if (isset($methods[$method_name])) {
				$method = $methods[$method_name];
				$over['action'] = 'trait';
				$over['call']   = '$this->';
			}
			else {
				// the method exists into a parent class / trait and is not abstract
				$methods = $this->class->getMethods(['inherited']);
				if (isset($methods[$method_name]) && !$methods[$method_name]->isAbstract()) {
					$method = $methods[$method_name];
					$over['action'] = false;
					$over['call']   = 'parent::';
				}
				else {
					// the method does not exist and the parent has no AOP properties
					$over['action'] = false;
					$over['call']   = false;
					$over['cases']  = $this->parentCases($method_name, $parameters, $advices);
				}
			}
		}
		// the method exists : prepare call and prototype
		if (isset($method)) {
			$over['method']    = $method;
			$over['prototype'] = rtrim($method->prototype);
			if (in_array($method_name, ['__get', '__isset', '__set', '__unset'])) {
				$parameters = $method->getParametersNames();
				if (reset($parameters) !== 'property_name') {
					$over['prototype'] .= '
		$property_name = $' . reset($parameters) . ';';
				}
				if ((count($parameters) == 2) && (end($parameters) !== 'value')) {
					$over['prototype'] .= '
		$value = $' . end($parameters) . ';';
				}
			}
			if ($over['call']) {
				$suffix = ($over['call'] === 'parent::') ? '' : '_0';
				$over['call'] = ($method->returns() ? 'return ' : '')
					. $over['call'] . $method_name . $suffix . '(' . $method->getParametersCall() . ');'
					. (($method->returns() || !$needs_return) ? '' : ' return;');
			}
		}
		// the method does not exist : call default code and create default prototype
		else {
			$over['action'] = false;
			$over['method'] = false;
			if (!$over['call']) {
				$parameters = '$property_name';
				switch ($method_name) {
					case '__get':
						$over['call'] = 'return $this->$property_name;';
						break;
					case '__isset':
						$over['call'] = 'return isset($this->$property_name);';
						break;
					case '__set':
						$over['call'] = '$this->$property_name = $value; return;';
						$parameters .= ', $value';
						break;
					case '__unset':
						$over['call'] = 'unset($this->$property_name); return;';
						break;
					default:
						$parameters = '';
				}
			}
			$over['prototype'] = '
	/** AOP */
	public function ' . $method_name . '(' . $parameters . ')
	{';
		}
		if ($over['action']) {
			$this->actions[$method_name] = $over['action'];
		}
		$over['prototype'] = preg_replace('%(function\s+)(__get\s*\()%', '$1& $2', $over['prototype']);
		return $over;
	}

	//----------------------------------------------------------------------------------- parentCases
	/**
	 * @param $method_name string
	 * @param $parameters  string
	 * @param $advices     array
	 * @return string
	 *
	 * @todo this check only getters, links and setters. This should check AOP links too.
	 * (the parent class has not this method but it has AOP properties)
	 */
	private function parentCases($method_name, &$parameters, $advices)
	{
		$cases = [];
		if (
			in_array($method_name, ['__get', '__set'])
			&& ($this->class->type == 'class')
			&& ($parent = $this->class->getParent())
		) {
			$annotation = ($method_name == '__get') ? '(getter|link)' : 'setter';
			$type = ($method_name == '__get') ? 'read' : 'write';
			foreach ($parent->getProperties(['traits', 'inherited']) as $property) {
				if (!isset($advices[$property->name]['implements'][$type])) {
					$expr = '%'
						. '\n\s+\*\s+'               // each line beginnig by '* '
						. '@' . $annotation          // 1 : AOP annotation
						. '(?:\s+(?:([\\\\\w]+)::)?' // 2 : class name
						. '(\w+)?)?'                 // 3 : method or function name
						. '%';
					preg_match($expr, $property->documentation, $match);
					if ($match) {
						$cases[$property->name] = LF . TAB . TAB . TAB . 'case ' . Q . $property->name . Q . ':';
					}
				}
			}
			if ($cases) {
				$parameters = '$property_name';
				switch ($method_name) {
					case '__get':
						$cases[] .= ' return parent::__get($property_name);';
						break;
					case '__isset':
						$cases[] .= ' return parent::__isset($property_name);';
						break;
					case '__set':
						$cases[] .= ' parent::__set($property_name, $value); return;';
						$parameters .= ', $value';
						break;
					case '__unset':
						$cases[] .= ' parent::__unset($property_name); return;';
						break;
					default:
						$parameters = '';
				}
			}
		}
		return $cases;
	}

}
