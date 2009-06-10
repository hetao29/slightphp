<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
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
					SLanguage::$defaultLocale[$locale] = $locale;
				}
			}
		}
		$locales = SLanguage::$defaultLocale;
		if(SLanguage::$locale){
			$locales[SLanguage::$locale] = SLanguage::$locale;
		}
		$locales = array_reverse ($locales);

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
