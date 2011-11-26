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
class SLanguage{
	/**
	 *
	 */
	static $languageDir;
	//强制翻译语言
	static $locale;
	//默认语言，当其它都没有设置时，使用这个默认翻译
	static $defaultLocale;

	private static $_languageCache;
	private static $_locales=array();
	static public function tr($source,$zone="main"){
		if(empty(self::$_locales)){
			if(empty(self::$locale)){
				if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
					$l=@explode(",",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
					if(!empty($l)){
						foreach($l as $t){
							$t=@explode(';',$t);
							if(!empty($t)){
								$k = strtolower($t[0]);
								self::$_locales[$k] = $k;
							}
						}
					}
				}
			}else{
				$k = strtolower(self::$locale);
				self::$_locales[$k] = $k;
			}
			if(!empty(self::$defaultLocale)){
				$k = strtolower(self::$defaultLocale);
				self::$_locales[$k] = $k;
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
	static public function setLanguageDir($dir){
		self::$languageDir = $dir;
	}
	static public function getLanguageDir(){
		return self::$languageDir;
	}
	static public function setLocale($locale){
		self::$locale = $locale;
	}
	static public function getLocale(){
		return self::$locale;
	}
}
?>
