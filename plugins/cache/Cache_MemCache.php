<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/


class Cache_MemCache extends CacheObject{
	/**
	 * @var string host
	 */
	var $host;
	/**
	 * @var int $port
	 */

	var $port="11211";
	 

	/**
	 * @var boolean $permanent default false
	 */

	var $permanent=false;
	/**
	 * @var array $globals
	 */
	static $globals;
	/**
	 * init
	 *
	 * @param array $params array("include","host","port","permanent")
	 */
	function init($params=array()){
		foreach($params as $key=>$value){
			$this->$key = $value;
		}
	}
	/**
	 * set cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $timestamp default:-1 forever
	 * @return boolean
	 */
	function set($key,$value,$timestamp=-1){
		$memcache = $this->_connect();
		if($memcache){
			return memcache_set($memcache, $key, $value, MEMCACHE_COMPRESSED , $timestamp==-1?0:$timestamp);
		}
		return false;
	}
	/**
	 * get cache
	 *
	 * @param mixed $key
	 * @return mixed $result
	 */
	function get($key){
		$memcache = $this->_connect();
		if($memcache){
			return memcache_get($memcache, $key);
		}
		return false;
	}
	/**
	 * delete cache
	 *
	 * @param string $key
	 * @return boolean
	 */
	function del($key){
		$memcache = $this->_connect();
		if($memcache){
			return memcache_delete($memcache,$key);
		}
		return false;
	}
	
	/**
	 * @return boolean
	 */
	private function _connect(){
		$s_str=md5($this->host.":".$this->port);
		if(!$this->permanent || empty(Cache_MemCache::$globals[$s_str])){
			return Cache_MemCache::$globals[$s_str] = memcache_connect($this->host, $this->port);
		}else{
			return Cache_MemCache::$globals[$s_str];
		}
		return false;
	}

}
?>
