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
$slight->loadPlugin("SError");

//cacheengine = array("File","APC","MemCache");

/**
 * File Cache Samples
 */
/*
$cache = SCache::getCacheEngine($cacheengine="File");
if(!$cache){
	die("File cache engine not exists");
}
$cache->init(array("dir"=>"cache","depth"=>3));
var_dump($cache->set("name",new stdclass));
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
var_dump($cache->del("name"));

*/
/**
 * APC Cache Samples
 */

$cache = SCache::getCacheEngine($cacheengine="APC");
if(!$cache){
	die("File cache engine not exists");
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
	die("File cache engine not exists");
}
$cache->init(array("host"=>"10.10.221.12","port"=>10001,"permanent"=>true));
var_dump($cache->set("name",new stdclass));
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
var_dump($cache->del("name"));

?>
