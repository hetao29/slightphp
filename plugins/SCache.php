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
/** Memcache 缓存入口，提供文件和 APC 引擎的工厂方法。 */
class SCache extends \SlightPHP\Cache_MemCache{
	/** 创建 Memcache 缓存客户端。 */
	public function __construct(){
		parent::__construct();
	}
	private static $_config;
	/**
	 * other cache engine
	 */
	private static $engines=array("file","apc");

	/**
	 * 创建指定缓存引擎。当前实现使用全局类名；若应用未定义对应别名，
	 * 加载的 SlightPHP 命名空间类无法被找到，将抛出 Error。
	 * @param string $engine 引擎名称：file 或 apc。
	 * @return \SlightPHP\Cache_APC|\SlightPHP\Cache_File|false 有相应类别名时返回实例；未知引擎或 APC 扩展不可用时返回 false。
	 * @throws Error 对应全局类名不存在时。
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
	
	/**
	 * @param string $file 缓存服务器配置文件路径。
	 * @return void
	 */
	static function setConfigFile($file){
		self::$_config = $file;
	}
	/**
	 * @param string|null $zone 配置区域。
	 * @return mixed 区域配置或完整配置。
	 */
	static function getConfig($zone=null){
		return SConfig::getConfig(self::$_config,$zone);
	}
	/**
	 * @param string $zone 传给 addServers 的配置区域名。
	 * @return void
	 */
	function useConfig($zone){
		parent::addServers(self::getConfig($zone));
	}
}
