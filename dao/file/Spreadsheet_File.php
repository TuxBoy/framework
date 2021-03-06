<?php
namespace ITRocks\Framework\Dao\File;

use PHPExcel_IOFactory;
use ITRocks\Framework\Application;
use ITRocks\Framework\Locale\Loc;
use ITRocks\Framework\Locale\Option\Replace;
use ITRocks\Framework\Reflection\Reflection_Class;

/*
use PHPExcel;
use PHPExcel_IOFactory;
*/

/**
 * Excel file
 *
 * Needs gnumeric and others dependencies : install (Debian/Ubuntu/Mint example) with :
 * apt-get install gnumeric php_zip php_xml php_gd2
 */
class Spreadsheet_File
{

	//-------------------------------------------------------------------------------- createFromFile
	/**
	 * @param $file_name string The Excel file name to be read
	 * @return Spreadsheet_File
	 */
	public static function createFromFile($file_name)
	{
		$source_object = PHPExcel_IOFactory::load($file_name);
		$destination_object = new Spreadsheet_File();
		$source_class = new Reflection_Class(get_class($source_object));
		$destination_class = new Reflection_Class(__CLASS__);
		$destination_properties = $destination_class->accessProperties();
		foreach ($source_class->accessProperties() as $source_property) {
			if (!$source_property->isStatic()) {
				$destination_property = $destination_properties[$source_property->name];
				$destination_property->setValue(
					$destination_object,
					$source_property->getValue($source_object)
				);
			}
		}
		return $destination_object;
	}

	//----------------------------------------------------------------------------------- fileToArray
	/**
	 * This is a direct, fast and optimized feature to read an excel file and return its worksheets
	 * into a simple PHP array, as fast as possible, using gnumeric.
	 *
	 * This enables you to import huge xls files of 10MB and more
	 *
	 * @param $file_name string
	 * @param $errors    string[]
	 * @return array three dimensions (worksheet, row, column) array of read data
	 */
	public static function fileToArray($file_name, array &$errors = [])
	{
		if (substr($file_name, -4) == '.csv') {
			$csv_file = $file_name;
			$count = '';
		}
		else {
			$csv_file = Application::current()->getTemporaryFilesPath() . SL . uniqid() . '.csv';
			exec('ssconvert ' . DQ . $file_name . DQ . SP . DQ . $csv_file . DQ . ' -S 2>&1 &');
			$count = 0;
		}
		$result = [];
		while (file_exists($csv_file . (strlen($count) ? (DOT . $count) : ''))) {
			$result[$csv_file . DOT . $count] = self::readCsvFile(
				$csv_file . (strlen($count) ? (DOT . $count) : ''), $errors
			);
			$count ++;
		}
		return $result;
	}

	//----------------------------------------------------------------------------------- readCsvFile
	/**
	 * @param $csv_file string
	 * @param $errors   string[]
	 * @return array
	 */
	public static function readCsvFile($csv_file, array &$errors = [])
	{
		$lines = [];
		$row   = 0;
		$f = fopen($csv_file, 'r');
		if ($f) while ($buf = fgetcsv($f)) {
			$row ++;
			if (($column = array_search('#REF!', $buf)) !== false) {
				$column ++;
				$replace  = new Replace([1 => $row, 2 => $column]);
				$errors[] = Loc::tr('unsolved reference at row $1 and column $2', $replace);
			}
			$lines[] = $buf;
		}
		fclose($f);
		return $lines;
	}

}
