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
class SlightPHP{
	var $ModuleName ="index";	//目录名,程序在 APP_DIR / ModuleName / PageName.class.php 里
	var $PageName = "index";
	var $Action = "entry";		//执行的方法名
	
	var $RELATIVE_PATH="";
	var $PATH_ARRAY=array();

	function SlightPHP(){
		//{{{
			$PATH_ARRAY = array();
			if (!empty($_SERVER["PATH_INFO"])) // This ensures Zoop works as a fastcgi script
			{
				if(substr($_SERVER["PATH_INFO"],0,1) == "/")
					$PATH_INFO = $_SERVER["PATH_INFO"];
				else
					$PATH_INFO = "/" . $_SERVER["PATH_INFO"];
				$PATH_ARRAY = explode("/",$PATH_INFO);
			}
			

			$xtrapath = "";
			foreach($PATH_ARRAY as $key =>$val){
				if ($val){
					$xtrapath = "../" . $xtrapath;
				}
			}
			$this->RELATIVE_PATH = $xtrapath;
			$this->PATH_ARRAY = $PATH_ARRAY;
		//}}}
		if(!empty($_REQUEST['_modulename'])){
			$this->ModuleName=$_REQUEST['_modulename'];
		}elseif(!empty($PATH_ARRAY[1])){
			$this->ModuleName=$PATH_ARRAY[1];
		}
		if(!empty($_REQUEST['_pagename'])){
			$this->PageName=$_REQUEST['_pagename'];
		}elseif(!empty($PATH_ARRAY[2])){
			$this->PageName=$PATH_ARRAY[2];
		}
		if(!empty($_REQUEST['_action'])){
			$this->Action=$_REQUEST['_action'];
		}elseif(!empty($PATH_ARRAY[3])){
			$this->Action=$PATH_ARRAY[3];
		}
	}
	function redirect404($msg){
		header("HTTP/1.1 404 Not Found $msg");
		exit;
	}
	function run(){
		$app_file = APP_DIR . DIRECTORY_SEPARATOR . $this->ModuleName . DIRECTORY_SEPARATOR . $this->PageName . ".php";
		if(!file_exists($app_file)){
			$this->redirect404("$app_file not exists");
		}else{
			require_once($app_file);
		}
		$method = "Page".$this->Action;
		$classname = $this->ModuleName ."_". $this->PageName;
		SInclude($classname);
		if(!class_exists($classname)){
			$this->redirect404("$classname not exits in file $app_file");
		}
		$classInstance = new $classname;
		if(!method_exists($classInstance,$method)){
			$this->redirect404("$method not exits in class $classname");
		}
		$classInstance->ModuleName = $this->ModuleName;
		$para= array_slice($this->PATH_ARRAY,4);
		global $APP_CONFIG;
		$returnType="";
		if(!empty($APP_CONFIG[$this->ModuleName]) and !empty($APP_CONFIG[$this->ModuleName]['type'])){
			$returnType = $APP_CONFIG[$this->ModuleName]['type'];
		}
		$r = call_user_func(array(&$classInstance,$method),$para);
		
		switch($returnType){
			case "json":
				SInclude("SlightPHPJson");
				$json = new SlightPHPJson;
				echo $json->encode($r);
				break;
			default:
				echo $r;
		}
	}
}

?>