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
	final class SlightPHP
	{
		/**
		 * @var string
		 */
		public static $appDir = ".";

		/**
		 * 框架模式，命令空间模式和文件分隔模式
		 */
		private static $modes = [
			"PAGE", //普通的文件分隔模式
			"NAMESPACE", //命名空间模式
		];
		private const MODE_PAGE = 'PAGE';
		private const MODE_NAMESPACE = 'NAMESPACE';
		/**
		 * 设置框架模式
		 * 1. 默认同时支持命名空间模式和文件分隔模式 PAGE,NAMESPACE
		 * 2. 如果只想使用命名空间模式，则设置为 NAMESPACE
		 * 3. 您可以设置优先顺序，默认是PAGE优先，NAMESPACE次之，如果您想让命名空间模式优先，则设置为 NAMESPACE,PAGE
		 * @param array $modes
		 */
		public static function setModes(array $modes)
		{
			self::$modes = $modes;
			return true;
		}
		public static function getModes()
		{
			return self::$modes;
		}
		/**
		 * @var string
		 */
		private static $appDirRealPath = "";

		/**
		 * namespace prefix of the pages, default "" (global class, like zone_page)
		 * @var string
		 */
		public static $appNamespace = "";

		/**
		 * @var string
		 */
		public static $pathInfo = "";

		/**
		 * set controller suffix, default "Controller"
		 * @var string
		 */
		private static $setControllerSuffix = "Controller";

		/**
		 * set controller suffix, default "Controller"
		 * @param string $suffix
		 * @return bool
		 */
		public static function setControllerSuffix(string $suffix)
		{
			self::$setControllerSuffix = $suffix;
			return true;
		}
		/**
		 * get controller suffix, default "Controller"
		 */
		public static function getControllerSuffix()
		{

			return self::$setControllerSuffix;
		}


		/**
		 * current zone
		 * @var string
		 */
		public static $zone;

		/**
		 * @var string
		 */
		public static $defaultZone = "zone";

		/**
		 * current page
		 * @var string
		 */
		public static $page;

		/**
		 * @var string
		 */
		public static $defaultPage = "page";

		/**
		 * current type
		 * @var string
		 */
		public static $type;

		/**
		 * default file type, like $defaultZone.$defaultType.php
		 * @var string
		 */
		public static $defaultType = "page";

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
		 * 设置允许的请求类型，这些类型会映射为方法前缀
		 * 默认PAGE模式下，方法以page开头
		 * 默认NAMESPACE模式下，方法不以任何开头
		 * @param array $types
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
		 * @var string
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
		 * @param string $namespace
		 * @return boolean
		 */
		public static function setAppNamespace($namespace)
		{
			self::$appNamespace = trim($namespace, "\\");
			return true;
		}

		/**
		 * @return string
		 */
		public static function getAppNamespace()
		{
			return self::$appNamespace;
		}
		/**
		 * zoneAlias
		 *
		 * @var array
		 */
		public static $zoneAlias;

		/**
		 * @param string $zone
		 * @param string $alias
		 * @return boolean
		 */
		public static function setZoneAlias(string $zone, string $alias)
		{
			self::$zoneAlias[$zone] = $alias;
			return true;
		}

		/**
		 * @param string $zone
		 * @return string | boolean
		 */
		public static function getZoneAlias(string $zone)
		{
			return isset(self::$zoneAlias[$zone]) ? self::$zoneAlias[$zone] : false;
		}

		/**
		 * pageAlias
		 *
		 * @var array
		 */
		public static $pageAlias;

		/**
		 * @param string $page
		 * @param string $alias
		 * @return boolean
		 */
		public static function setPageAlias(string $page, string $alias)
		{
			self::$pageAlias[$page] = $alias;
			return true;
		}

		/**
		 * @param string $page
		 * @return string | boolean
		 */
		public static function getPageAlias(string $page)
		{
			return isset(self::$pageAlias[$page]) ? self::$pageAlias[$page] : false;
		}

		/**
		 * defaultZone set
		 * 
		 * @param string $zone
		 * @return boolean
		 */

		public static function setDefaultZone(string $zone)
		{
			self::$defaultZone = $zone;
			return true;
		}
		/**
		 * defaultZone get
		 * 
		 * @return string
		 */

		public static function getDefaultZone()
		{
			return self::$defaultZone;
		}
		/**
		 * defaultClass set
		 *
		 * @param string $page
		 * @return boolean
		 */
		public static function setDefaultPage(string $page)
		{
			self::$defaultPage = $page;
			return true;
		}
		/**
		 * defaultPage get
		 *
		 * @return string
		 */
		public static function getDefaultPage()
		{
			return self::$defaultPage;
		}
		/**
		 * defaultType set
		 *
		 * @param string $type
		 * @return boolean
		 */
		public static function setDefaultType(string $type)
		{
			self::$defaultType = $type;
			return true;
		}
		/**
		 * defaultType get
		 *
		 * @return string
		 */
		public static function getDefaultType()
		{
			return self::$defaultType;
		}
		/**
		 * defaultMethod set
		 * 
		 * @param string $entry
		 * @return boolean
		 */
		public static function setDefaultEntry(string $entry)
		{
			self::$defaultEntry = $entry;
			return true;
		}
		/**
		 * defaultMethod get
		 * 
		 * @return string $method
		 */
		public static function getDefaultEntry()
		{
			return self::$defaultEntry;
		}
		/**
		 * splitFlag set
		 * 
		 * @param string $flag
		 * @return boolean
		 */
		public static function setSplitFlag(string $flag)
		{
			self::$splitFlag = $flag;
			return true;
		}
		/**
		 * defaultMethod get
		 * 
		 * @return string
		 */
		public static function getSplitFlag()
		{
			return self::$splitFlag;
		}
		/**
		 * appDir set && get
		 * @param string $dir
		 * @return boolean
		 */

		public static function setAppDir(string $dir)
		{
			self::$appDir = $dir;
			self::$appDirRealPath = realpath($dir);
			return true;
		}
		/**
		 * pathInfo set
		 * 
		 * @param string $pathInfo
		 * @return boolean
		 */
		public static function setPathInfo(string $pathInfo)
		{
			self::$pathInfo = $pathInfo;
			return true;
		}
		/**
		 * appDir get
		 * 
		 * @return string
		 */
		public static function getAppDir()
		{
			return self::$appDir;
		}
		/**
		 * debug status set
		 *
		 * @param boolean $debug
		 * @return boolean
		 */
		public static function setDebug(bool $debug)
		{
			self::$_debug = $debug;
			return true;
		}
		/**
		 * debug status get
		 * 
		 * @return boolean 
		 */
		public static function getDebug()
		{
			return self::$_debug;
		}

		/**
		 * main method!
		 *
		 * @param string $path
		 * @return boolean
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
			$path_array = preg_split("/[$splitFlag\/]/", $url, -1, PREG_SPLIT_NO_EMPTY);
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
				$classname = strtolower(implode("_", array_merge($zoneParts, [$_page])));
				$type = "";
				foreach (self::$modes as $_mode) {
					$file = "";
					if ($_mode == self::MODE_NAMESPACE) {
						$classname_namespace = self::$appNamespace . "\\" . self::snakeToNamespace($classname, "\\") . self::$setControllerSuffix;
						$file = realpath(self::$appDir . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $classname_namespace) . ".php");
						if (self::loadFile($file)) {
							$type   = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : "";
							$classname = $classname_namespace;
							break;
						}
					} elseif ($_mode == self::MODE_PAGE) {
						$type = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : self::$defaultType;
						$file = realpath(self::$appDir . DIRECTORY_SEPARATOR . str_replace("_", DIRECTORY_SEPARATOR, $classname) . ".page.php");
						if (self::loadFile($file)) {
							$type   = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : self::$defaultType;
							break;
						}
					}
				}
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
		 * @var boolean
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
				SLog::debug($args);
			}
		}

		/**
		 * 将下划线分隔的字符串转换为命名空间格式
		 * @param string $str
		 * @param string $split
		 * @return string
		 */
		private static function snakeToNamespace(string $str, string $split = DIRECTORY_SEPARATOR): string
		{
			return implode($split, array_map('ucfirst', explode('_', strtolower($str))));
		}
		/**
		 * 加载文件
		 * @param string $file
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
