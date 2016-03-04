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
	public static function encode($object,$options = 0){
		return json_encode($object, $options);
	}
	/**
	 * decode string to json object
	 *
	 * @param	string $str
	 * @return mixed object
	 * @access public
	 */

	public static function decode($str, $options = 0){
		return json_decode($str,$options);
	}
}

