<?php

/**
 * SlightPHP
 * @version 6.5.0
 * @link https://slightphp.com/
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
		 * @return string 别名。
		 * @throws \SlightPHP\Exception 别名未设置，原因码 ALIAS_NOT_FOUND。
		 */
		public static function getZoneAlias(string $zone)
		{
			if (!isset(self::$zoneAlias[$zone])) {
				throw new \SlightPHP\Exception('Zone alias not found', \SlightPHP\Exception::ALIAS_NOT_FOUND, ['zone' => $zone]);
			}
			return self::$zoneAlias[$zone];
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
		 * @return string 别名。
		 * @throws \SlightPHP\Exception 别名未设置，原因码 ALIAS_NOT_FOUND。
		 */
		public static function getPageAlias(string $page)
		{
			if (!isset(self::$pageAlias[$page])) {
				throw new \SlightPHP\Exception('Page alias not found', \SlightPHP\Exception::ALIAS_NOT_FOUND, ['page' => $page]);
			}
			return self::$pageAlias[$page];
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
			// 尾部 /. 要求路径指向目录，避免调度时额外查询 is_dir()。
			self::$appDirRealPath = realpath(($dir === '' ? '.' : $dir) . DIRECTORY_SEPARATOR . '.');
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
		 * 与当前入口相同的局部调用抛出 RECURSIVE_DISPATCH 异常。
		 * 请求路径最多包含 maxDepth 段（默认 10），超限抛出 PATH_TOO_DEEP 异常。
		 * 上限包含区域、page、entry 和路径参数；完整路径数组通过 Request::route 提供。
		 * 未显式传路径时依次使用 pathInfo、PATH_INFO 和 REQUEST_URI。
		 *
		 * @param string $path 显式路径；空字符串时从 pathInfo 或服务器请求信息读取。
		 * @return mixed 入口方法的原始返回值，包括业务代码主动返回的 false。
		 * 响应已通过 end()/redirect() 结束时返回 null，避免入口重复输出返回值。
		 * @throws \SlightPHP\Exception 路径、控制器或入口不可用，或 Route 方法限制不匹配。
		 * 核心不自动输出异常响应；控制器和注解自身的异常原样传播。
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
					self::debug('Request path not set');
					throw new \SlightPHP\Exception('Request path not set', \SlightPHP\Exception::PATH_NOT_SET);
				}
			}
			$len = strlen($path);
			$start = 0;
			for ($start = 0; $start < $len; $start++) {
				if ($path[$start] != '/') break;
			}

			$url_parsed = parse_url(substr($path, $start), PHP_URL_PATH);
			if ($url_parsed === false) {
				throw new \SlightPHP\Exception('Invalid request path', \SlightPHP\Exception::INVALID_PATH, ['path' => $path]);
			}
			if (!empty($url_parsed)) {
				$url = $url_parsed;
			} else {
				$url = "";
			}
			$path_array = preg_split("/[$splitFlag\/]+/", $url, min(self::$maxDepth, PHP_INT_MAX - 1) + 1, PREG_SPLIT_NO_EMPTY);
			if (count($path_array) > self::$maxDepth) {
				self::debug('Request path exceeds maxDepth');
				throw new \SlightPHP\Exception('Request path exceeds maxDepth', \SlightPHP\Exception::PATH_TOO_DEEP, ['path' => $path, 'maxDepth' => self::$maxDepth]);
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
			if (!self::$appDirRealPath) {
				self::setAppDir(self::$appDir);
			}
			if (self::$appDirRealPath === false) {
				throw new \SlightPHP\Exception('Invalid application directory', \SlightPHP\Exception::APP_DIR_INVALID, ['appDir' => self::$appDir]);
			}
			// 候选查找只记录诊断，成功回退时不构造最终失败异常。
			$failure = null;
			$missingFile = null;
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
					self::debug('Internal dispatch targets the current entry', $path);
					throw new \SlightPHP\Exception('Internal dispatch targets the current entry', \SlightPHP\Exception::RECURSIVE_DISPATCH, ['path' => $path]);
				}
				$controller = strtolower(implode("_", array_merge($zoneParts, [$_page])));
				$classname = self::$appNamespace . "\\" . self::snakeToNamespace($controller, "\\") . self::$setControllerSuffix;
				$candidate = self::$appDir . DIRECTORY_SEPARATOR . str_replace("\\", DIRECTORY_SEPARATOR, $classname) . ".php";
				try {
					$file = self::resolveControllerFile($candidate);
				} catch (\SlightPHP\Exception $error) {
					if ($error->getCode() !== \SlightPHP\Exception::FILE_NOT_ALLOWED) {
						throw $error;
					}
					$failure = [$error->getMessage(), $error->getCode(), ['path' => $path, 'controller' => $classname] + $error->getContext(), $error];
					continue;
				}
				if ($file === false) {
					// 普通缺失不抛异常，也不覆盖已找到控制器的具体失败原因。
					$missingFile ??= ['path' => $path, 'controller' => $classname, 'file' => $candidate];
					continue;
				}
				require_once $file;
				$type = !empty($_SERVER['HTTP_REQUEST_TYPE']) ? strtolower(trim($_SERVER['HTTP_REQUEST_TYPE'])) : "";
				if (class_exists($classname, false)) {
					$request = new \SlightPHP\Request();
					$request->route = $path_array;
					if ($type !== "" && in_array($type, self::$allowRequestTypes)) {
						$_method = $type . $_entry;
					} else {
						$_method = $_entry;
					}
					if (method_exists($classname, $_method)) {
						$methods = self::getRouteMethods($classname, $_method);
						if ($methods !== false && $methods !== [] && !in_array(strtoupper((string) $request->getMethod()), $methods, true)) {
							throw new \SlightPHP\Exception('HTTP method not allowed', \SlightPHP\Exception::METHOD_NOT_ALLOWED, [
								'path' => $path, 'controller' => $classname, 'method' => $_method,
								'requestMethod' => $request->getMethod(), 'allowedMethods' => $methods,
							]);
						}
					}
					$classInstance = new $classname($request);
					/**
					 * 方法必须存在并且可调用；private 和 protected 方法不可调用。
					 */
					if (method_exists($classInstance, $_method) && is_callable(array(&$classInstance, $_method))) {
						if (!$isPart) {
							self::$zone  = $_zone;
							self::$page  = $_page;
							self::$entry = $_entry;
						}
						$response = new \SlightPHP\Response();
						$result = $classInstance->$_method($request, $response);
						return $response->isWritable() ? $result : null;
					}
					$failure = ['Controller entry is missing or not callable',
						method_exists($classInstance, $_method) ? \SlightPHP\Exception::METHOD_NOT_CALLABLE : \SlightPHP\Exception::METHOD_NOT_FOUND,
						['path' => $path, 'controller' => $classname, 'method' => $_method]];
				} else {
					$failure = ['Controller class not found', \SlightPHP\Exception::CLASS_NOT_FOUND, ['path' => $path, 'controller' => $classname, 'file' => $file]];
				}
			}
			self::debug('Controller dispatch failed', $path_array);
			if ($failure !== null) {
				throw new \SlightPHP\Exception(...$failure);
			}
			throw new \SlightPHP\Exception('Controller file not found', \SlightPHP\Exception::FILE_NOT_FOUND, $missingFile);
		}

		/**
		 * 按实际控制器与入口缓存 Route 元数据；命中后不再创建反射对象。
		 * 不缓存当前请求的匹配结果；非公开方法不解析注解。
		 * @return string[]|false 允许的 HTTP 方法；未限制为空数组，非公开方法为 false。
		 */
		private static function getRouteMethods(string $classname, string $method): array|false
		{
			static $cache = [];
			$key = strtolower($classname . '::' . $method);
			if (!isset($cache[$key])) {
				$reflection = new \ReflectionMethod($classname, $method);
				if (!$reflection->isPublic()) {
					return $cache[$key] = false;
				}
				$attributes = $reflection->getAttributes(\SlightPHP\Route::class);
				$cache[$key] = $attributes === [] ? [] : $attributes[0]->newInstance()->getMethods();
			}
			return $cache[$key];
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
		 * 解析并校验候选控制器文件；文件加载在调用方完成，避免吞掉文件自身的异常。
		 * @param string $candidate 候选文件路径。
		 * @return string|false 规范文件路径；候选不存在或不是普通文件时返回 false。
		 * @throws \SlightPHP\Exception 应用目录无效或文件越界。
		 */
		private static function resolveControllerFile(string $candidate): string|false
		{
			$file = realpath($candidate);
			if ($file === false) {
				return false;
			}
			if (!self::$appDirRealPath) {
				self::setAppDir(self::$appDir);
			}
			if (self::$appDirRealPath === false) {
				throw new \SlightPHP\Exception('Invalid application directory', \SlightPHP\Exception::APP_DIR_INVALID, ['appDir' => self::$appDir]);
			}
			if (!is_file($file)) {
				return false;
			}
			$prefix = rtrim(self::$appDirRealPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
			if (!str_starts_with($file, $prefix)) {
				throw new \SlightPHP\Exception('Controller file is outside application directory', \SlightPHP\Exception::FILE_NOT_ALLOWED, ['file' => $file]);
			}
			return $file;
		}
	}
endif;
