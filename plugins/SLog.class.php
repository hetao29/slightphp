<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/

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
