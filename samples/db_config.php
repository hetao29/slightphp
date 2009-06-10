<?php
require_once("../SlightPHP.php");



SlightPHP::setDebug(true);
SlightPHP::setSplitFlag("-_");
SlightPHP::setPluginsDir("../plugins");	
SlightPHP::loadPlugin("SSmarty");

SlightPHP::loadPlugin("SDb");
SDb::setConfigFile("db.ini");


print_r(SDb::getConfig("main","main"));
print_r(SDb::getConfig("user","query"));
print_r(SDb::getConfig("blog","main"));
print_r(SDb::getConfig("blog","query"));

?>
