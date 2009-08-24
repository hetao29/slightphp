<?php
require_once("global.php");
SlightPHP::setDebug(true);


$sm = new SJson;
$testObject = new stdclass;
$testObject->name="SlightPHP";
$testObject->value=array("min"=>1,"max"=>999);
print_r($tmp = $sm->encode($testObject));
echo "\n";
print_r($sm->decode($tmp));	
?>
