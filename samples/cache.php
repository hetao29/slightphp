<?php
/**
 * sample to test
 *
 * http://localhost/samples/index.php/zone/default/entry/a/b/c
 * http://localhost/samples/index.php/zone-default-entry-a-b-c.html
 *
 */
require_once("../SlightPHP.php");
$slight=new SlightPHP;
$slight->_debug=true;
$slight->splitFlag="-_";
$slight->appDir=".";
$slight->defaultZone = "zone";
$slight->pluginsDir="../plugins";
$slight->loadPlugin("SCache");

/**
 * File Cache Samples
 */
//CacheEngine = array("File","APC","MemCache");
echo phpversion();
var_dump($result = SCache::getCacheEngine($cacheengine="File"));
var_dump($result = SCache::getCacheEngine($cacheengine="apc"));
var_dump($result = SCache::getCacheEngine($cacheengine="memcache"));
//设置文件存放位置
/*$cache->setCacheFileDir($dir=);
//设置目录深度
$cache->setCacheFileDepth($depth=3);
//缓存$timestamp时间是秒，-1表永久，默认是一年
$cache->set($key,$value,$timestamp=);
$cache->get($key);
$cache->del($key);

//缓存整个页面
$cache->pageCache($timestamp);

*/
/**
 * APC Cache Samples
 */
//CacheEngine = array("File","APC","MemCached");
/*
$result = $cache->setCacheEngine($cacheengine="APC");
//设置文件存放位置
$cache->setCacheFileDir($dir=);
//设置目录深度
$cache->setCacheFileDepth($depth=3);
//缓存$timestamp时间是秒，-1表永久，默认是一年
$cache->set($key,$value,$timestamp=);
$cache->get($key);
$cache->del($key);
*/
?>
