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
if (!class_exists("SlightPHP", false)):
	final class SlightPHP
	{
		/**
		 * @var string
		 */
		public static $appDir = ".";

		/**
		 * @var string
		 */
		public static $pathInfo = "";

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
		 * current entry
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
		public static function setZoneAlias($zone, $alias)
		{
			self::$zoneAlias[$zone] = $alias;
			return true;
		}

		/**
		 * @param string $zone
		 * @return string | boolean
		 */
		public static function getZoneAlias($zone)
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
		public static function setPageAlias($page, $alias)
		{
			self::$pageAlias[$page] = $alias;
			return true;
		}

		/**
		 * @param string $page
		 * @return string | boolean
		 */
		public static function getPageAlias($page)
		{
			return isset(self::$pageAlias[$page]) ? self::$pageAlias[$page] : false;
		}

		/**
		 * defaultZone set
		 * 
		 * @param string $zone
		 * @return boolean
		 */

		public static function setDefaultZone($zone)
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
		public static function setDefaultPage($page)
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
		 * @param string $page
		 * @return boolean
		 */
		public static function setDefaultType($type)
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
		public static function setDefaultEntry($entry)
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
		public static function setSplitFlag($flag)
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
		 * IMPORTANT: you must set absolute path if you use extension mode(extension=SlightPHP.so)
		 *
		 * @param string $dir
		 * @return boolean
		 */

		public static function setAppDir($dir)
		{
			self::$appDir = $dir;
			return true;
		}
		public static function setPathInfo($pathInfo)
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
		public static function setDebug($debug)
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
				if (class_exists($classname, true)) {
					$classInstance = new $classname($path_array);
					$type   = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : self::$defaultType;
					$_method = $type . $_entry;
				} else {
					$classname = self::snakeToNamespace($classname, "\\") . "Controller";
					if (class_exists($classname, true)) {
						$classInstance = new $classname($path_array);
					}
					$type   = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : "";
					$_method = $type . $_entry;
				}
				if (class_exists($classname, false) && method_exists($classInstance, $_method)) {
					if (!$isPart) {
						self::$zone  = $_zone;
						self::$page  = $_page;
						self::$entry = $_entry;
					}
					return call_user_func(array(&$classInstance, $_method), $path_array);
				}
			}
			self::debug("run patharray error ", $path_array);
			return false;
		}

		/**
		 * @var boolean
		 */
		private static $_debug = 0;

		/*private*/
		private static function debug(...$args)
		{
			if (self::$_debug) {
				SLog::debug($args);
			}
		}
		/*private*/
		private static function snakeToNamespace(string $str, $split = DIRECTORY_SEPARATOR): string
		{
			return implode($split, array_map('ucfirst', explode('_', strtolower($str))));
		}
	}
	/**
	 * 加载传统的_分隔的类
	 */
	spl_autoload_register(function ($class) {
		$file = realpath(SlightPHP::$appDir . DIRECTORY_SEPARATOR . str_replace("_", DIRECTORY_SEPARATOR, $class) . ".page.php");
		if (is_file($file)) {
			require_once(realpath($file));
		}
	});
	/**
	 * 加载命令空间的类
	 */
	spl_autoload_register(function ($class) {
		$file = realpath(SlightPHP::$appDir . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $class) . ".php");
		if (is_file($file)) {
			require_once(realpath($file));
		}
	});
endif;
