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
class SConfig{
	/**
	 * 支持新的conf配置格式(类似nginx的配置)
	 * @return mixed $result
	 * @param string $configFile
	 **/
	public static function getConfig($configFile,$zone=null){
		$config = self::parse($configFile);
		if($zone){
			if($config->$zone){
				return $config->$zone;
			}elseif($config->default){
				return $config->default;
			}
			return null;
		}
		return $config;
	}
	public static function parse($configFile){
		$cacheKey = "SConfig_Cache_"+$configFile;
		if(isset(self::$_result[$cacheKey])){
			return self::$_result[$cacheKey];
		}
		$tmp_file = self::_tmpDir()."/".self::$_tmpPrefix.basename($configFile);
		if(is_file($tmp_file) && filemtime($tmp_file)>=filemtime($configFile)){
			$result = unserialize(file_get_contents($tmp_file,false));
			self::$_result[$cacheKey]=$result;
			return $result;
		}
		$content = file_get_contents($configFile,false);
		//去掉注释,#号表示注释
		$content = preg_replace("/^(\s*)#(.*)/m","",$content);
		//保存临时变量,单引号,双引号里特殊字符
		$content = preg_replace_callback("/(\S+?)[\s:]+([\'\"])(.*?)\\2;/m",array("SConfig","_tmpData"),$content);
		//获取最直接的k,v值
		$result = self::_getKV($content);
		self::_split($content,$result);
		file_put_contents($tmp_file,serialize($result),LOCK_EX);
		self::$_result[$cacheKey]=$result;
		return $result;
	}

	private static $_tmpData=array();
	private static $_tmpPrefix="SCONFIG_TMP_PREFIX_";
	private static $_tmpIndex=0;
	private static $_result=array();
	private static function _tmpData($matches){
		$key = self::$_tmpPrefix.(self::$_tmpIndex++);
		self::$_tmpData[$key]=$matches[3];
		return $matches[1].":".$key.";";
	}
	private static function _getKV($string) {
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
	private static function _split ($string,&$result) {
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
	private static function _tmpDir(){
		if ( !function_exists('sys_get_temp_dir')){
			function sys_get_temp_dir() {
				if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
				if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
				if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
				$tempfile=tempnam(uniqid(rand(),TRUE),'');
				if (file_exists($tempfile)) {
					unlink($tempfile);
					return realpath(dirname($tempfile));
				}
			}
		}else{
			return sys_get_temp_dir();
		}
	}
}
