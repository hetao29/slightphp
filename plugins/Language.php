<?php

namespace SlightPHP;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 通过 Config 从语言目录中的 JSON/JSONC 对象查找翻译。
 * @package SlightPHP
 */
class Language{
	/**
	 * @var string|null 语言文件根目录，按 locale/zone.json 或 locale/zone.jsonc 组织；设置前为 null。
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
	 * 词条必须是字符串；语言文件缺失时继续回退，格式或结构错误时抛出异常。
	 * @param string $source 原文或词条键，对应 JSON 对象的属性名。
	 * @param string $zone 语言文件名（不含 .json/.jsonc），同名文件优先使用 .json。
	 * @return string 翻译结果或原文。
	 * @throws \JsonException JSON/JSONC 解析失败。
	 * @throws \InvalidArgumentException 语言文件不是 JSON 对象或词条不是字符串。
	 * @throws \RuntimeException 语言文件无法读取。
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
			if(!isset(self::$_languageCache[$locale][$zone])){
				foreach(['json', 'jsonc'] as $extension){
					$path = $prefix.$locale.DIRECTORY_SEPARATOR.$zone.'.'.$extension;
					$filename = realpath($path);
					if($filename === false || !str_starts_with($filename, $prefix) || !is_file($filename)) continue;
					$config = Config::parse($path);
					if(!$config instanceof \stdClass){
						throw new \InvalidArgumentException('Language file must be a JSON object: '.$path);
					}
					$translations = get_object_vars($config);
					foreach($translations as $translation){
						if(!is_string($translation)){
							throw new \InvalidArgumentException('Language translations must be strings: '.$path);
						}
					}
					self::$_languageCache[$locale][$zone] = $translations;
					break;
				}
			}
			if(isset(self::$_languageCache[$locale][$zone][$source])){
				return self::$_languageCache[$locale][$zone][$source];
			}
		}
		return $source;
	}
	/**
	 * 设置语言文件根目录，并清空 Language 已加载的翻译缓存；不清理 Config 缓存。
	 * @param string $dir 语言文件根目录。
	 * @return void
	 */
	static public function setLanguageDir($dir){
		self::$languageDir = $dir;
		self::$_languageCache = [];
	}
	/** @return string|null 语言文件根目录。 */
	static public function getLanguageDir(){
		return self::$languageDir;
	}
	/**
	 * 设置强制语言，并清空候选语言列表，下次翻译时重新选择；保留已加载词条。
	 * @param string|null $locale 强制使用的语言标识。
	 * @return void
	 */
	static public function setLocale($locale){
		self::$locale = $locale;
		self::$_locales = [];
	}
	/**
	 * 清除当前请求的强制语言及候选列表，下次翻译时重新读取请求语言。
	 * 保留语言目录、defaultLocale、已加载词条及 Config 缓存。
	 * 仅重置 Language 的静态请求状态，不提供并发请求隔离。
	 * @return void
	 */
	static public function resetRequest(): void {
		self::$locale = null;
		self::$_locales = [];
	}
	/** @return string|null 强制使用的语言标识。 */
	static public function getLocale(){
		return self::$locale;
	}
}

?>
