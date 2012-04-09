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
			$content = file(self::getConfigFile());
			$result=null;
			$path = array();
			$_deep=0;
			foreach($content as $line){
				//去掉注释,#号表示注释
				$line = preg_replace("/^(\s*)#(.*)/m","",$line);
				//找出key,value对应
				preg_match_all("/(\w+)([\s:]+)([\{\['\"]*)(.*?)\\3([;\{\}\[\]])/S",$line,$_matches,PREG_SET_ORDER);
				//print_r($_matches);exit;
				if(!empty($_matches)){
					foreach($_matches as $_m){
						if(!empty($_m)){
							$key = ($_m[1]);
							$value = ($_m[4]);
							$object = ($_m[5]);
							$path[$_deep]=$key;
							if($object !="{" && $object!="["){
								self::_setData($result,$path,$value,$flag,$allowMultiValue);
							}else{
								$_deep++;
								self::_setData($result,$path,null,$flag,$allowMultiValue);
							}
						}
					}
				}else{
					preg_match("/([\]\}]+).*?/",$line,$_m);
					if(!empty($_m)){
						$_deep--;
						array_pop($path);
					}


				}
			}
			return $result;
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
