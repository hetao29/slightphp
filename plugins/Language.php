<?php

namespace SlightPHP;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 从语言目录中的 INI 文件查找翻译。
 * @package SlightPHP
 */
class Language{
	/**
	 * @var string|null 语言文件根目录，按 locale/zone.ini 组织；设置前为 null。
	 */
	static $languageDir;
	/** @var string|null 强制使用的语言。 */
	static $locale;
	/** @var string|null 请求语言列表包含该语言时优先使用。 */
	static $defaultLocale;

	private static $_languageCache=[];
	private static $_locales=[];
	/**
	 * 按指定语言或请求的 Accept-Language 顺序查找翻译，找不到时返回原文。
	 * 请求语言按出现顺序处理，不按 q 值排序；defaultLocale 在请求列表中时
	 * 会成为唯一候选语言。非法请求语言及 q=0 项会被忽略，路径必须位于语言目录内。
	 * @param string $source 原文，同时作为 INI 键。
	 * @param string $zone 语言文件名（不含 .ini）。
	 * @return string 翻译结果或原文。
	 */
	static public function tr($source,$zone="main"){
		if(empty(self::$_locales)){
			if(self::$locale !== null && self::$locale !== ''){
				if(!is_string(self::$locale) || strlen(self::$locale) > 255 ||
					!preg_match('/\A[a-z]{1,8}(?:[-_][a-z0-9]{1,8})*\z/i', self::$locale)) return $source;
				$k = strtolower(self::$locale);
				self::$_locales[$k] = $k;
			}else{
				$langs = [];
				$header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
				// 仅接受语言标签及合法 q 值；不解码或删除危险字符后再拼路径。
				if(is_string($header) && strlen($header) <= 8192 && strpbrk($header, "\r\n\0") === false){
					foreach(array_slice(explode(',', $header), 0, 32) as $range){
						if(!preg_match('/\A([a-z]{1,8}(?:-[a-z0-9]{1,8})*)(?:[ \t]*;[ \t]*q=(0(?:\.[0-9]{0,3})?|1(?:\.0{0,3})?))?\z/i', trim($range, " \t"), $match)) continue;
						if(strlen($match[1]) > 255 || (isset($match[2]) && (float)$match[2] === 0.0)) continue;
						$k = strtolower($match[1]);
						$langs[$k] = $k;
						$base = explode('-', $k, 2)[0];
						if(!isset($langs[$base])) $langs[$base] = $base;
					}
				}
				if(is_string(self::$defaultLocale) && in_array(strtolower(self::$defaultLocale), $langs, true)){
					$k = strtolower(self::$defaultLocale);
					self::$_locales[$k] = $k;
				}else{
					self::$_locales = $langs;
				}
			}
		}
		if(empty(self::$_locales) || !is_array(self::$_locales))return $source;

		if(!is_string($zone) || !preg_match('/\A[a-z0-9_][a-z0-9_.-]*\z/i', $zone)) return $source;
		$root = is_string(self::$languageDir) && self::$languageDir !== '' ? realpath(self::$languageDir) : false;
		if($root === false || !is_dir($root)) return $source;
		$prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

		foreach(self::$_locales as $locale){
			
			if(isset(self::$_languageCache[$locale][$zone][$source])){
				return self::$_languageCache[$locale][$zone][$source];
			}

			$filename = realpath($prefix.$locale.DIRECTORY_SEPARATOR.$zone.".ini");
			if($filename !== false && str_starts_with($filename, $prefix) && is_file($filename) && !isset(self::$_languageCache[$locale][$zone])){
				self::$_languageCache[$locale][$zone] = parse_ini_file($filename);
				if(isset(self::$_languageCache[$locale][$zone][$source])){
					return self::$_languageCache[$locale][$zone][$source];
				}
			}
		}
		return $source;
	}
	/**
	 * 设置语言文件根目录；已加载的翻译缓存不会清除。
	 * @param string $dir 语言文件根目录。
	 * @return void
	 */
	static public function setLanguageDir($dir){
		self::$languageDir = $dir;
	}
	/** @return string|null 语言文件根目录。 */
	static public function getLanguageDir(){
		return self::$languageDir;
	}
	/**
	 * 设置强制语言；已初始化的语言列表不会因此重建。
	 * @param string|null $locale 强制使用的语言标识。
	 * @return void
	 */
	static public function setLocale($locale){
		self::$locale = $locale;
	}
	/** @return string|null 强制使用的语言标识。 */
	static public function getLocale(){
		return self::$locale;
	}
}

?>
