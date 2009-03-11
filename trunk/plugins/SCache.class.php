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


require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."cache/CacheObject.php");
class SCache{
	static $engines=array("file","apc","memcache","memcached");
	static function getCacheEngine($engine){
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
}
?>