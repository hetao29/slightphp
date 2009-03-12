<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/
//if(!class_exists("SlightPHP")){

final class SlightPHP{
	/**
	 * @var string
	 */
	public static $appDir=".";
	/**
	 * @var string
	 */
	public static $pluginsDir="plugins";
	/**
	 * @var string
	 */
	public static $defaultZone="index";
	/**
	 * @var string
	 */
	public static $defaultClass="default";
	/**
	 * @var string
	 */
	public static $defaultMethod="entry";
	/**
	 * split flag of zone,classs,method
	 *
	 * @var string
	 */
	public static $splitFlag="";

	
	/**
	 * construct method
	 *
	 */

	function SlightPHP(){


	}
	/**
	 * defaultZone set
	 * 
	 * @param string $zone
	 * @return boolean
	 */

	public static function setDefaultZone($zone){
		SlightPHP::$defaultZone = $zone;
		return true;
	}
	/**
	 * defaultZone get
	 * 
	 * @return string
	 */

	public static function getDefaultZone(){
		return SlightPHP::$defaultZone;
	}
	/**
	 * defaultClass set
	 * 
	 * @param string $class
	 * @return boolean
	 */
	public static function setDefaultClass($class){
		SlightPHP::$defaultClass = $class;
		return true;
	}
	/**
	 * getDefaultClass get
	 * 
	 * @return string
	 */
	public static function getDefaultClass(){
		return SlightPHP::$defaultClass;
	}
	/**
	 * defaultMethod set
	 * 
	 * @param string $method
	 * @return boolean
	 */
	public static function setDefaultMethod($method){
		SlightPHP::$defaultMethod = $method;
		return true;
	}
	/**
	 * defaultMethod get
	 * 
	 * @return string $method
	 */
	public static function getDefaultMethod(){
		return SlightPHP::$defaultMethod;
	}
	/**
	 * splitFlag set
	 * 
	 * @param string $flag
	 * @return boolean
	 */
	public static function setSplitFlag($flag){
		SlightPHP::$splitFlag = $flag;
		return true;
	}
	/**
	 * defaultMethod get
	 * 
	 * @return string
	 */
	public static function getSplitFlag(){
		return SlightPHP::$splitFlag;
	}
	/**
	 * appDir set && get
	 *
	 * @param string $dir
	 * @return boolean
	 */

	public static function setAppDir($dir){
		SlightPHP::$appDir = $dir;
		return true;
	}
	/**
	 * appDir get
	 * 
	 * @return string
	 */
	public static function getAppDir(){
		return SlightPHP::$appDir;
	}
	/**
	 * pluginsDir set && get
	 * @param string $dir
	 * @return boolean
	 */
	public static function setPluginsDir($dir){
		SlightPHP::$pluginsDir = $dir;
		return true;
	}
	/**
	 * pluginsDir get
	 * 
	 * @return string
	 */
	public static function getPluginsDir(){
		return SlightPHP::$pluginsDir;
	}
	/**
	 * debug status set
	 *
	 * @param boolean $debug
	 * @return boolean
	 */
	public static function setDebug($debug){
		SlightPHP::$_debug = $debug;
		return true;
	}
	/**
	 * debug status get
	 * 
	 * @return boolean 
	 */
	public static function getDebug(){
		return SlightPHP::$_debug;
	}
	/**
	 * main method!
	 *
	 * @param
	 * @return boolean
	 */

	public static function run(){
		//{{{
		$splitFlag = preg_quote(SlightPHP::$splitFlag,"/");
		$PATH_ARRAY = array();
		if (!empty($_SERVER["PATH_INFO"])){
			$PATH_ARRAY = preg_split("/[$splitFlag\/]/",$_SERVER["PATH_INFO"],-1,PREG_SPLIT_NO_EMPTY);
		}
		if(!empty($PATH_ARRAY[0])){
			SlightPHP::$defaultZone=$PATH_ARRAY[0];
		}else{
			$PATH_ARRAY[0] = SlightPHP::$defaultZone ;
		}

		if(!empty($PATH_ARRAY[1])){
			SlightPHP::$defaultClass=$PATH_ARRAY[1];
		}else{
			$PATH_ARRAY[1] = SlightPHP::$defaultClass ;
		}

		if(!empty($PATH_ARRAY[2])){
			SlightPHP::$defaultMethod=$PATH_ARRAY[2];
		}else{
			$PATH_ARRAY[2] = SlightPHP::$defaultMethod ;
		}


		//}}}
		$app_file = SlightPHP::$appDir . DIRECTORY_SEPARATOR . SlightPHP::$defaultZone . DIRECTORY_SEPARATOR . SlightPHP::$defaultClass . ".class.php";
		if(!file_exists($app_file)){
			SlightPHP::debug("file[$app_file] not exists");
			return false;
		}else{
			SlightPHP::loadFile($app_file);
		}
		$method = "Page".SlightPHP::$defaultMethod;
		$classname = SlightPHP::$defaultZone ."_". SlightPHP::$defaultClass;
		


		if(!class_exists($classname)){
			SlightPHP::debug("class[$classname] not exists");
			return false;
		}
		$classInstance = new $classname;
		if(!method_exists($classInstance,$method)){
			SlightPHP::debug("method[$method] not exists in class[%classname]");
			return false;
		}
		return call_user_func(array(&$classInstance,$method),$PATH_ARRAY);
	}
	/**
	 * loadFile,like require_once
	 *
	 * @param string $filePath
	 * @return boolean
	 */
	public static function loadFile($filePath){
		if(file_exists($filePath)){
			require_once($filePath);
			return true;
		}else{
			SlightPHP::debug("file[$filePath] not exists");
			return false;
		}
	}
	/**
	 * loadPlugin in $pluginsDir 
	 *
	 * @param string $pluginName
	 * @return boolean
	 */
	public static function loadPlugin($pluginName){
		$app_file = SlightPHP::$pluginsDir. DIRECTORY_SEPARATOR . $pluginName. ".class.php";
		return SlightPHP::loadFile($app_file);

	}



	/**
	 * @var boolean
	 */
	public static $_debug=0;

	/*private*/
	private function debug($debugmsg){
		if(SlightPHP::$_debug){
			error_log($debugmsg);
			echo "<!--slightphp debug:".$debugmsg."-->";
		}
	}
}

//}
?>
