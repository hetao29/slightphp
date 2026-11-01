<?php

/**
 * @package SlightPHP
 * @subpackage SCache
 */
namespace SlightPHP;
/** 通过旧版 APC 扩展存取缓存。 */
class Cache_APC extends CacheObject{
	/**
	 * APC 无需初始化参数。
	 * @param array $params 忽略的配置。
	 * @return void
	 */
	function init($params=array()){}
	/**
	 * 保存缓存值。
	 *
	 * @param string $key 缓存键。
	 * @param mixed $value 缓存值。
	 * @param int $timestamp 有效期秒数；-1 或 0 表示不过期。
	 * @return bool 是否保存成功。
	 */
	function set($key,$value,$timestamp=-1){
		return apc_store ($key,$value,$timestamp==-1?0:$timestamp);
	}
	/**
	 * 读取缓存值。
	 *
	 * @param string $key 缓存键。
	 * @return mixed 缓存值；未命中时为 false。
	 */
	function get($key){
		return apc_fetch ($key );
	}
	/**
	 * 删除缓存值。
	 *
	 * @param string $key 缓存键。
	 * @return bool 是否删除成功。
	 */
	function del($key){
		return apc_delete($key);
	}

}
