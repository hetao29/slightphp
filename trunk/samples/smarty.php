<?php
require_once("../SlightPHP.php");
SlightPHP::setDebug(true);
//var_dump(SlightPHP::getDebug());
SlightPHP::setSplitFlag("-_");
//var_dump(SlightPHP::getSplitFlag());
SlightPHP::setAppDir(".");
//var_dump(SlightPHP::getAppDir());
SlightPHP::setDefaultZone("zone");
//var_dump(SlightPHP::getDefaultzone());
SlightPHP::setDefaultClass("smarty");
//var_dump(SlightPHP::getDefaultClass());
SlightPHP::setDefaultMethod("entry");
//var_dump(SlightPHP::getDefaultMethod("entry"));
SlightPHP::setPluginsDir("../plugins");
//var_dump(SlightPHP::getPluginsDir());
//var_dump(SlightPHP::loadFile("../plugins/SError.class.php"));
SlightPHP::loadPlugin("SSmarty");
SlightPHP::loadPlugin("SError");
SlightPHP::loadPlugin("SJson");


if(SlightPHP::run()===false){
	//redirect to 404
	die("ERROR ENTRY");
}
?>
