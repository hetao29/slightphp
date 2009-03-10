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
$slight->loadPlugin("SCache");
$a = new SCache;
print_r($a);
?>
