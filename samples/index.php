<?php
/**
 * sample to test
 *
 * http://localhost/samples/index.php/zone/default/entry/a/b/c
 * http://localhost/samples/index.php/zone-default-entry-a-b-c.html
 *
 */
require_once("global.php");

define("APP_ROOT",WWW_ROOT."/app");

SlightPHP::setDebug(true);
SlightPHP::setAppDir(APP_ROOT);
SlightPHP::setDefaultZone("index");
SlightPHP::setDefaultPage("main");
SlightPHP::setDefaultEntry("entry");
SlightPHP::setSplitFlag("-_.");

SDb::setConfigFile(APP_ROOT. "/index/db.ini.php");

#SError::$CONSOLE= true;
if(($r=SlightPHP::run())===false){
	echo("404 error");
}else{
	echo($r);
}
?>
