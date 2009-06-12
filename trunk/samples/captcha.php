<?php
require_once("../SlightPHP.php");
SlightPHP::setDebug(true);
//var_dump(SlightPHP::getDebug());
//SlightPHP::setSplitFlag("-_");
//var_dump(SlightPHP::getSplitFlag());
SlightPHP::setAppDir(".");
//var_dump(SlightPHP::getAppDir());
SlightPHP::setDefaultZone("zone");
//var_dump(SlightPHP::getDefaultzone());
SlightPHP::setDefaultClass("default");
//var_dump(SlightPHP::getDefaultClass());
SlightPHP::setDefaultMethod("entry");
//var_dump(SlightPHP::getDefaultMethod("entry"));
SlightPHP::setPluginsDir("../plugins");
//var_dump(SlightPHP::getPluginsDir());
//var_dump(SlightPHP::loadFile("../plugins/SError.class.php"));
//var_dump(SlightPHP::loadPlugin("SSmarty"));

if(SlightPHP::loadPlugin("SCaptcha")==false){
	die("loadPlugin error\n");
}

$cap = new SCaptcha();

$code = $cap->CreateImage();
if(SCaptcha::check($code)){
	error_log(true);
}else{
	error_log(false);
}

?>
