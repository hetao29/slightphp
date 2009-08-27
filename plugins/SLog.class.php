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

class SLog{
	/**
	 * 
	 */
	static $CONSOLE=true;
	/**
	 * 
	 */
	static $LOG=false;
	/**
	 * 
	 */
	static $LOGFILE="";

	/**
	  * params mix $info
	  * return boolean
	  **/
	public static function write($info){
			if(is_object($info) || is_array($info)){
					$info_text = var_export($info,true);
			}elseif(is_bool($info)){
					$info_text = $info?"true":"false";
			}else{
					$info_text = $info;
			}
			$info_text = "[".date("Y-m-d H:i:s")."] ".$info_text."\r\n";
			if(!empty(SLog::$LOGFILE)){
				error_log($info_text,3,SLog::$LOGFILE);
			}else error_log($info_text);
			if(SLog::$CONSOLE)		echo "<!--\n".$info_text."\n-->";
	}
}
?>
