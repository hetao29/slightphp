<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

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
                $splitFlag = $splitFlag[0];
                foreach(self::$_Routes as $route){
                        $pattern = $route['pattern'];
                        foreach($route as $k=>$v){
                                if(preg_match("/:\w+/",$k)){
                                        $pattern = str_replace("$k","($v)",$pattern);
                                }
                        }
                        if(preg_match_all("/$pattern/sm",!empty($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:$_SERVER['REQUEST_URI'],$_m)){
                                array_shift($_m);
                                $params = array();
                                if(!empty($_m)){
                                        foreach($_m as $_m2){
                                                $params[]=$_m2[0];
                                        }
                                }
                                $params=implode($splitFlag,$params);
                                $zone = empty($route['zone']) ? SlightPHP::getDefaultZone() : $route['zone'];
                                $page = empty($route['page']) ? SlightPHP::getDefaultPage() : $route['page'];
                                $entry = empty($route['entry']) ? SlightPHP::getDefaultEntry() : $route['entry'];
                                $PATH_INFO = "{$zone}{$splitFlag}{$page}{$splitFlag}{$entry}{$splitFlag}{$params}";
                                SlightPHP::setPathInfo($PATH_INFO);
                                break;
                        }

                }

        }
}
