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
	/**
	 * @param string $engine enum("file","apc","memcache","memcached")
	 * @return CacheObject
	 */
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
