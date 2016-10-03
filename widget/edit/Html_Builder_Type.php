<?php
namespace SAF\Framework\Widget\Edit;

use DateTime;
use SAF\Framework\Controller\Feature;
use SAF\Framework\Controller\Parameter;
use SAF\Framework\Controller\Target;
use SAF\Framework\Dao\File;
use SAF\Framework\Dao\File\Session_File;
use SAF\Framework\Dao\File\Session_File\Files;
use SAF\Framework\Dao;
use SAF\Framework\Locale\Loc;
use SAF\Framework\Reflection\Type;
use SAF\Framework\Session;
use SAF\Framework\Tools\Names;
use SAF\Framework\View;
use SAF\Framework\View\Html\Dom\Anchor;
use SAF\Framework\View\Html\Dom\Button;
use SAF\Framework\View\Html\Dom\Element;
use SAF\Framework\View\Html\Dom\Image;
use SAF\Framework\View\Html\Dom\Input;
use SAF\Framework\View\Html\Dom\Select;
use SAF\Framework\View\Html\Dom\Set;
use SAF\Framework\View\Html\Dom\Span;
use SAF\Framework\View\Html\Dom\Textarea;

/**
 * Builds a standard form input matching a given data type and value
 */
class Html_Builder_Type
{

	//----------------------------------------------------------------------------------- $conditions
	/**
	 * The key is the name of the condition, the value is the name of the value that enables
	 * the condition
	 *
	 * @var string[]
	 */
	public $conditions;

	//----------------------------------------------------------------------------------------- $name
	/**
	 * @var string
	 */
	public $name;

	//----------------------------------------------------------------------------------------- $null
	/**
	 * The control may have an empty value
	 * ie checkboxes may not be limited to '0' / '1' value, and may be '' too
	 *
	 * @var boolean
	 */
	public $null = false;

	//------------------------------------------------------------------------------------ $on_change
	/**
	 * @var string[]
	 */
	public $on_change = [];

	//-------------------------------------------------------------------------------------- $preprop
	/**
	 * @var string
	 */
	public $preprop;

	//------------------------------------------------------------------------------------- $readonly
	/**
	 * the control will be read-only
	 *
	 * @var boolean
	 */
	public $readonly = false;

	//------------------------------------------------------------------------------------- $template
	/**
	 * @var Html_Template
	 */
	public $template;

	//----------------------------------------------------------------------------------------- $type
	/**
	 * @var Type
	 */
	protected $type;

	//---------------------------------------------------------------------------------------- $value
	/**
	 * @var string
	 */
	protected $value;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $name    string
	 * @param $type    Type
	 * @param $value   mixed
	 * @param $preprop string
	 */
	public function __construct($name = null, Type $type = null, $value = null, $preprop = null)
	{
		if (isset($name))    $this->name = $name;
		if (isset($type))    $this->type = $type;
		if (isset($value))   $this->value = $value;
		if (isset($preprop)) $this->preprop = $preprop;
	}

	//------------------------------------------------------------------------ addConditionsToElement
	/**
	 * Add conditions to the input 'data-conditions' attribute
	 *
	 * @param $element Element the button / input / select element
	 */
	protected function addConditionsToElement($element)
	{
		if ($this->conditions) {
			$html_conditions = [];
			$old_name = $this->name;
			foreach ($this->conditions as $condition_name => $condition_value) {
				$this->name = $condition_name;
				$name = $this->getFieldName('', false);
				$html_conditions[] = $name . '=' . $condition_value;
			}
			$this->name = $old_name;
			$element->setAttribute('data-conditions', join(';', $html_conditions));
		}
	}

	//----------------------------------------------------------------------------------------- build
	/**
	 * @return string
	 */
	public function build()
	{
		$this->patchSearchTypes();
		$type = $this->type;
		if (!isset($type)) {
			return $this->buildId();
		}
		else {
			switch ($type->asString()) {
				case Type::BOOLEAN:      $result = $this->buildBoolean(); break;
				case Type::FLOAT:        $result = $this->buildFloat();   break;
				case Type::INTEGER:      $result = $this->buildInteger(); break;
				case Type::STRING:       $result = $this->buildString();  break;
				case Type::STRING_ARRAY: $result = $this->buildString();  break;
			}
			if (!isset($result) && $type->isClass()) {
				$class_name = $type->asString();
				if (is_a($class_name, DateTime::class, true)) {
					$result = $this->buildDateTime();
				}
				elseif (is_a($class_name, File::class, true)) {
					$result = $this->buildFile();
				}
				else {
					$result = $this->buildObject();
				}
			}
			/**
			 * @todo SM: create a Editable_Element class to be able to add some behavior like on_change because Element may be span or other html
			 *
			 */
			if (isset($result) && ($result instanceof Element)) {
				$this->setOnChangeAttribute($result);
			}
		}
		return isset($result) ? $result : $this->value;
	}

