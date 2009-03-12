<?php
require_once("../SlightPHP.php");

$slight=new SlightPHP;
$slight->setDebug(true);
$slight->setSplitFlag("-_");
$slight->setDefaultZone("zone");
$slight->setAppDir(".");
$slight->setPluginsDir("../plugins");


var_dump($slight->loadPlugin("SSmarty"));
$sm = new SSmarty;
print_r($sm);
?>
