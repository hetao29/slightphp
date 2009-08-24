<?php
require_once("global.php");
SlightPHP::setDebug(true);
SlightPHP::setSplitFlag("-_");


//cacheengine = array("File","APC","MemCache");

/**
 * File Cache Samples
 */
$cache = SCache::getCacheEngine($cacheengine="File");
if(!$cache){
	die("File cache engine not exists");
}
$cache->init(array("dir"=>"cache","depth"=>3));
var_dump($cache->set("name",new stdclass));
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
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
$cache->init(array("host"=>"10.10.221.12","port"=>10001,"permanent"=>true));
var_dump($cache->set("name",new stdclass));
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
var_dump($cache->del("name"));

?>
