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
class SLog
{
	public static $CONSOLE = false;

	public static $LOG = false;

	public static $LOGFILE = "";
	/**
	 *
	 * @param string $LOGTYPE text/json
	 */
	public static $LOGTYPE="text";

	/**
	 * @var array
	 */
	public static $logLevelMap = array(
		LOG_DEBUG	=> 'DEBUG',
		LOG_INFO	=> 'INFO',
		LOG_NOTICE	=> 'NOTICE',
		LOG_WARNING	=> 'WARNING',
		LOG_ERR		=> 'ERR',
		LOG_CRIT	=> 'CRIT',
		LOG_ALERT	=> 'ALERT',
		LOG_EMERG	=> 'EMERG',
	);

	/**
	 * Write debug log
	 *
	 * @return int
	 */
	public static function debug(...$args){
		return self::writeArray(self::$logLevelMap[LOG_DEBUG], $args);
	}
	public static function trace(...$args){
		return self::writeArray(self::$logLevelMap[LOG_DEBUG], $args);
	}
	public static function notice(...$args){
		return self::writeArray(self::$logLevelMap[LOG_NOTICE], $args);
	}
	public static function warning(...$args){
		return self::writeArray(self::$logLevelMap[LOG_WARNING], $args);
	}
	public static function error(...$args){
		return self::writeArray(self::$logLevelMap[LOG_ERR], $args);
	}
	public static function fatal(...$args){
		return self::writeArray(self::$logLevelMap[LOG_CRIT], $args);
	}

	/**
	 * @param $args
	 */
	public static function write(...$args){
		return self::writeArray("", $args);
	}
	private static function writeArray($prefix, Array $args){
		$data="";
		if($prefix){
			$data.=" ".$prefix;
		}
		foreach($args as $info){
			if (is_object($info) || is_array($info)) {
				if(self::$LOGTYPE=="json"){
					$data .= " ".json_encode($info);
				}else{
					$data .= " ".var_export($info, true);
				}
			} elseif (is_bool($info)) {
				$data .= " ".($info ? "true" : "false");
			} else {
				$data .= " ".$info;
			}
		}
		$infoText = "[".date("Y-m-d H:i:s")."]".$data;

		if (!empty(self::$LOGFILE)) {
			error_log($infoText."\n", 3, self::$LOGFILE);
		} else {
			error_log($infoText);
		}

		if (self::$CONSOLE){
			if(PHP_SAPI=="cli"){
				echo $infoText."\n";
			}else{
				echo "<!--\n".$infoText."\n-->";
			}
		}
	}
}
