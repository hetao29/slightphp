<?php
require_once("../SlightPHP.php");

$slight=new SlightPHP;
$slight->appDir="..";
$slight->pluginsDir="../plugins";
$slight->_debug=true;

/*
drop table if exists test;
CREATE TABLE `test` (
  `id` int not null primary key auto_increment,
  `name` varchar(30) default NULL,
  `password` varchar(30) default NULL,
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
*/
$slight->loadPlugin("SDb");
$db = new SDb("localhost","root","huoqiabc","test");
//插入记录
print_r($db->insert($table = "test",$items=array("name"=>"testName","password"=>"testPassword")));
//检索一个
print_r($db->selectOne($table = "test",$condition=array(),$items=array("name")));
//按条件检索一个
print_r($db->selectOne($table = "test",$condition=array("id"=>1),$items=array("*")));
//搜索全部
$db->setLimit(-1);
print_r($db->select($table = "test",$condition=array(),$items=array("*")));
//按页检索
$db->setPage(2);
$db->setLimit(5);
//是否算总数
$db->setCount(true);
print_r($db->select($table = "test",$condition=array(),$items=array("*")));

?>
