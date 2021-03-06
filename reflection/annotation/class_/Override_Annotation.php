<?php
namespace ITRocks\Framework\Reflection\Annotation\Class_;

use ITRocks\Framework\Reflection\Annotation\Template\Annotation_In;
use ITRocks\Framework\Reflection\Annotation\Template\List_Annotation;
use ITRocks\Framework\Reflection\Annotation\Template\Multiple_Annotation;

/**
 * Class override annotation
 */
class Override_Annotation extends List_Annotation implements Multiple_Annotation
{
	use Annotation_In;

	//-------------------------------------------------------------------------------- $property_name
	/**
	 * @var string
	 */
	public $property_name;

	//----------------------------------------------------------------------------------- __construct
	/** @noinspection PhpMissingParentConstructorInspection This does all the wark itself */
	/**
	 * @param $value string
	 */
	public function __construct($value)
	{
		foreach (explode(SP . AT, $value) as $override_annotation) {
			if (!isset($this->property_name)) {
				$this->property_name = $override_annotation;
			}
			else {
				if (strpos($override_annotation, SP)) {
					list($annotation_name, $annotation_value) = explode(SP, $override_annotation, 2);
				}
				else {
					$annotation_name = $override_annotation;
					$annotation_value = '';
				}
				$this->value[$annotation_name] = $annotation_value;
			}
		}
	}

}
