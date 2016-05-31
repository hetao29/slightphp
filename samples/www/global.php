<?php
date_default_timezone_set("Asia/Shanghai");
define("ROOT",				dirname(__FILE__)."/../");
define("ROOT_WWW",			ROOT."/www");
define("ROOT_APP",			ROOT."/app");
define("ROOT_CONFIG",		ROOT."/config");
define("ROOT_SLIGHTPHP",	ROOT."/../");
require_once(ROOT_SLIGHTPHP."/vendor/autoload.php");
//{{{
spl_autoload_register(function($class){
	$file = SlightPHP::$appDir."/".str_replace("_","/",$class).".class.php";
	if(is_file($file)) return require_once($file);

});
//}}}
