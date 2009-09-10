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
 * @subpackage samples
 */
/**
 * 这个例子演示如何使用cache
 * 更多APC请参看docs目录
 */
require_once("global.php");

/**
 * 获取Cache Engine，SCache共支持三种缓存，分别是 File, APC, MemCache
 */
$cache = SCache::getCacheEngine($cacheengine="File");

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
$cache->init(array("dir"=>"cache","depth"=>3));
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

$cache = SCache::getCacheEngine($cacheengine="Memcache");
if(!$cache){
	die("Memcache cache engine not exists");
}
/**
 * 初始化参数，其实host为必要参数 
 */
$cache->init(array("host"=>"10.10.221.12","port"=>10001,"permanent"=>true));
var_dump($cache->set("name",new stdclass));
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
var_dump($cache->del("name"));

?>
