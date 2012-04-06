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
require_once(SLIGHTPHP_PLUGINS_DIR."/rest/Rest_Http.class.php");
class SRestClient extends Rest_Http{
	private static $_config;
	static function setConfigFile($file){
		self::$_config = new SConfig;
		self::$_config->setConfigFile($file);
	}
	static function getConfigFile(){
		return self::$_config->getConfigFile();
	}
	/**
	 * @param string $zone
	 * @param string $type	main|query
	 * @return array
	 */
	static function getConfig($zone){
		return self::$_config->listConfig($zone,".*");
	}
	static function useConfig($zone){
		self::addServers(self::getConfig($zone));
	}
	private static $_requests=array();
	static function addRequest($zone="default",$parameters=array(),$key=null,$method="GET"){
		self::useConfig($zone);
		self::addServers(self::getConfig($zone));
		$para = "";
		if(!empty($parameters)){
			$para = http_build_query($parameters);
		}

		$hash = $zone.$para.$method.$key;
		$server = self::getServer($hash);
		$url = "http://".$server['host']."/".$server['path'];
		$request = array("url"=>$url,"parameters"=>$parameters,"timeout"=>$server['timeout'],"method"=>$method);
		if(empty($key)){
			array_push(self::$_requests,$request);
		}else{
			self::$_requests[$key]=$request;
		}
		return self::$_requests;
	}
	static function request(){
		require_once(SLIGHTPHP_PLUGINS_DIR."/SHttp.class.php");
		$http = new SHttp;
		return $http->getArray(self::$_requests);
	}
	static function reset(){
		self::$_requests = array();
	}
}
