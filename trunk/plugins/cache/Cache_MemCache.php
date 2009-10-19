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
 * @subpackage SCache
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
	
	function __construct($params=array()) {
		$this->init($params);
	}
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