<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 从语言目录中的 INI 文件查找翻译。
 * @package SlightPHP
 */
class SLanguage{
	/**
	 * @var string|null 语言文件根目录，按 locale/zone.ini 组织；设置前为 null。
	 */
	static $languageDir;
	/** @var string|null 强制使用的语言。 */
	static $locale;
	/** @var string|null 请求语言列表包含该语言时优先使用。 */
	static $defaultLocale;

	private static $_languageCache;
	private static $_locales=array();
	/**
	 * 按指定语言或请求的 Accept-Language 顺序查找翻译，找不到时返回原文。
	 * 请求语言按出现顺序处理，不按 q 值排序；defaultLocale 在请求列表中时
	 * 会成为唯一候选语言。
	 * @param string $source 原文，同时作为 INI 键。
	 * @param string $zone 语言文件名（不含 .ini）。
	 * @return string 翻译结果或原文。
	 */
	static public function tr($source,$zone="main"){
		if(empty(self::$_locales)){
			if(!empty(self::$locale)){
				$k = strtolower(self::$locale);
				self::$_locales[$k] = $k;
			}else{
				$langs = array();
				if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
					$l=explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
					if(!empty($l)){
						foreach($l as $t){
							$t=explode(';',$t);
							if(!empty($t)){
								$k = strtolower($t[0]);
								$langs[$k] = $k;
							}
							$t2 = explode('-',$t[0]);
							$k2 = strtolower($t2[0]);
							if(!isset($langs[$k2])) $langs[$k2] = $k2;
						}
					}
				}
				if(!empty(self::$defaultLocale) && in_array(strtolower(self::$defaultLocale) , $langs)){
					$k = strtolower(self::$defaultLocale);
					self::$_locales[$k] = $k;
				}else{
					self::$_locales = $langs;
				}
			}
		}
		if(empty(self::$_locales) || !is_array(self::$_locales))return $source;

		foreach(self::$_locales as $locale){
			
			if(isset(self::$_languageCache[$locale][$zone][$source])){
				return self::$_languageCache[$locale][$zone][$source];
			}

			$filename = self::$languageDir."/".$locale."/".$zone.".ini";
			if(is_file($filename) && !isset(self::$_languageCache[$locale][$zone])){
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
