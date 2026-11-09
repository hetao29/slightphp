<?php

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 */
if (!class_exists("SlightPHP", false)):
	/** 应用入口：解析请求路径，加载控制器并调用对应入口方法。 */
	final class SlightPHP
	{
		/**
		 * @var string 应用根目录。
		 */
		public static $appDir = ".";

		/**
		 * @var string|false 应用根目录的规范路径；解析失败时为 false。
		 */
		private static $appDirRealPath = "";

		/**
		 * namespace prefix of the controllers, default ""
		 * @var string 控制器命名空间前缀。
		 */
		public static $appNamespace = "";

		/**
		 * @var string 优先于服务器请求信息的显式路径。
		 */
		public static $pathInfo = "";

		/**
		 * @var int 请求路径允许的最大分段数，默认 10。
		 */
		private static $maxDepth = 10;

		/**
		 * 设置请求路径的最大分段数，包含区域、page、entry 和路径参数。
		 * @param int $maxDepth 最大深度，必须大于等于 1。
		 * @return bool 始终返回 true。
		 * @throws \InvalidArgumentException 深度小于 1。
		 */
		public static function setMaxDepth(int $maxDepth)
		{
			if ($maxDepth < 1) {
				throw new \InvalidArgumentException('maxDepth must be at least 1');
			}
			self::$maxDepth = $maxDepth;
			return true;
		}

		/**
		 * @return int 当前请求路径的最大分段数。
		 */
		public static function getMaxDepth()
		{
			return self::$maxDepth;
		}

		/**
		 * set controller suffix, default "Controller"
		 * @var string
		 */
		private static $setControllerSuffix = "Controller";

		/**
		 * set controller suffix, default "Controller"
		 * @param string $suffix 控制器类名后缀。
		 * @return bool
		 */
		public static function setControllerSuffix(string $suffix)
		{
			self::$setControllerSuffix = $suffix;
			return true;
		}
		/**
		 * get controller suffix, default "Controller"
		 * @return string 控制器类名后缀。
		 */
		public static function getControllerSuffix()
		{

			return self::$setControllerSuffix;
		}


		/**
		 * current zone
		 * @var string|null 当前请求匹配的区域；调度前为 null。
		 */
		public static $zone;

		/**
		 * @var string
		 */
		public static $defaultZone = "zone";

		/**
		 * current page
		 * @var string|null 当前请求匹配的页面；调度前为 null。
		 */
		public static $page;

		/**
		 * @var string
		 */
		public static $defaultPage = "page";

		/**
		 * current type
		 * @var string|null 保留的当前请求类型字段；调度过程不设置此字段。
		 */
		public static $type;

		/**
		 * 允许的请求类型
		 * @var array
		 */
		public static $allowRequestTypes = [
			"page",
			"ajax",
			"part",
			"json",
			"xml",
			"html"
		];

		/**
		 * 设置允许作为方法前缀的请求类型。
		 * HTTP_REQUEST_TYPE 在此列表中时加到入口名前；不在列表时调用无前缀入口。
		 * 未提供请求类型时不添加前缀。
		 * 该设置不限制 HTTP 请求方法，也不是权限控制。
		 * @param string[] $types 允许作为入口方法前缀的请求类型。
		 * @return bool 始终返回 true。
		 */
		public static function setAllowRequestTypes(array $types)
		{
			self::$allowRequestTypes = $types;
			return true;
		}

		/**
		 * Get the allowed request types
		 * @return array
		 */
		public static function getAllowRequestTypes()
		{
			return self::$allowRequestTypes;
		}
		/**
		 * @var string|null 当前请求匹配的入口；调度前为 null。
		 */
		public static $entry;

		/**
		 * @var string
		 */
		public static $defaultEntry = "entry";

		/**
		 * split flag of zone,classs,method
		 *
		 * @var string
		 */
		public static $splitFlag = "/";

		/**
		 * 设置命名空间模式下的控制器前缀，去掉首尾反斜杠。
		 * @param string $namespace 命名空间前缀。
		 * @return bool 始终返回 true。
		 */
		public static function setAppNamespace($namespace)
		{
			self::$appNamespace = trim($namespace, "\\");
			return true;
		}

		/**
		 * @return string 当前控制器命名空间前缀。
		 */
		public static function getAppNamespace()
		{
			return self::$appNamespace;
		}
		/**
		 * 区域名到 URL 别名的映射，尚未设置时为 null。
		 *
		 * @var array<string,string>|null
		 */
		public static $zoneAlias;

		/**
		 * 设置区域的 URL 别名；调度时根据别名反查区域名。
		 * 当前类名和文件路径仍由原始区域路径构造，别名不能可靠替代目录映射。
		 * @param string $zone 原区域名。
		 * @param string $alias URL 中使用的别名。
		 * @return bool 始终返回 true。
		 */
		public static function setZoneAlias(string $zone, string $alias)
		{
			self::$zoneAlias[$zone] = $alias;
			return true;
		}

		/**
		 * 查询区域的 URL 别名。
		 * @param string $zone 原区域名。
		 * @return string|false 别名，或未设置时的 false。
		 */
		public static function getZoneAlias(string $zone)
		{
			return isset(self::$zoneAlias[$zone]) ? self::$zoneAlias[$zone] : false;
		}

		/**
		 * 页面名到 URL 别名的映射，尚未设置时为 null。
		 *
		 * @var array<string,string>|null
		 */
		public static $pageAlias;

		/**
		 * 设置页面的 URL 别名；调度时根据别名反查页面名。
		 * @param string $page 原页面名。
		 * @param string $alias URL 中使用的别名。
		 * @return bool 始终返回 true。
		 */
		public static function setPageAlias(string $page, string $alias)
		{
			self::$pageAlias[$page] = $alias;
			return true;
		}

		/**
		 * 查询页面的 URL 别名。
		 * @param string $page 原页面名。
		 * @return string|false 别名，或未设置时的 false。
		 */
		public static function getPageAlias(string $page)
		{
			return isset(self::$pageAlias[$page]) ? self::$pageAlias[$page] : false;
		}

		/**
		 * 设置路径缺少区域时使用的默认区域。
		 * 
		 * @param string $zone 默认区域名。
		 * @return boolean
		 */

		public static function setDefaultZone(string $zone)
		{
			self::$defaultZone = $zone;
			return true;
		}
		/**
		 * 获取默认区域。
		 * 
		 * @return string
		 */

		public static function getDefaultZone()
		{
			return self::$defaultZone;
		}
		/**
		 * 设置路径缺少页面时使用的默认页面。
		 *
		 * @param string $page 默认页面名。
		 * @return boolean
		 */
		public static function setDefaultPage(string $page)
		{
			self::$defaultPage = $page;
			return true;
		}
		/**
		 * 获取默认页面。
		 *
		 * @return string
		 */
		public static function getDefaultPage()
		{
			return self::$defaultPage;
		}
		/**
		 * 设置路径缺少入口时使用的默认入口名。
		 * 
		 * @param string $entry 默认入口名。
		 * @return boolean
		 */
		public static function setDefaultEntry(string $entry)
		{
			self::$defaultEntry = $entry;
			return true;
		}
		/**
		 * 获取默认入口名。
		 * 
		 * @return string 入口名。
		 */
		public static function getDefaultEntry()
		{
			return self::$defaultEntry;
		}
		/**
		 * 设置路径中的附加分隔字符；斜杠始终是分隔符。
		 * 
		 * @param string $flag 附加分隔字符集合。
		 * @return boolean
		 */
		public static function setSplitFlag(string $flag)
		{
			self::$splitFlag = $flag;
			return true;
		}
		/**
		 * 获取附加路径分隔字符。
		 * 
		 * @return string
		 */
		public static function getSplitFlag()
		{
			return self::$splitFlag;
		}
		/**
		 * 设置应用根目录并刷新其规范路径缓存。
		 * @param string $dir 应用根目录路径。
		 * @return boolean
		 */

		public static function setAppDir(string $dir)
		{
			self::$appDir = $dir;
			self::$appDirRealPath = realpath($dir);
			return true;
		}
		/**
		 * 设置调度时优先使用的路径。
		 * 
		 * @param string $pathInfo 请求路径；空字符串恢复使用服务器信息。
		 * @return boolean
		 */
		public static function setPathInfo(string $pathInfo)
		{
			self::$pathInfo = $pathInfo;
			return true;
		}
		/**
		 * 获取应用根目录。
		 * 
		 * @return string
		 */
		public static function getAppDir()
		{
			return self::$appDir;
		}
		/**
		 * 设置是否记录调度调试信息。
		 *
		 * @param bool $debug 是否开启调试日志。
		 * @return boolean
		 */
		public static function setDebug(bool $debug)
		{
			self::$_debug = $debug;
			return true;
		}
		/**
		 * 获取调试开关；尚未设置时返回整数 0。
		 * 
		 * @return bool|int
		 */
		public static function getDebug()
		{
			return self::$_debug;
		}

		/**
		 * 解析路径并调用匹配的控制器入口。
		 * 显式传入非空路径时作为局部调用，不更新当前 zone/page/entry，
		 * 且会跳过与当前入口相同的局部调用。
		 * 请求路径最多包含 maxDepth 段（默认 10），超限返回 false。
		 * 上限包含区域、page、entry 和路径参数；未超限时完整路径数组传给控制器。
		 * 未显式传路径时依次使用 pathInfo、PATH_INFO 和 REQUEST_URI。
		 *
		 * @param string $path 显式路径；空字符串时从 pathInfo 或服务器请求信息读取。
		 * @return mixed 入口方法的返回值；找不到可调用入口时返回 false。
		 */
		public static function run($path = "")
		{
			$splitFlag = preg_quote(self::$splitFlag, "/");
			$path_array = array();
			if (!empty($path)) {
				$isPart = true;
			} else {
				$isPart = false;
				if (!empty(self::$pathInfo)) {
					$path = self::$pathInfo;
				} elseif (!empty($_SERVER['PATH_INFO'])) {
					$path = $_SERVER["PATH_INFO"];
				} elseif (!empty($_SERVER['REQUEST_URI'])) {
					$path = $_SERVER["REQUEST_URI"];
				} else {
					self::debug("path not set in params or server.path_info, server.request_uri");
					return false;
				}
			}
			$len = strlen($path);
			$start = 0;
			for ($start = 0; $start < $len; $start++) {
				if ($path[$start] != '/') break;
			}

			$url_parsed = parse_url(substr($path, $start), PHP_URL_PATH);
			if (!empty($url_parsed)) {
				$url = $url_parsed;
			} else {
				$url = "";
			}
			$path_array = preg_split("/[$splitFlag\/]+/", $url, min(self::$maxDepth, PHP_INT_MAX - 1) + 1, PREG_SPLIT_NO_EMPTY);
			if (count($path_array) > self::$maxDepth) {
				self::debug("path exceeds maxDepth");
				return false;
			}
			if (!isset($path_array[0])) {
				$path_array[0] = self::$defaultZone;
			}
			if (!isset($path_array[1])) {
				$path_array[1] = self::$defaultPage;
			}
			if (!isset($path_array[2])) {
				$path_array[2] = self::$defaultEntry;
			}
			$_count = count($path_array);
			for ($depth = $_count - 2; $depth >= 1; $depth--) {
				$zoneParts = array_slice($path_array, 0, $depth);
				$_zone  =  strtolower(implode(DIRECTORY_SEPARATOR, $zoneParts));
				$_page   = strtolower($path_array[$depth]);
				$_entry  = strtolower($path_array[$depth + 1]);
				if (self::$zoneAlias && ($key = array_search($_zone, self::$zoneAlias)) !== false) {
					$_zone = $key;
				}
				if (self::$pageAlias && ($key = array_search($_page, self::$pageAlias)) !== false) {
					$_page = $key;
				}
				if ($isPart && $_zone == self::$zone && $_page == self::$page && $_entry == self::$entry) {
					self::debug("part ignored [$path]");
					return false;
				}
				$controller = strtolower(implode("_", array_merge($zoneParts, [$_page])));
				$classname = self::$appNamespace . "\\" . self::snakeToNamespace($controller, "\\") . self::$setControllerSuffix;
				$file = realpath(self::$appDir . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $classname) . ".php");
				if (!self::loadFile($file)) {
					continue;
				}
				$type = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : "";
				if (class_exists($classname, false)) {
					$classInstance = new $classname($path_array);
					if ($type !== "" && in_array($type, self::$allowRequestTypes)) {
						$_method = $type . $_entry;
					} else {
						$_method = $_entry;
					}
					/**
					 * 方法必须存在并且可调用，才会被执行，否则返回false（private方法和protected方法都不可调用）
					 */
					if (method_exists($classInstance, $_method) && is_callable(array(&$classInstance, $_method))) {
						if (!$isPart) {
							self::$zone  = $_zone;
							self::$page  = $_page;
							self::$entry = $_entry;
						}
						return call_user_func(array(&$classInstance, $_method), $path_array);
					}
				}
			}
			self::debug("run patharray error ", $path_array);
			return false;
		}

		/**
		 * @var bool|int 调试开关；初始值为整数 0。
		 */
		private static $_debug = 0;

		/**
		 * debug
		 * @param mixed ...$args
		 * @return void
		 */
		private static function debug(mixed ...$args)
		{
			if (self::$_debug) {
				\SlightPHP\Log::debug($args);
			}
		}

		/**
		 * 将下划线分隔的字符串转换为命名空间格式
		 * @param string $str 下划线分隔的名称。
		 * @param string $split 输出部分之间的分隔符。
		 * @return string 首字母大写的各部分拼接结果。
		 */
		private static function snakeToNamespace(string $str, string $split = DIRECTORY_SEPARATOR): string
		{
			return implode($split, array_map('ucfirst', explode('_', strtolower($str))));
		}
		/**
		 * 加载文件
		 * @param string|false $file 已解析的文件路径；false 表示路径解析失败。
		 * @return bool
		 */
		private static function loadFile(string|false $file)
		{
			if ($file === false || $file === '') {
				return false;
			}
			if (!self::$appDirRealPath) {
				self::$appDirRealPath = realpath(self::$appDir);
				if (self::$appDirRealPath === false) {
					return false;
				}
			}
			if (!is_file($file)) {
				return false;
			}
			$prefix = rtrim(self::$appDirRealPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			if (!str_starts_with($file, $prefix)) {
				return false;
			}
			require_once $file;
			return true;
		}
	}
endif;
