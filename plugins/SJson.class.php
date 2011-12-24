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

//if(function_exists("json_encode")):
if(false):
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
		function encode($object){
			return json_encode($object);
		}
		/**
		 * decode string to json object
		 *
		 * @param	string $str
		 * @return mixed object
		 * @access public
		 */

		function decode($str,$assoc=false){
			return json_decode($str,$assoc);
		}
	}

else:
	if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
	require_once(SLIGHTPHP_PLUGINS_DIR."/json/json.php");
	class SJson{
	   /**
		* encode object to json
		*
		* @param    mixed  $object
		* @return   string  json
		* @access   public
		*/
		function encode($object){
			$json = new Json;
			return $json->encode($object);
		}
		/**
		 * decode string to json object
		 *
		 * @param	string $str
		 * @return mixed object
		 * @access public
		 */

		function decode($str,$assoc=false){
			$json = new Json;
			return $json->decode($str);
		}
	}
endif;
?>
