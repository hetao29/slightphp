<?php

define("SlightPHP_ROOT",dirname(__FILE__));

define("SlightPHP_VERSION","0.1");
define("SlightPHP_AUTHOR","HETAL");
define("SlightPHP_WEBSITE","WWW.SlightPHP.com");

define("SlightPHP_DIR_LIB",SlightPHP_ROOT.DIRECTORY_SEPARATOR."lib");
define("SlightPHP_DIR_LOG",SlightPHP_ROOT.DIRECTORY_SEPARATOR."log".DIRECTORY_SEPARATOR."log_".date("Ymd").".log");

define("SlightPHP_DEBUG_DISPLAY",true);


function SInclude($name){
	if(strpos($name,"SlightPHP")===0){
		//框架类
		$path = SlightPHP_DIR_LIB.DIRECTORY_SEPARATOR."$name.class.php";
	}else{
		if(strpos($name,"_")){
			//程序类
			$tmp = str_replace("_",DIRECTORY_SEPARATOR,$name);
			$path = APP_DIR.DIRECTORY_SEPARATOR.$tmp.".class.php";
			if(file_exists($path)){
				include_once($path);
				return true;
			}
			$path = APP_DIR.DIRECTORY_SEPARATOR.$tmp.".php";
			if(file_exists($path)){
				include_once($path);
				return true;
			}
		}else{
			//程序公用类
			$path = APP_DIR.DIRECTORY_SEPARATOR."class".DIRECTORY_SEPARATOR.$name.".class.php";
		}
	}
	if(file_exists($path)){
		include_once($path);
		return true;
	}
}
SInclude("SlightPHP");
SInclude("SlightPHPJson");

?>