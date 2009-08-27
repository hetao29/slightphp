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