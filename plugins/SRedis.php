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
 * SRedis is base on php5-redis(phpredis https://github.com/phpredis/phpredis)
 * use RedisArray , docs is on https://github.com/phpredis/phpredis/blob/master/arrays.markdown#readme
 */
if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
require_once(SLIGHTPHP_PLUGINS_DIR."/SConfig.php");
class SRedis{
	private static $_config;
	/**
	 * 当前使用的resouce
	 */
	private static $_resource;
	/**
	 *
	 */
	private static $_resources;
	private static $_instances=array();

	public function __construct(){
	}

	static function setConfigFile($file){
		self::$_config = $file;
	}
	/**
	 * @param string $zone
	 * @return array
	 */
	static function getConfig($zone=null,$type="host"){
		$config = SConfig::getConfig(self::$_config,$zone);
		if(isset($config->$type)){
			return $config->$type;
		}elseif(isset($config->host)){
			return $config->host;
		}
		return;
	}
	/**
	 * 切换配置文件
	 * @param string $zone
	 * @return array
	 */
	static function useConfig($zone,$type="host"){
		$key = $zone.":".$type;
		if(isset(self::$_instances[$key])){
			return self::$_instances[$key];
		}else{
			self::$_instances[$key] = new self;
		}
		$hosts=array();
		$options=array();
		$config = self::getConfig($zone,$type);
		if(empty($config)){
			trigger_error("the redis hosts is not set in config file(".self::$_config.")");
			return false;
		}
		if(is_array($config)){
			$hosts=$config;
		}else{
			$hosts[]=$config;
		}
		$config = self::getConfig($zone,"options");
		if(!empty($config)){
			if(is_object($config)){
				foreach ($config as $k=>$v){
					$options[$k]=$v;
				}
			}
		}
		self::$_resource = self::$_resources[$key] = new RedisArray($hosts,$options);
		return self::$_instances[$key];
	}
	public function __call($name,$args){
		try{
			if(self::$_resource) return call_user_func_array(array(self::$_resource,$name),$args);
		}catch(RedisException $e){
			self::$_instances=null;
			self::$_resources=null;
			self::$_resource=null;
			trigger_error($e);
		}
		return false;
	}
	public static function __callStatic($name,$args){
		try{
			if(self::$_resource) return call_user_func_array(array(self::$_resource,$name),$args);
		}catch(RedisException $e){
			self::$_instances=null;
			self::$_resources=null;
			self::$_resource=null;
			trigger_error($e);
		}
		return false;
	}
}
