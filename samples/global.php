<?php

//{{{ define plugin 
define("PLUGINS_DIR",dirname(__FILE__)."/../plugins");
function __autoload($class){
	if($class{0}=="S"){
		$file = PLUGINS_DIR."/$class.class.php";
	}else{
		$file = SlightPHP::$appDir."/".str_replace("_","/",$class).".class.php";
	}
	if(file_exists($file)) return require_once($file);
}
spl_autoload_register('__autoload');
//}}}

//define WWW_ROOT
define("WWW_ROOT",dirname(__FILE__));
require_once("../SlightPHP.php");
?>
