<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * 将请求路径匹配到 SlightPHP 的区域、页面和入口。
 * @package SlightPHP
 */
class SRoute{
        private static $_RouteConfigFile;
        private static $_Routes=array();
        /**
         * 加载 INI 路由，追加到现有规则后立即尝试匹配当前请求。
         * @param string $file INI 配置文件路径，节对应路由规则。
         * @return void
         * @throws TypeError INI 无法解析时，parse_ini_file 的 false 不能传给 array_merge。
         */
        static function setConfigFile($file){
                self::$_RouteConfigFile= $file;
                self::$_Routes = array_merge(self::$_Routes,parse_ini_file(self::$_RouteConfigFile,true));
                self::parse();
        }
        /** @return string|null 最近设置的路由配置文件路径。 */
        static function getConfigFile(){
                return self::$_RouteConfigFile;
        }
        /**
         * 追加路由并立即按注册顺序尝试匹配当前请求。
         * @param array $route 包含 pattern、可选 zone/page/entry 及占位符正则的规则。
         *                     pattern 不带外围分隔符；内部使用 /.../sm，斜杠须转义。
         *                     以 :name 为键的正则替换 pattern 中的同名占位符并产生捕获组。
         * @return void
         */
        static function set(array $route){
                self::$_Routes[] = $route;
                self::parse();
        }
        /**
         * 优先匹配非空 PATH_INFO，否则匹配 REQUEST_URI；首条匹配的规则生效。
         * 各捕获组的首次匹配依次作为路径参数，以框架分隔符的首字符拼接。
         * @return void
         */
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
