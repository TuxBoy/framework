<?php
namespace SAF\Framework;

/**
 * Import worksheet
 */
class Import_Worksheet
{

	//----------------------------------------------------------------------------------------- $file
	/**
	 * @var string
	 */
	public $file;

	//----------------------------------------------------------------------------------------- $name
	/**
	 * @var string
	 */
	public $name;

	//-------------------------------------------------------------------------------------- $preview
	/**
	 * @getter getPreview
	 * @var Import_Preview
	 */
	public $preview;

	//------------------------------------------------------------------------------------- $settings
	/**
	 * @var Import_Settings
	 */
	public $settings;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $name     string
	 * @param $settings Import_Settings
	 * @param $file     File
	 * @param $preview  Import_Preview
	 */
	public function __construct(
		$name = null, Import_Settings $settings = null, $file = null, Import_Preview $preview = null
	) {
		if (isset($file))     $this->file     = $file;
		if (isset($name))     $this->name     = $name;
		if (isset($preview))  $this->preview  = $preview;
		if (isset($settings)) $this->settings = $settings;
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return strval($this->name);
	}

	//------------------------------------------------------------------------------------ getPreview
	/**
	 * @return Import_Preview
	 */
	public function getPreview()
	{
		if (!isset($this->preview)) {
			$this->preview = new Import_Preview($this->file->getCsvContent());
		}
		return $this->preview;
	}

}
