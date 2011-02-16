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
	static $locale;
	//static $charset;
	static $defaultLocale;

	static $_languageCache;
	static public function tr($source,$zone="main"){
		$locales = array();
		if(!SLanguage::$defaultLocale){
			if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
				$l=explode(";",$_SERVER['HTTP_ACCEPT_LANGUAGE']);
				$t=explode(',',$l[0]);
				foreach($t as $locale){
					SLanguage::$defaultLocale[$locale] = strtolower($locale);
				}
			}
		}
		$locales = SLanguage::$defaultLocale;
		if(SLanguage::$locale){
			$locales[SLanguage::$locale] = SLanguage::$locale;
		}
		$locales = @array_reverse ($locales);

		foreach($locales as $locale){
			
			if(!empty(SLanguage::$_languageCache[$locale][$zone][$source])){
				return SLanguage::$_languageCache[$locale][$zone][$source];
			}

			$filename = SLanguage::$languageDir."/".$locale."/".$zone.".ini";
			if(file_exists($filename)){
				SLanguage::$_languageCache[$locale][$zone] = parse_ini_file($filename);
				if(!empty(SLanguage::$_languageCache[$locale][$zone][$source])){
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