	//---------------------------------------------------------------------------------- buildBoolean
	/**
	 * @return Element
	 */
	protected function buildBoolean()
	{
		$value = strlen($this->value) ? ($this->value ? 1 : 0) : ($this->null ? null : 0);
		if ($this->null) {
			$input = new Select(
				$this->getFieldName(), ['' => '', '0' => 'no', '1' => 'yes'], $value
			);
			if ($this->readonly) {
				$this->setInputAsReadOnly($input);
			}
			return $input;
		}
		else {
			$input = new Input($this->getFieldName());
			$input->setAttribute('type', 'hidden');
			$input->setAttribute('value', $value);
			$checkbox = new Input();
			$checkbox->setAttribute('type', 'checkbox');
			$checkbox->setAttribute('value', $value);
			if ($this->null) {
				$checkbox->setData('nullable', strlen($this->value) ? ($this->value ? 0 : 1) : 0);
			}
			if ($this->readonly) {
				$this->setInputAsReadOnly($input);
			}
			if ($this->value) {
				$checkbox->setAttribute('checked');
			}
			$this->addConditionsToElement($checkbox);
			return $input . $checkbox;
		}
	}

	//--------------------------------------------------------------------------------- buildDateTime
	/**
	 * @param $format boolean
	 * @return Element
	 */
	protected function buildDateTime($format = true)
	{
		$input = new Input(
			$this->getFieldName(),
			$format ? Loc::dateToLocale($this->value) : $this->value
		);
		$input->setAttribute('autocomplete', 'off');
		if ($this->readonly) {
			$this->setInputAsReadOnly($input);
		}
		else {
			$input->addClass('datetime');
		}
		$this->addConditionsToElement($input);
		return $input;
	}

	//------------------------------------------------------------------------------------- buildFile
	/**
	 * @return Span
	 */
	protected function buildFile()
	{
		$file = new Input($this->getFieldName());
		$file->setAttribute('type', 'file');
		$file->addClass('file');
		if ($this->readonly) {
			$this->setInputAsReadOnly($file);
		}
		if ($this->value instanceof File) {
			$span = $this->buildFileAnchor($this->value);
		}
		else {
			$span = '';
		}
		$this->addConditionsToElement($file);
		return $file . $span;
	}

	//------------------------------------------------------------------------------- buildFileAnchor
	/**
	 * @param $file File
	 * @return Anchor
	 */
	protected function buildFileAnchor(File $file)
	{
		/** @var $session_files Files */
		$session_files = Session::current()->get(Files::class, true);
		$session_files->files[] = $file;
		$image = ($file->getType()->is('image'))
			? new Image(View::link(Session_File::class, Feature::F_OUTPUT, [$file->name], ['size' => 22]))
			: '';
		$anchor = new Anchor(
			View::link(Session_File::class, 'image', [$file->name]),
			$image . new Span($file->name)
		);
		if ($file->getType()->is('image')) {
			$anchor->setAttribute('target', Target::BLANK);
			//$anchor->addClass('popup');
		}
		return $anchor;
	}

	//------------------------------------------------------------------------------------ buildFloat
	/**
	 * @param $format boolean
	 * @return Element
	 */
	protected function buildFloat($format = true)
	{
		$input = new Input(
			$this->getFieldName(),
			$format ? Loc::floatToLocale($this->value) : $this->value
		);
		if ($this->readonly) {
			$this->setInputAsReadOnly($input);
		}
		$input->addClass('float');
		$input->addClass('autowidth');
		$this->addConditionsToElement($input);
		return $input;
	}

