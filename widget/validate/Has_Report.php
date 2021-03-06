<?php
namespace ITRocks\Framework\Widget\Validate;

/**
 * Validators have report
 */
trait Has_Report
{

	//--------------------------------------------------------------------------------------- $report
	/**
	 * The validation report contains a detailed list of validate annotations and values
	 *
	 * @read_only
	 * @var Annotation[]
	 */
	public $report = [];

}
