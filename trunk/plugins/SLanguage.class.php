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

	static $_languageCache;
	static $_loadedFile;
	static public function tr($source,$zone="main"){
		$locales = array();
		if(empty(SLanguage::$locale)){
			if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
				$l=explode(";",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
				$t=explode(',',$l[0]);
				foreach($t as $locale){
					$k = strtolower($locale);
					$locales[$k] = $k;
				}
			}
		}else{
			$k = strtolower(SLanguage::$locale);
			$locales[$k] = $k;
		}
		if(!empty(SLanguage::$defaultLocale)){
			$k = strtolower(SLanguage::$defaultLocale);
			$locales[$k] = $k;
		}
		if(empty($locales) || !is_array($locales))return $source;

		foreach($locales as $locale){
			
			if(isset(SLanguage::$_languageCache[$locale][$zone][$source])){
				return SLanguage::$_languageCache[$locale][$zone][$source];
			}

			$filename = SLanguage::$languageDir."/".$locale."/".$zone.".ini";
			if(file_exists($filename) && !isset(SLanguage::$_loadedFile[$filename])){
				SLanguage::$_loadedFile[$filename]=1;
				SLanguage::$_languageCache[$locale][$zone] = parse_ini_file($filename);
				if(isset(SLanguage::$_languageCache[$locale][$zone][$source])){
					return SLanguage::$_languageCache[$locale][$zone][$source];
				}
			}
		}
		return $source;
	}
	static public function setLanguageDir($dir){
		SLanguage::$languageDir = $dir;
	}
	static public function getLanguageDir(){
		return SLanguage::$languageDir;
	}
	static public function setLocale($locale){
		SLanguage::$locale = $locale;
	}
	static public function getLocale(){
		return SLanguage::$locale;
	}
}
?>
