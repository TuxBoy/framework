<?php
namespace SAF\Framework;
use AopJoinpoint;

/** @noinspection PhpIncludeInspection */
require_once "framework/core/toolbox/Aop.php";
/** @noinspection PhpIncludeInspection */
require_once "framework/core/toolbox/Plugin.php";

abstract class Html_Session implements Plugin
{

	//----------------------------------------------------------------------------------- $registered
	private static $registered = false;

	//--------------------------------------------------------------------------------- postSessionId
	/**
	 * Send session ID as a POST var
	 *
	 * This is done at end of html templates parsing.
	 *
	 * @param $joinpoint AopJoinpoint
	 */
	public static function postSessionId(AopJoinpoint $joinpoint)
	{
		if (!$joinpoint->getObject()->getParameter("is_included")) {
			$content = $joinpoint->getReturnedValue();
			// $_POST
			$content = str_replace(
				"</form>",
				"<input type=\"hidden\" name=\"" . session_name() . "\" value=\"" . session_id() . "\">"
					. "</form>",
				$content
			);
			// $_GET
			$links = array("action=", "href=", "location=");
			$quotes = array("'", '"');
			foreach ($links as $link) {
				foreach ($quotes as $quote) {
					$i = 0;
					while (($i = strpos($content, $link . $quote, $i)) !== false) {
						$i += strlen($link) + 1;
						$j = strpos($content, $quote, $i);
						$old = substr($content, $i, $j - $i);
						$sep = strpos($link, "?") ? "&" : "?";
						$add = session_name() . "=" . session_id();
						if (
							(substr($old, 0, 1) != "#")
							&& (strpos($old, $add) === false)
							&& (strpos($old, "://") === false)
						) {
							$content = substr($content, 0, $j) . $sep . $add . substr($content, $j);
							$i = $j + strlen($sep . $add) + 1;
						}
						else {
							$i = $j + 1;
						}
					}
				}
			}
			// done
			$joinpoint->setReturnedValue($content);
		}
	}

	//----------------------------------------------------------------------------------- useTransSid
	public static function useTransSid()
	{
		ini_set("session.use_trans_sid", true);
	}

	//-------------------------------------------------------------------------------------- register
	/**
	 * always add session id at end of html documents parsing
	 */
	public static function register()
	{
		if (!self::$registered) {
			self::$registered = true;
			ini_set("session.use_cookies", false);
			ini_set("session.use_only_cookies", false);
			Aop::add("before", "session_start()", array(__CLASS__, "useTransSid"));
		}
		//
		/*
		Aop::add("after",
			'SAF\Framework\Html_Template->parse()',
			array(__CLASS__, "postSessionId")
		);
		*/
	}

}
