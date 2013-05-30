<?php
/**
 * sample to test
 *
 * http://localhost/samples/www/index.php/zone/default/entry/a/b/c
 * http://localhost/samples/www/index.php/zone-default-entry-a-b-c.html
 *
 */
require_once("global.php");


SlightPHP::setDebug(true);
SlightPHP::setAppDir(ROOT_APP);
SlightPHP::setDefaultZone("index");
SlightPHP::setDefaultPage("main");
SlightPHP::setDefaultEntry("entry");
SlightPHP::setSplitFlag("-_.");

//{{{
SDb::setConfigFile(ROOT_CONFIG. "/db.ini");
SRoute::setConfigFile(ROOT_CONFIG."/route.ini");
//}}}
if(($r=SlightPHP::run())===false){
	echo("404 error");
}elseif(is_object($r)){
	var_dump($r);
}else{
	echo($r);
}
