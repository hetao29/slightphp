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
$slight->setDebug(true);
$slight->setSplitFlag("-_");
$slight->setDefaultZone("zone");
$slight->setDefaultClass("smarty");
$slight->setAppDir(".");
$slight->setPluginsDir("../plugins");
$slight->loadPlugin("SSmarty");
$slight->loadPlugin("SError");
$slight->loadPlugin("SJson");
if($slight->run()===false){
	//redirect to 404
	die("ERROR ENTRY");
}
?>
