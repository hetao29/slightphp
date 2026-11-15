<?php

namespace SlightPHP;

use \SlightPHP;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * 将请求路径匹配到 SlightPHP 的区域、页面和入口，或作为控制器方法的 HTTP 方法限制。
 * @package SlightPHP
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route{
        /** @var string[] 注解允许的 HTTP 方法；空数组表示不限制。 */
        private array $methods = [];

        /**
         * method 与 methods 取并集，统一为大写并去重；均未设置时不限制。
         * @param string|null $method 单个 HTTP 方法。
         * @param string[] $methods 多个 HTTP 方法。
         * @throws \InvalidArgumentException 方法为空或不是合法的 HTTP token。
         */
        public function __construct(?string $method = null, array $methods = []) {
                if ($method !== null) {
                        array_unshift($methods, $method);
                }
                foreach ($methods as $value) {
                        if (!is_string($value)) {
                                throw new \InvalidArgumentException('Route methods must be strings');
                        }
                        $value = strtoupper(trim($value));
                        if (!preg_match('/^[!#$%&\'*+.^_`|~0-9A-Z-]+$/D', $value)) {
                                throw new \InvalidArgumentException('Invalid Route HTTP method');
                        }
                        if (!in_array($value, $this->methods, true)) {
                                $this->methods[] = $value;
                        }
                }
        }

        /** @return string[] 已规范化的方法列表；空数组表示不限制。 */
        public function getMethods(): array {
                return $this->methods;
        }

        private static $_RouteConfigFile;
        private static $_Routes=array();
        /**
         * 加载 INI 路由，追加到现有规则后立即尝试匹配当前请求。
         * @param string $file INI 配置文件路径，节对应路由规则。
         * @return void
         * @throws \RuntimeException INI 文件不可读或解析失败。
         * @throws \InvalidArgumentException 路由配置或正则无效。
         */
        static function setConfigFile($file){
                $routes = @parse_ini_file($file, true);
                if ($routes === false) {
                        throw new \RuntimeException('Cannot read or parse Route INI file');
                }
                foreach ($routes as $route) {
                        if (!is_array($route)) {
                                throw new \InvalidArgumentException('Route INI rules must be grouped into sections');
                        }
                        self::pattern($route);
                }
                self::$_RouteConfigFile= $file;
                self::$_Routes = array_merge(self::$_Routes, array_values($routes));
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
         * @throws \InvalidArgumentException 路由配置或正则无效。
         */
        static function set(array $route){
                self::pattern($route);
                self::$_Routes[] = $route;
                self::parse();
        }
        /**
         * 匹配显式路径，未传时优先使用非空 PATH_INFO，否则使用 REQUEST_URI。
         * 只匹配 URL 路径，忽略查询串；首条匹配的规则生效，未匹配不修改 pathInfo。
         * 各捕获组的首次匹配依次作为路径参数，以框架分隔符的首字符拼接。
         * @param string|null $path 显式路径；用于入口已规范化 URI 或常驻进程重新匹配。
         * @return void
         * @throws \InvalidArgumentException URL 或路由正则无效。
         */
        public static function parse(?string $path = null){
                $path = $path ?? (!empty($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : ($_SERVER['REQUEST_URI'] ?? ''));
                // 以 / 开头的请求目标是路径；不能将 //articles/42 解析成主机名和 /42。
                $path = str_starts_with($path, '/')
                        ? substr($path, 0, strcspn($path, '?#'))
                        : parse_url($path, PHP_URL_PATH);
                if ($path === false) {
                        throw new \InvalidArgumentException('Invalid Route request URL');
                }
                if ($path === null || $path === '') return;
                $splitFlag = SlightPHP::getSplitFlag();
                $splitFlag = $splitFlag !== '' ? $splitFlag[0] : '/';
                foreach(self::$_Routes as $route){
                        $pattern = self::pattern($route);
                        $matched = @preg_match_all($pattern, $path, $_m);
                        if ($matched === false) {
                                throw new \InvalidArgumentException('Route matching failed: ' . preg_last_error_msg());
                        }
                        if($matched){
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

        /** 展开完整占位符名，避免 :id 替换破坏 :identifier 等较长名称。 */
        private static function pattern(array $route): string {
                if (!isset($route['pattern']) || !is_string($route['pattern']) || $route['pattern'] === '') {
                        throw new \InvalidArgumentException('Route pattern must be a non-empty string');
                }
                $pattern = preg_replace_callback('/:([A-Za-z0-9_]+)/', static function ($match) use ($route) {
                        if (!array_key_exists($match[0], $route)) return $match[0];
                        if (!is_string($route[$match[0]])) {
                                throw new \InvalidArgumentException('Route placeholder patterns must be strings');
                        }
                        return '(' . $route[$match[0]] . ')';
                }, $route['pattern']);
                $pattern = '/' . $pattern . '/sm';
                if (@preg_match($pattern, '') === false) {
                        throw new \InvalidArgumentException('Invalid Route regular expression: ' . preg_last_error_msg());
                }
                return $pattern;
        }
}

