<?php
/**
 * 这个例子演示如何使用cache
 * 更多APC请参看docs目录
 */
class index_cache{
	function pageEntry($inPath){
		/**
		 * 获取Cache Engine，SCache共支持三种缓存，分别是 File, APC, MemCache
		 */
		/**
		 * 文件cache例子 File Cache Samples
		 */
		$cache = SCache::getCacheEngine($cacheengine="File");
		if(!$cache){
			die("File cache engine not exists");
		}
		/**
		 * 初始参数，这里的dir为必要参数，depth表示目录深度
		 */
		$cache->init(array("dir"=>SlightPHP::$appDir."../cache","depth"=>3));
		/**
		 * 设置
		 */
		var_dump($cache->set("name",new stdclass));
		/**
		 * 获取
		 */
		var_dump($cache->get("name2"));
		var_dump($cache->get("name"));
		/**
		 * 删除
		 */
		var_dump($cache->del("name"));
		
		/**
		 * APC Cache Samples
		 */
		
		$cache = SCache::getCacheEngine($cacheengine="APC");
		if(!$cache){
			die("APC cache engine not exists");
		}
		
		var_dump($cache->set("name",new stdclass));
		var_dump($cache->get("name2"));
		var_dump($cache->get("name"));
		var_dump($cache->del("name"));
		
		/**
		 * Memcache Cache Samples
		 */
		/**
		 * 初始化参数，其实host为必要参数 
		 */
		SCache::useConfig("video");
		var_dump(SCache::set("name",new stdclass));
		var_dump(SCache::get("name2"));
		var_dump(SCache::get("name"));
		var_dump(SCache::del("name"));
	}
}
?>
