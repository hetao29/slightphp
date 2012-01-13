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
		public function getConfig($zone,$key){
			$configs = $this->listConfig($zone,$key);
			if(!empty($configs)){
				$i =  array_rand($configs);
				return $configs[$i];
			}
			return array();
		}
		public function listConfig($zone,$key){
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
						$row = str_replace(":","=",$row);
						$row = str_replace(",","&",$row);
						parse_str($row,$out);
						if(!empty($out)){
								$cache[$zone][$key][]=$out;
						}
					}
				}
				if(isset($cache[$zone][$key])){
					return $cache[$zone][$key];
				}
				return array();
		}
}
