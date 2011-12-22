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
class SRoute{
	private static $_RouteConfigFile;
	private static $_Routes=array();
	static function setConfigFile($file){
		self::$_RouteConfigFile= $file;
		self::$_Routes = array_merge(self::$_Routes,parse_ini_file(self::$_RouteConfigFile,true));
		self::parse();
	}
	static function getConfigFile(){
		return self::$_RouteConfigFile;
	}
	static function set(array $route){
		self::$_Routes[] = $route;
		self::parse();
	}
	private static function parse(){
		$splitFlag = SlightPHP::getSplitFlag();
		$splitFlag = $splitFlag{0};
		foreach(self::$_Routes as $route){
			$pattern = $route['pattern'];
			foreach($route as $k=>$v){
				if(preg_match("/:\w+/",$k)){
					$pattern = str_replace("$k","($v)",$pattern);
				}
			}
			if(preg_match_all("/$pattern/sm",!empty($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:$_SERVER['REQUEST_URI'],$_m)){
				array_shift($_m);
				if(!empty($_m)){
					$params = "";
					foreach($_m as $_m2){
						$params.=$splitFlag.$_m2[0];
					}
					$zone = empty($route['zone']) ? SlightPHP::getDefaultZone() : $route['zone'];
					$page = empty($route['page']) ? SlightPHP::getDefaultPage() : $route['page'];
					$entry = empty($route['entry']) ? SlightPHP::getDefaultEntry() : $route['entry'];
					$_SERVER['PATH_INFO'] = "{$zone}{$splitFlag}{$page}{$splitFlag}{$entry}{$params}";
					break;
				}
			}

		}

	}
}