	//--------------------------------------------------------------------------------------- buildId
	/**
	 * @return Element
	 */
	protected function buildId()
	{
		$input = new Input($this->getFieldName(), $this->value);
		$input->setAttribute('type', 'hidden');
		$input->addClass('id');
		if ($this->readonly) {
			// SM : note, here factorize even if there was no setAttribute('readonly')
			//$input->removeAttribute('name');
			$this->setInputAsReadOnly($input);
		}
		return $input;
	}

	//---------------------------------------------------------------------------------- buildInteger
	/**
	 * @param $format boolean
	 * @return Element
	 */
	protected function buildInteger($format = true)
	{
		$input = new Input(
			$this->getFieldName(),
			$format ? Loc::integerToLocale($this->value) : $this->value
		);
		if ($this->readonly) {
			$this->setInputAsReadOnly($input);
		}
		$input->addClass('integer');
		$input->addClass('autowidth');
		$this->addConditionsToElement($input);
		return $input;
	}

	//----------------------------------------------------------------------------------- buildObject
	/**
	 * @param $filters string[] the key is the name of the filter, the value is the name of the form
	 *   element containing its value
	 * @return string
	 */
	public function buildObject($filters = null)
	{
		$class_name = $this->type->asString();
		// visible input
		$input = new Input(null, strval($this->value));
		$input->setAttribute('autocomplete', 'off');
		$input->setAttribute('data-combo-class', Names::classToSet($class_name));
		$input->addClass('autowidth');
		if ($this->readonly) {
			$this->setInputAsReadOnly($input);
		}
		else {
			if ($filters) {
				$html_filters = [];
				$old_name = $this->name;
				foreach ($filters as $filter_name => $filter_value) {
					$this->name = $filter_value;
					$name = $this->getFieldName('', false);
					$html_filters[] = $filter_name . '=' . $name;
				}
				$this->name = $old_name;
				$input->setAttribute('data-combo-filters', join(',', $html_filters));
			}
			$input->addClass('combo');
			$this->addConditionsToElement($input);
			// id input
			$id_input = new Input(
				$this->getFieldName('id_'), $this->value ? Dao::getObjectIdentifier($this->value) : ''
			);
			$id_input->setAttribute('type', 'hidden');
			$id_input->addClass('id');
			// 'add' / 'edit' anchor
			$fill_combo = isset($this->template)
				? ['fill_combo' => $this->template->getFormId() . DOT . $this->getFieldName('id_', false)]
				: '';
			$edit = new Anchor(
				View::current()->link(
					$this->value ? get_class($this->value) : $class_name, Feature::F_ADD, null, $fill_combo
				),
				'edit'
			);
			$edit->addClass('edit');
			$edit->setAttribute('target', Target::BLANK);
			$edit->setAttribute('title', '|Edit ¦' . Names::classToDisplay($class_name) . '¦|');
			// 'more' button
			$more = new Button('more');
			$more->addClass('more');
			$more->setAttribute('tabindex', -1);
			$this->setOnChangeAttribute($id_input);
			return $id_input . $input . $more . $edit;
		}
		return $input;
	}

	//----------------------------------------------------------------------------------- buildString
	/**
	 * @param $multiline boolean
	 * @param $values    string[]
	 * @return Element
	 */
	protected function buildString($multiline = false, $values = null)
	{
		// case choice of values (single or multiple)
		if ($values) {
			if (!$this->readonly) {
				if ($this->type->isMultipleString()) {
					$input = new Set($this->getFieldName(), $values, $this->value);
				}
				else {
					if (!isset($values[''])) {
						$values = array_merge(['' => ''], $values);
					}
					$input = new Select($this->getFieldName(), $values, $this->value);
				}
			}
			else {
				if ($this->type->isMultipleString()) {
					$selected = explode(',', $this->value);
					$inputs = [];
					foreach($values as $value => $caption) {
						if (in_array($value, $selected)) {
							$hidden = new Input(null, $value);
							$hidden->setAttribute('type', 'hidden');
							$hidden->setAttribute('readonly');
							$inputs[] = [
								$hidden,
								Loc::tr($caption),
								BR
							];
						}
					}
					$input = new Span($inputs);
					$input->setAttribute('class', 'set');
					$input->setBuildMode(Element::BUILD_MODE_RAW);
				}
				else {
					$hidden = new Input(null, $this->value);
					$hidden->setAttribute('type', 'hidden');
					$hidden->setAttribute('readonly');
					$input = new Span([$hidden, Loc::tr($values[$this->value])]);
				}
			}
		}
		// case of free editable text (mono or multi line)
		else {
			$input = $this->makeTextInputOrTextarea($multiline, $this->value);
		}
		$this->addConditionsToElement($input);
		return $input;
	}

