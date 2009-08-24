<?php
require_once("../SlightPHP.php");
define("PLUGINS_DIR","../plugins");
function __autoload($class){
	if($class{0}=="S"){
		require_once(PLUGINS_DIR."/$class.class.php");
	}
}
?>