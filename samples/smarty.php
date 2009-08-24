<?php
require_once("global.php");
SlightPHP::setDebug(true);
//var_dump(SlightPHP::getDebug());
SlightPHP::setSplitFlag("-_");
//var_dump(SlightPHP::getSplitFlag());
SlightPHP::setAppDir(".");
//var_dump(SlightPHP::getAppDir());
SlightPHP::setDefaultZone("zone");
//var_dump(SlightPHP::getDefaultzone());
SlightPHP::setDefaultPage("smarty");
//var_dump(SlightPHP::getDefaultClass());
SlightPHP::setDefaultEntry("entry");
//var_dump(SlightPHP::getDefaultMethod("entry"));


if(($r=SlightPHP::run())===false){
	//redirect to 404
	die("ERROR ENTRY");
}else{
	echo $r;
}
?>
