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
if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
require_once(SLIGHTPHP_PLUGINS_DIR."/SConfig.class.php");
class SRedis{
	public function __construct(){
	}
	private static $_config;
	private static $_rc;
	
	static function setConfigFile($file){
		self::$_config = $file;
	}
	static function getConfig($zone=null){
		return SConfig::getConfig(self::$_config,$zone);
	}
	public function useConfig($zone){
		$hosts=array();
		$cfg = self::getConfig($zone);
		if(empty($cfg))$cfg=self::getConfig("default");
		if(!empty($cfg)){
			foreach($cfg as $host){
			$hosts[]=$host->host.":".$host->port;
			}
		}
		$this->_rc = new RedisArray($hosts);
	}
	public function __call($name,$args){
		return call_user_method_array($name,$this->_rc,$args);
	}
}
