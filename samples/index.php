<?php
/**
 * sample to test
 *
 * http://localhost/samples/index.php/zone/default/entry/a/b/c
 *
 */
error_reporting(0);
require_once("../SlightPHP.php");
$slight=new SlightPHP;
$slight->appDir=".";
$slight->defaultZone = "zone";
$slight->pluginsDir="../plugins";
$slight->_debug=true;
if($slight->run()===false){
	//redirect to 404
	die("ERROR ENTRY");
}
?>
