<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/
/**
 * @package SlightPHP
 */
if(!class_exists("SlightPHP",false)):
final class SlightPHP{
	/**
	 * @var string
	 */
	public static $appDir=".";

	/**
	 * @var string
	 */
	public static $pathInfo="";

	/**
	 * current zone
	 * @var string
	 */
	public static $zone;
	/**
	 * @var string
	 */
	public static $defaultZone="zone";
	
	/**
	 * current page
	 * @var string
	 */
	public static $page;
	/**
	 * @var string
	 */
	public static $defaultPage="page";
	/**
	 * current entry
	 * @var string
	 */
	public static $entry;
	/**
	 * @var string
	 */
	public static $defaultEntry="entry";
	/**
	 * split flag of zone,classs,method
	 *
	 * @var string
	 */
	public static $splitFlag="/";

	/**
	 * zoneAlias
	 *
	 * @var array
	 */
	public static $zoneAlias;

	/**
	 * @param string $zone
	 * @param string $alias
	 * @return boolean
	 */
	public static function setZoneAlias($zone,$alias){
		self::$zoneAlias[$zone]=$alias;
		return true;
	}
	/**
	 * @param string $zone
	 * @return string | boolean
	 */
	public static function getZoneAlias($zone){
		return isset(self::$zoneAlias[$zone]) ? self::$zoneAlias[$zone] : false;
	}
	
	/**
	 * defaultZone set
	 * 
	 * @param string $zone
	 * @return boolean
	 */

	public static function setDefaultZone($zone){
		self::$defaultZone = $zone;
		return true;
	}
	/**
	 * defaultZone get
	 * 
	 * @return string
	 */

	public static function getDefaultZone(){
		return self::$defaultZone;
	}
	/**
	 * defaultClass set
	 * 
	 * @param string $page
	 * @return boolean
	 */
	public static function setDefaultPage($page){
		self::$defaultPage = $page;
		return true;
	}
	/**
	 * getDefaultClass get
	 * 
	 * @return string
	 */
	public static function getDefaultPage(){
		return self::$defaultPage;
	}
	/**
	 * defaultMethod set
	 * 
	 * @param string $entry
	 * @return boolean
	 */
	public static function setDefaultEntry($entry){
		self::$defaultEntry = $entry;
		return true;
	}
	/**
	 * defaultMethod get
	 * 
	 * @return string $method
	 */
	public static function getDefaultEntry(){
		return self::$defaultEntry;
	}
	/**
	 * splitFlag set
	 * 
	 * @param string $flag
	 * @return boolean
	 */
	public static function setSplitFlag($flag){
		self::$splitFlag = $flag;
		return true;
	}
	/**
	 * defaultMethod get
	 * 
	 * @return string
	 */
	public static function getSplitFlag(){
		return self::$splitFlag;
	}
	/**
	 * appDir set && get
	 * IMPORTANT: you must set absolute path if you use extension mode(extension=SlightPHP.so)
	 *
	 * @param string $dir
	 * @return boolean
	 */

	public static function setAppDir($dir){
		self::$appDir = $dir;
		return true;
	}
	public static function setPathInfo($pathInfo){
		self::$pathInfo = $pathInfo;
		return true;
	}
	/**
	 * appDir get
	 * 
	 * @return string
	 */
	public static function getAppDir(){
		return self::$appDir;
	}
	/**
	 * debug status set
	 *
	 * @param boolean $debug
	 * @return boolean
	 */
	public static function setDebug($debug){
		self::$_debug = $debug;
		return true;
	}
	/**
	 * debug status get
	 * 
	 * @return boolean 
	 */
	public static function getDebug(){
		return self::$_debug;
	}

	/**
	 * main method!
	 *
	 * @param string $path
	 * @return boolean
	 */

	public static function run($path=""){
		$splitFlag = preg_quote(self::$splitFlag,"/");
		$path_array = array();
		if(!empty($path)){
			$isPart = true;
			if($path[0]=="/")$path=substr($path,1);
			$path_array = preg_split("/[$splitFlag\/]/",$path,-1);
		}else{
			$isPart = false;
			if(!empty(self::$pathInfo)){
				$url = self::$pathInfo;
			}else{
				if(!empty($_GET['PATH_INFO'])){
					$url = $_GET['PATH_INFO'];
				}else{
					$url = !empty($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:"";
				}
			}
			if(!empty($url)){
				if($url[0]=="/")$url=substr($url,1);
				$path_array = preg_split("/[$splitFlag\/]/",$url,-1);
			}
		}

		$zone	= !empty($path_array[0]) ? $path_array[0] : self::$defaultZone ;
		$page	= !empty($path_array[1]) ? $path_array[1] : self::$defaultPage ;
		$entry	= !empty($path_array[2]) ? $path_array[2] : self::$defaultEntry ;

		if(self::$zoneAlias && ($key = array_search($zone,self::$zoneAlias))!==false){
			$zone = $key;
		}
		if(!$isPart){
			self::$zone	= $zone;
			self::$page	= $page;
			self::$entry	= $entry;
		}else{
			if($zone == self::$zone && $page == self::$page && $entry == self::$entry){
				self::debug("part ignored [$path]");
				return;
			}
		}

		$app_file = self::$appDir . DIRECTORY_SEPARATOR . $zone . DIRECTORY_SEPARATOR . $page . ".page.php";
		if(!is_file($app_file)){
			self::debug("file[$app_file] does not exists");
			return false;
		}else{
			require_once(realpath($app_file));
		}
		$method = "Page".$entry;
		$classname = $zone ."_". $page;
		
		if(!class_exists($classname)){
			self::debug("class[$classname] does not exists");
			return false;
		}
		$path_array[0] = $zone;
		$path_array[1] = $page;
		$path_array[2] = $entry;
		$classInstance = new $classname($path_array);
		if(!method_exists($classInstance,$method)){
			self::debug("method[$method] does not exists in class[$classname]");
			return false;
		}
		return call_user_func(array(&$classInstance,$method),$path_array);

	}

	/**
	 * @var boolean
	 */
	private static $_debug=0;

	/*private*/
	private function debug($debugmsg){
		if(self::$_debug){
			error_log($debugmsg);
			echo "<!--slightphp debug: ".$debugmsg."-->";
		}
	}
}
endif;
