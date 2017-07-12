<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/

/**
 * @package SlightPHP
 */
class SJson{
	/**
	 * encode object to json
	 *
	 * @param    mixed  $object
	 * @return   string  json
	 * @access   public
	 */
	public static function encode($object, $options = 0, $depth = 512){
		return json_encode($object, $options, $depth);
	}
	/**
	 * decode string to json object
	 *
	 * @param	string $str
	 * @return mixed object
	 * @access public
	 */
	public static function decode($str, $assoc = false, $depth = 512, $options = 0){
		return json_decode($str, $assoc, $depth, $options);
	}
	/**
	 * get last json encode/decode error code
	 * @return int
	 * @access public
	 */
	public static function error(){
		return json_last_error();
	}
	/**
	 * get last json encode/decode error msg
	 * @return string
	 * @access public
	 */
	public static function errorMsg(){
		if (!function_exists('json_last_error_msg')){
			$ERRORS = array(
				JSON_ERROR_NONE => 'No error',
				JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
				JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
				JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
				JSON_ERROR_SYNTAX => 'Syntax error',
				JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);

			$error = json_last_error();
			return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
		}
		return json_last_error_msg();
	}
}
