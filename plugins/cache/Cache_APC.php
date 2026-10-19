<?php

/**
 * @package SlightPHP
 * @subpackage SCache
 */
namespace SlightPHP;
class Cache_APC extends CacheObject{
	function init($params=array()){}
	/**
	 * set cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $timestamp default:-1 forever
	 * @return boolean
	 */
	function set($key,$value,$timestamp=-1){
		return apc_store ($key,$value,$timestamp==-1?0:$timestamp);
	}
	/**
	 * get cache
	 *
	 * @param string $key
	 * @return mixed $result
	 */
	function get($key){
		return apc_fetch ($key );
	}
	/**
	 * delete cache
	 *
	 * @param string $key
	 * @return boolean
	 */
	function del($key){
		return apc_delete($key);
	}

}
