<?php
/**
 * sample to test
 *
 * http://localhost/samples/index.php/zone/default/entry/a/b/c
 * http://localhost/samples/index.php/zone-default-entry-a-b-c.html
 *
 */
require_once("../SlightPHP.php");
$slight=new SlightPHP;
$slight->_debug=true;
$slight->splitFlag="-_";
$slight->appDir=".";
$slight->defaultZone = "zone";
$slight->pluginsDir="../plugins";
$slight->loadPlugin("SSmarty");
$slight->loadPlugin("SError");
$slight->loadPlugin("SJson");
if($slight->run()===false){
	//redirect to 404
	die("ERROR ENTRY");
}
?>
