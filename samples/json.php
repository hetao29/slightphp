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
if(SlightPHP::loadPlugin("SJson")==false){
	die("loadPlugin error\n");
}



$sm = new SJson;
$testObject = new stdclass;
$testObject->name="SlightPHP";
$testObject->value=array("min"=>1,"max"=>999);
print_r($tmp = $sm->encode($testObject));
echo "\n";
print_r($sm->decode($tmp));	
?>
