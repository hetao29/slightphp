<?php
$slight=new SlightPHP;
$slight->appDir="..";
$slight->pluginsDir="../plugins";
$slight->_debug=true;
var_dump($slight->loadPlugin("Smarty"));
$sm = new Smarty;
print_r($sm);
?>
