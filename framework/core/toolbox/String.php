<?php

class String
{

	//---------------------------------------------------------------------------------------- $value
	/**
	 * @var string
	 */
	public $value;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $value string
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}

	//------------------------------------------------------------------------------------ __toString
	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->value;
	}

	//----------------------------------------------------------------------------------------- first
	/**
	 * First element of a separated string
	 *
	 * @return string
	 */
	public function first()
	{
		foreach (array(":", ".", "-", ",") as $char) {
			if (strpos($this->value, $char) !== false) {
				return substr($this->value, 0, strpos($this->value, $char));
			}
		}
		return $this->value;
	}

	//------------------------------------------------------------------------------------------ last
	/**
	 * Last element of a separated string
	 *
	 * @param $count integer
	 * @return string
	 */
	public function last($count = 1)
	{
		foreach (array(":", ".", "-", ",") as $char) {
			if (strrpos($this->value, $char) !== false) {
				return rLastParse($this->value, $char, $count, true);
			}
		}
		return $this->value;
	}

	//----------------------------------------------------------------------------------------- lower
	/**
	 * @return string
	 */
	public function lower()
	{
		return strtolower($this->value);
	}

	//----------------------------------------------------------------------------------------- short
	/**
	 * @return string
	 */
	public function short()
	{
		return Namespaces::shortClassName($this->value);
	}

	//--------------------------------------------------------------------------------------- twoLast
	/**
	 * The two last elements of a separated string
	 *
	 * @todo remove and replace .twoLast by .last(2) (needs debugging of Html_Template)
	 * @return string
	 */
	public function twoLast()
	{
		return $this->last(2);
	}

	//--------------------------------------------------------------------------------------- ucfirst
	/**
	 * @return string
	 */
	public function ucfirst()
	{
		return ucfirst($this->value);
	}

	//--------------------------------------------------------------------------------------- ucwords
	/**
	 * @return string
	 */
	public function ucwords()
	{
		return ucwords($this->value);
	}

	//----------------------------------------------------------------------------------------- upper
	/**
	 * @return string
	 */
	public function upper()
	{
		return strtoupper($this->value);
	}

}

//----------------------------------------------------------------------------- function lLastParse

/**
 * Renvoie la partie de chaine à gauche de la dernière occurence du séparateur
 *
 * @param $str string
 * @param $sep string
 * @param $cnt int
 * @param $complete_if_not bool
 * @return string
 */
function lLastParse($str, $sep, $cnt = 1, $complete_if_not = true)
{
	if ($cnt > 1) {
		$str = lLastParse($str, $sep, $cnt - 1);
	}
	$i = strrpos($str, $sep);
	if ($i === false) {
		return $complete_if_not ? $str : "";
	}
	else {
		return substr($str, 0, $i);
	}
}

//--------------------------------------------------------------------------------- function lParse
/**
 * Renvoie la partie de chaine à gauche de la première occurence du séparateur
 *
 * @param $str string
 * @param $sep string
 * @param $cnt int
 * @param $complete_if_not bool
 * @return string
 */
function lParse($str, $sep, $cnt = 1, $complete_if_not = true)
{
	$i = -1;
	while ($cnt--) {
		$i = strpos($str, $sep, $i + 1);
	}
	if ($i === false) {
		return $complete_if_not ? $str : "";
	}
	else {
		return substr($str, 0, $i);
	}
}

//--------------------------------------------------------------------------- function maxRowLength
/**
 * Renvoie la plus grande longueur de ligne d'un texte dont les lignes sont séparées par "\n"
 *
 * @param $str string
 * @return int
 */
function maxRowLength($str)
{
	$length = 0;
	$rows = explode("\n", $str);
	foreach ($rows as $row) {
		if (strlen($row) > $length) {
			$length = strlen($row);
		}
	}
	return $length;
}

//--------------------------------------------------------------------------------- function mParse
/**
 * Renvoie la partie de la chaîne située entre le délimiteur de début et le délimiteur de fin
 * Si le délimiteur est un tableau, les délimiteurs seront recherchés successivement.
 *
 * @example echo mParse("il a mangé, a bu, a digéré", array(",", "a "), ",")
 *          recherchera ce qui entre le "a " qui est après "," et le "," qui suit,
 *          et affichera "bu"
 * @param $str string
 * @param $begin_sep mixed  array, string
 * @param $end_sep mixed    array, string
 * @param $cnt int
 * @return string
 */
function mParse($str, $begin_sep, $end_sep, $cnt = 1)
{
	// if $begin_sep is an array, rParse each $begin_sep element
	if (is_array($begin_sep)) {
		$sep = array_pop($begin_sep);
		foreach ($begin_sep as $beg) {
			$str = rParse($str, $beg, $cnt);
			$cnt = 1;
		}
		$begin_sep = $sep;
	}
	// if $end_sep is an array, lParse each $end_sep element, starting from the last one
	if (is_array($end_sep)) {
		$end_sep = array_reverse($end_sep);
		$sep = array_pop($end_sep);
		foreach ($end_sep as $end) {
			$str = lParse($str, $end);
		}
		$end_sep = $sep;
	}
	return lParse(rParse($str, $begin_sep, $cnt), $end_sep);
}

//----------------------------------------------------------------------------- function rLastParse
/**
 * Renvoie la partie de chaine à droite de la dernière occurence du séparateur
 *
 * @param $str string
 * @param $sep string
 * @param $cnt int
 * @param $complete_if_not bool
 * @return string
 */
function rLastParse($str, $sep, $cnt = 1, $complete_if_not = false)
{
	$i = strrpos($str, $sep);
	while (($cnt > 1) && ($i !== false)) {
		$i = strrpos(substr($str, 0, $i), $sep);
		$cnt--;
	}
	if ($i === false) {
		return $complete_if_not ? $str : "";
	}
	else {
		return substr($str, $i + strlen($sep));
	}
}

//------------------------------------------------------------------------------- function rowCount
/**
 * Renvoie le nombre de lignes dans un texte dont les lignes sont séparées par "\n"
 *
 * @param $str string
 * @return string
 */
function rowCount($str)
{
	return substr_count($str, "\n");
}

//--------------------------------------------------------------------------------- function rParse
/**
 * Renvoie la partie de chaine à droite de la première occurence du séparateur
 *
 * @param $str             string
 * @param $sep             string
 * @param $cnt             integer
 * @param $complete_if_not boolean
 * @return string
 */
function rParse($str, $sep, $cnt = 1, $complete_if_not = false)
{
	$i = -1;
	while ($cnt--) {
		$i = strpos($str, $sep, $i + 1);
	}
	if ($i === false) {
		return $complete_if_not ? $str : "";
	}
	else {
		return substr($str, $i + strlen($sep));
	}
}

//------------------------------------------------------------------------------------------ isWord
/**
 * Test is the string put in parameter like a word
 * @param $word The string to test
 * @return int Return 0 if it's not a word.
 */
function isWord($word)
{
	return preg_match("#[a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ]#", $word);
}

//--------------------------------------------------------------------------------------- cleanWord
/**
 * Clean a word put in parameter, this delete all character who don't have a place in a current word.
 * @param $word The word that clean.
 * @return string Return the clean word.
 * @example
 * cleanWord("Albert, ") => return "Albert"
 * cleanWord(" list : ") => return "list"
 */
function cleanWord($word){
	return preg_replace("#[^a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ\-\'\_\\\/]#", "", $word);
}
