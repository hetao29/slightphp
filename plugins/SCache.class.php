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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cache/CacheObject.php");
/**
 * @package SlightPHP
 */
class SCache{
	static $engines=array("file","apc","memcache","memcached");
	
	static $_CacheConfigFile;
	static $_CachedefaultZone="default";
	static $_CacheConfigCache;
	/**
	 * @param string $engine enum("file","apc","memcache","memcached")
	 * @return CacheObject
	 */
	static function getCacheEngine($engine='memcache'){//,$server=array()){
		// global config
		/*
		global $CacheServer;
		if (empty($server) && isset($CacheServer)) {
			$server = $CacheServer;
		}
		*/
		// only support single cache server
		//$cindex = 0;
		$engine = strtolower($engine);
		if(!in_array($engine,SCache::$engines)){
			return false;
		}
		if($engine=="apc" && extension_loaded("apc")){
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cache/Cache_APC.php");
			return new Cache_APC;
		}elseif(($engine=="memcache" ||$engine=="memcached")&& extension_loaded("memcache")){
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cache/Cache_MemCache.php");
			return new Cache_MemCache;
		}elseif($engine=="file"){
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cache/Cache_File.php");
			return new Cache_File;
		}
	}
	
	static function setConfigFile($file){
		SCache::$_CacheConfigFile = $file;
	}
	static function getConfigFile(){
		return SCache::$_CacheConfigFile;
	}
	
	/**
	 * @param string $zone
	 * @param string $type	main|query
	 * @return array
	 */
	static function getConfig($zone){
		if(!SCache::$_CacheConfigFile){return;}
		
		$cache = &SCache::$_CacheConfigCache;
		if(empty($cache[$zone])){
			$file_data = parse_ini_file(realpath(SCache::$_CacheConfigFile),true);
			if(isset($file_data[$zone])){
					$db = $file_data[$zone];
			}elseif(isset($file_data[SCache::$_CachedefaultZone])){
					$db = $file_data[SCache::$_CachedefaultZone];
			}else{
					return;
			}
			foreach($db as $key =>$row){
				if(strpos($key,"main")!==false){
					$row = str_replace(":","=",$row);
					$row = str_replace(",","&",$row);
					parse_str($row,$out);
					if(!empty($out)){
						$cache[$zone][]=$out;
					}
				}
	
			}
		}
		if(isset($cache[$zone])){	
			$i =  array_rand($cache[$zone]);
			return $cache[$zone][$i];
		}else return array();
	}
	/**
	 * check key
	 */
	static private function checkKey($key) {
		return md5($key);
	}
	/**
	 * get
	 */
	static public function get($key) {
		return self::getCacheEngine()->get(self::checkKey($key));
	}
	/**
	 * set
	 */
	static public function set($key,$value,$exp=-1) {
		return self::getCacheEngine()->set(self::checkKey($key),$value,$exp);
	}
	/**
	 * del
	 */
	static public function del($key) {
		return self::getCacheEngine()->del(self::checkKey($key));
	}
}
?>
