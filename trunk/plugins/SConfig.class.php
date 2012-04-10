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
define("SCONFIG_FLAG_OBJECT",	1);
define("SCONFIG_FLAG_ARRAY",	2);
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
	 * @param int $flag 返回类型Array|Object,默认是Object
	 * @param boolean $allowMultiValue 允许配置文件存在相同的key(多个相同的key,会自动变成数组)
	 * @return mixed $result
	 **/
	public function parse($flag=SCONFIG_FLAG_OBJECT,$allowMultiValue=true){
		self::$_flag = $flag;
		self::$_allowMultiValue = $allowMultiValue;
		$cacheKey = self::$_flag."_".self::$_allowMultiValue;
		if(isset(self::$_result[$cacheKey])){
			return self::$_result[$cacheKey];
		}
		$content = file_get_contents(self::getConfigFile());
		//去掉注释,#号表示注释
		$content = preg_replace("/^(\s*)#(.*)/m","",$content);
		//保存临时变量,单引号,双引号里特殊字符
		$content = preg_replace_callback("/(\S+)[\s:]+([\'\"])(.*)\\2;/m",array("SConfig","_tmpData"),$content);
		self::_split($content,$result);
		self::$_result[$cacheKey]=$result;
		return $result;
	}
	private static $_tmpData=array();
	private static $_tmpPrefix="SCONFIG_TMP_PREFIX_";
	private static $_tmpIndex=0;
	private static $_flag = SCONFIG_FLAG_OBJECT;
	private static $_allowMultiValue = true;
	private static $_result=array();
	private function _tmpData($matches){
		$key = self::$_tmpPrefix.(self::$_tmpIndex++);
		self::$_tmpData[$key]=$matches[3];
		return $matches[1].":".$key.";";
	}
	private function _split ($string,&$result, $layer=0, $path=array()) {
		preg_match_all("/(\S+?)[:\s]*\{(([^{}]*|(?R))+)\}/xms",$string,$matches,PREG_SET_ORDER);
		if (!empty($matches)) {
			foreach($matches as $m){
				$path[$layer]=$m[1];
				//找出普通的k,v,需要把{}里的给无视
				$tmp_string = preg_replace("/(\S+)([\s:]*)\{(([^{}]+|(?R))+)\}/","",$m[2]);
				preg_match_all("/(\w+)[\s:]+(.+);/",$tmp_string,$_matches2,PREG_SET_ORDER);
				foreach($_matches2 as $_m2){
					$key = $_m2[1];
					$value= $_m2[2];
					$path2 = $path;
					array_push($path2,$key);
					//找回被替换的值
					if(isset(self::$_tmpData[$value])){$value = self::$_tmpData[$value];}
					self::_setData($result,$path2,$value,self::$_flag,self::$_allowMultiValue);
				}
				self::_split($m[2], $result,$layer + 1,$path);
			}
		}
	}
	private function _setData(&$arr,$path,$value,$flag=SCONFIG_FLAG_OBJECT,$allowMultiValue=true){
		$tmp = &$arr;
		foreach ($path as $segment) {
			if($flag==SCONFIG_FLAG_ARRAY){
				$tmp = &$tmp[$segment];
			}else{
				$tmp = &$tmp->$segment;
			}
		}
		if(isset($tmp) && $allowMultiValue==true){
			if(is_array($tmp)){
				array_push($tmp,$value);
			}else{
				$tmp2=array($tmp,$value);
				$tmp = $tmp2;
			}
		}else{
			$tmp=$value;
		}
	}
}
