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
	private static $hosts=array();
	private static $options=array();
	private static $engine="";
	private static $_configFile;
	//
	private $_redis;
	/**
	 * 当前使用的resouce
	 */
	public function __construct(array $hosts,array $options,$engine=""){
		if($engine=="cluster"){
			//https://github.com/phpredis/phpredis/blob/develop/cluster.md
			$this->_redis = new RedisCluster(NULL,$hosts,
				$options['timeout']??0,
				$options['read_timeout']??0,
				$options['persistent']??false,
				$options['auth']??NULL,
				$options['context']??NULL
			);
		}elseif($engine=="sentinel"){
			//https://github.com/phpredis/phpredis/blob/develop/sentinel.md
			$this->_redis = new RedisArray(self::$hosts,self::$options);
		}else{
			//https://github.com/phpredis/phpredis/blob/develop/arrays.md
			$this->_redis = new RedisArray(self::$hosts,self::$options);
		}
		return $this->_redis;
	}

	static function setConfigFile($file){
		self::$_configFile = $file;
	}
	/**
	 * @param string $zone
	 * @return array
	 */
	static function getConfig($zone=null,$type="host"){
		$config = SConfig::getConfig(self::$_configFile,$zone);
		if(isset($config->$type)){
			return $config->$type;
		}elseif(isset($config->host)){
			return $config->host;
		}
		return NULL;
	}
	/**
	 * 切换配置文件
	 * @param string $zone
	 * @return array
	 */
	static function useConfig($zone,$type="host"){
		$config = self::getConfig($zone,$type);
		if(empty($config)){
			trigger_error("the redis hosts is not set in config file(".self::$_configFile.")");
			return false;
		}
		self::$hosts=[];
		self::$options=[];
		if(is_array($config)){
			self::$hosts=$config;
		}else{
			self::$hosts[]=$config;
		}
		self::$engine = self::getConfig($zone,"engine");
		$config = self::getConfig($zone,"options");
		if(!empty($config)){
			if(is_object($config)){
				foreach ($config as $k=>$v){
					self::$options[$k]=$v;
				}
			}
		}
		return new self(self::$hosts,self::$options,self::$engine);
	}
	public function __call($name,$args){
		try{
			return call_user_func_array(array($this->_redis,$name),$args);
		}catch(RedisException $e){
			trigger_error($e);
		}
		return false;
	}
	public static function __callStatic($name,$args){
		try{
			$redis = new self(self::$hosts,self::$options,self::$engine);
			return call_user_func_array(array($redis,$name),$args);
		}catch(RedisException $e){
			trigger_error($e);
		}
		return false;
	}
}
