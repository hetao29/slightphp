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

class SlightPHP{
	/**
	 * @var string
	 */
	var $appDir=".";
	/**
	 * @var string
	 */
	var $pluginsDir="plugins";
	/**
	 * @var string
	 */
	var $defaultZone="index";
	/**
	 * @var string
	 */
	var $defaultClass="default";
	/**
	 * @var string
	 */
	var $defaultMethod="entry";
	/**
	 * split flag of zone,classs,method
	 *
	 * @var string
	 */
	var $splitFlag="";

	
	/**
	 * construct method
	 *
	 */

	function SlightPHP(){
	}
	/**
	 * main method!
	 *
	 * @param
	 * @return boolean
	 */

	function run(){
		//{{{
		$splitFlag = preg_quote($this->splitFlag,"/");
		$PATH_ARRAY = array();
		if (!empty($_SERVER["PATH_INFO"])){
			$PATH_ARRAY = preg_split("/[$splitFlag\/]/",$_SERVER["PATH_INFO"],-1,PREG_SPLIT_NO_EMPTY);
		}
		if(!empty($PATH_ARRAY[0])){
			$this->defaultZone=$PATH_ARRAY[0];
		}else{
			$PATH_ARRAY[0] = $this->defaultZone ;
		}

		if(!empty($PATH_ARRAY[1])){
			$this->defaultClass=$PATH_ARRAY[1];
		}else{
			$PATH_ARRAY[1] = $this->defaultClass ;
		}

		if(!empty($PATH_ARRAY[2])){
			$this->defaultMethod=$PATH_ARRAY[2];
		}else{
			$PATH_ARRAY[2] = $this->defaultMethod ;
		}


		//}}}
		$app_file = $this->appDir . DIRECTORY_SEPARATOR . $this->defaultZone . DIRECTORY_SEPARATOR . $this->defaultClass . ".class.php";
		if(!file_exists($app_file)){
			$this->debug("file[$app_file] not exists");
			return false;
		}else{
			$this->loadFile($app_file);
		}
		$method = "Page".$this->defaultMethod;
		$classname = $this->defaultZone ."_". $this->defaultClass;
		


		if(!class_exists($classname)){
			$this->debug("class[$classname] not exists");
			return false;
		}
		$classInstance = new $classname;
		if(!method_exists($classInstance,$method)){
			$this->debug("method[$method] not exists in class[%classname]");
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
	function loadFile($filePath){
		if(file_exists($filePath)){
			require_once($filePath);
			return true;
		}else{
			$this->debug("file[$filePath] not exists");
			return false;
		}
	}
	/**
	 * loadPlugin in $pluginsDir 
	 *
	 * @param string $pluginName
	 * @return boolean
	 */
	function loadPlugin($pluginName){
		$app_file = $this->pluginsDir. DIRECTORY_SEPARATOR . $pluginName. ".class.php";
		return $this->loadFile($app_file);

	}



	/**
	 * @var int
	 */
	var $_debug=0;

	/*private*/
	private function debug($debugmsg){
		if($this->_debug){
			error_log($debugmsg);
			echo "<!--slightphp debug:".$debugmsg."-->";
		}
	}
}

//}
?>
