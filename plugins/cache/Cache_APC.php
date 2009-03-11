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
?>