	//---------------------------------------------------------------------------------- getFieldName
	/**
	 * @param $prefix            string
	 * @param $counter_increment boolean
	 * @return string
	 */
	public function getFieldName($prefix = '', $counter_increment = true)
	{
		if (empty($this->name) && $this->preprop) {
			$prefix = '';
		}
		if (!isset($this->preprop)) {
			$field_name = $prefix . $this->name;
		}
		elseif (substr($this->preprop, -2) == '[]') {
			$field_name = substr($this->preprop, 0, -2) . '[' . $prefix . $this->name . ']';
			$count = $this->template->nextCounter($field_name, $counter_increment);
			$field_name .= '[' . $count . ']';
		}
		elseif (strlen($prefix . $this->name)) {
			$field_name = (strpos($this->preprop, '[]') !== false)
				? $this->getRepetitiveFieldName($prefix, $counter_increment)
				: $this->preprop . '[' . $prefix . $this->name . ']';
		}
		else {
			$count = $this->template->nextCounter($this->preprop, $counter_increment);
			$field_name = $this->preprop . '[' . $count . ']';
		}
		return $field_name;
	}

	//------------------------------------------------------------------------ getRepetitiveFieldName
	/**
	 * @param $prefix            string
	 * @param $counter_increment boolean
	 * @return string
	 */
	private function getRepetitiveFieldName($prefix, $counter_increment)
	{
		$i = strpos($this->preprop, '[]');
		$counter_name = substr($this->preprop, 0, $i);
		$field_name_i = $i + 3;
		$field_name_j = strpos($this->preprop, ']', $field_name_i);
		$super_field_name = substr($this->preprop, $field_name_i, $field_name_j - $field_name_i);
		$counter_name .= '[' . $super_field_name . ']' . '[' . $prefix . $this->name . ']';
		$count = $this->template->nextCounter($counter_name, $counter_increment);
		return substr($this->preprop, 0, $i)
			. '[' . $super_field_name . ']'
			. '[' . $count . ']'
			. substr($this->preprop, $field_name_j + 1)
			. '[' . $prefix . $this->name . ']';
	}

	/**
	 * @param $multiline boolean
	 * @param $value     string
	 * @return Input
	 */
	private function makeTextInputOrTextarea($multiline, $value)
	{
		if ($multiline) {
			$input = new Textarea($this->getFieldName(), $value);
			$input->addClass('autoheight');
		}
		else {
			$input = new Input($this->getFieldName(), $value);
			$input->setAttribute('autocomplete', 'off');
		}
		$input->addClass('autowidth');
		if ($this->readonly) {
			$this->setInputAsReadOnly($input);
		}
		return $input;
	}

	//------------------------------------------------------------------------------ patchSearchTypes
	/**
	 * Patch search type : eg dates should be typed as string
	 */
	private function patchSearchTypes()
	{
		if (
			(substr($this->name, 0, 7) === 'search[')
			&& $this->type->isDateTime()
		) {
			$this->type = new Type(Type::STRING);
			if ($this->value) {
				$this->value = Loc::dateToLocale($this->value);
			}
		}
	}

	//---------------------------------------------------------------------------- setInputAsReadOnly
	/**
	 * @param $input Element
	 */
	private function setInputAsReadOnly($input)
	{
		$input->removeAttribute('name');
		$input->setAttribute('readonly');
	}

	//-------------------------------------------------------------------------- setOnChangeAttribute
	/**
	 * @param $element Element
	 */
	private function setOnChangeAttribute(Element $element)
	{
		if ($this->on_change) {
			$on_change = join(',', $this->on_change);
			$element->setData('on-change', $on_change);
		}
	}

	//----------------------------------------------------------------------------------- setTemplate
	/**
	 * @param $template Html_Template
	 * @return Html_Builder_Type
	 */
	public function setTemplate(Html_Template $template)
	{
		$this->template = $template;
		if (!$this->preprop) {
			$this->preprop = $this->template->getParameter(Parameter::PROPERTIES_PREFIX);
		}
		return $this;
	}

}
