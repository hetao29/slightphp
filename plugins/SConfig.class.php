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
if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
class SConfig{
	private $_ConfigFile;
	private $_ConfigCache;
	private $_DefaultZone="default";
	public function setConfigFile($file){
		$this->_ConfigFile = $file;
	}
	public function getConfigFile(){
		return $this->_ConfigFile;
	}
	/**
	 * @param string $zone
	 * @param string $key preg_match
	 * @return array
	 */
	public function getConfig($zone,$key,$parse=true){
		$configs = $this->listConfig($zone,$key,$parse);
		if(!empty($configs)){
			$i =  array_rand($configs);
			return $configs[$i];
		}
		return array();
	}
	public function listConfig($zone,$key,$parse=true){
		if(!$this->_ConfigFile){return array();}
		$cache = $this->_ConfigCache;
		if(isset($cache[$zone]) && isset($cache[$zone][$key])){
			return $cache[$zone][$key];
		}
		$file_data = parse_ini_file(realpath($this->_ConfigFile),true);
		if(isset($file_data[$zone])){
			$_configs = $file_data[$zone];
		}elseif(isset($file_data[$this->_DefaultZone])){
			$_configs = $file_data[$this->_DefaultZone];
		}else{
			return array();
		}
		foreach($_configs as $k =>$row){
			if(preg_match("/$key/i",$k)){
				if($parse){
					$row = str_replace(":","=",$row);
					$row = str_replace(",","&",$row);
					parse_str($row,$out);
					if(!empty($out)){
						$cache[$zone][$key][$k]=$out;
					}
				}else{
					$cache[$zone][$key][$k]=$row;
				}
			}
		}
		if(isset($cache[$zone][$key])){
			return $cache[$zone][$key];
		}
		return array();
	}
	/**
	 * 支持新的conf配置格式(类似nginx的配置)
	 * @return mixed $result
	 **/
	public function parse(){
		$cacheKey = "SConfig_Cache";
		if(isset(self::$_result[$cacheKey])){
			return self::$_result[$cacheKey];
		}
		$content = file_get_contents(self::getConfigFile());
		//去掉注释,#号表示注释
		$content = preg_replace("/^(\s*)#(.*)/m","",$content);
		//保存临时变量,单引号,双引号里特殊字符
		$content = preg_replace_callback("/(\S+?)[\s:]+([\'\"])(.*?)\\2;/m",array("SConfig","_tmpData"),$content);
		//获取最直接的k,v值
		$result = self::_getKV($content);
		self::_split($content,$result);
		self::$_result[$cacheKey]=$result;
		return $result;
	}
	private static $_tmpData=array();
	private static $_tmpPrefix="SCONFIG_TMP_PREFIX_";
	private static $_tmpIndex=0;
	private static $_result=array();
	private function _tmpData($matches){
		$key = self::$_tmpPrefix.(self::$_tmpIndex++);
		self::$_tmpData[$key]=$matches[3];
		return $matches[1].":".$key.";";
	}
	private function _getKV($string) {
		$_data = new stdclass;
		$tmp_string = preg_replace("/([\w\.\-\_]+?)([\s:]*)\{(([^{}]+|(?R))+)\}/","",$string);
		preg_match_all("/([\w\.\-\_]+)[\s:]+(.+?);/",$tmp_string,$_matches2,PREG_SET_ORDER);
		if(!empty($_matches2)){
			foreach($_matches2 as $_m2){
				$key = $_m2[1];
				$value= $_m2[2];
				if(isset(self::$_tmpData[$value])){$value = self::$_tmpData[$value];}
				if(isset($_data->$key)){
					if(is_array($_data->$key)){
						array_push($_data->$key,$value);
					}else{
						$tmp2=array($_data->$key,$value);
						$_data->$key = $tmp2;
					}
				}else{
					$_data->$key = $value;
				}
			}
		}
		return $_data;
	}
	private function _split ($string,&$result) {
		preg_match_all("/([\w\.\-\_]*?)[:\s]*\{(([^{}]*|(?R))+)\}/xms",$string,$matches,PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $m){
				if(empty($m[1]))continue;
				$_data = self::_getKV($m[2]);
				if(!isset($result->$m[1])){
					if(!is_array($result)){
						$result->$m[1] = $_data;
					}
				}else{
					if(is_array($result->$m[1])){
						array_push($result->$m[1],$_data);
					}else{
						$result->$m[1] = array($result->$m[1],$_data);
					}
				}
				self::_split($m[2], $_data);
			}
		}
	}
}
