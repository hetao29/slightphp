<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 */
if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
require_once(SLIGHTPHP_PLUGINS_DIR."/SConfig.php");
require_once(SLIGHTPHP_PLUGINS_DIR."/cache/Cache_MemCache.php");
class SCache extends \SlightPHP\Cache_MemCache{
	public function __construct(){
		parent::__construct();
	}
	private static $_config;
	/**
	 * other cache engine
	 */
	private static $engines=array("file","apc");

	/**
	 * other cache engine
	 * @return class Cache_APC | Cache_File
	 */
	static function getCacheEngine($engine='file'){
		$engine = strtolower($engine);
		if(!in_array($engine,self::$engines)){
			return false;
		}
		if($engine=="apc" && extension_loaded("apc")){
			require_once(SLIGHTPHP_PLUGINS_DIR."/cache/CacheObject.php");
			require_once(SLIGHTPHP_PLUGINS_DIR."/cache/Cache_APC.php");
			return new Cache_APC;
		}elseif($engine=="file"){
			require_once(SLIGHTPHP_PLUGINS_DIR."/cache/CacheObject.php");
			require_once(SLIGHTPHP_PLUGINS_DIR."/cache/Cache_File.php");
			return new Cache_File;
		}
		return false;
	}
	
	static function setConfigFile($file){
		self::$_config = $file;
	}
	static function getConfig($zone=null){
		return SConfig::getConfig(self::$_config,$zone);
	}
	function useConfig($zone){
		parent::addServers(self::getConfig($zone));
	}
}
