<?php
/**
 * sample to test
 *
 * http://localhost/samples/index.php/zone/default/entry/a/b/c
 * http://localhost/samples/index.php/zone-default-entry-a-b-c.html
 *
 */
/* use static */
//{{{

require_once("global.php");

/*echo error info*/
SlightPHP::setDebug(true);

SlightPHP::setAppDir("app");
SlightPHP::setDefaultZone("index");
SlightPHP::setDefaultPage("main");
SlightPHP::setDefaultEntry("entry");

SDb::setConfigFile(SlightPHP::$appDir . "/index/db.ini.php");

SlightPHP::setSplitFlag("-_.");
#SError::$CONSOLE= true;
if(($r=SlightPHP::run())===false){
	die("404 error");
}else{
	echo $r;
}
?>
