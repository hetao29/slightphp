<?php
require_once("../SlightPHP.php");

$slight=new SlightPHP;

$slight->setDebug(true);
$slight->setSplitFlag("-_");
$slight->setDefaultZone("zone");
$slight->setAppDir(".");
$slight->setPluginsDir("../plugins");


if($slight->loadPlugin("SJson")==false){
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
