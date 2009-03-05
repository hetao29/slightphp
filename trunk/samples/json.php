<?php
require_once("../SlightPHP.php");

$slight=new SlightPHP;
//$slight->appDir="..";
$slight->pluginsDir="../plugins";
$slight->_debug=true;
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
