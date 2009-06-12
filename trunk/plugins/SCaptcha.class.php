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


require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."captcha/captcha.php");
class SCaptcha extends SimpleCaptcha{
	static $session_prefix="SCaptcha_";
	function __construct(){
		$this->wordsFile = "";
		$this->session_var = SCaptcha::$session_prefix ;
		$this->minWordLength = 4;
		$this->maxWordLength = 5;
		$this->width = 150;
		$this->height = 50;
		$this->Yamplitude = 6;
		$this->Xamplitude = 4;
		$this->scale=3;
		$this->blur = true;
		$this->imageFormat="png";
	}
	static function check($captcha_code){
		if(	empty($_SESSION[SCaptcha::$session_prefix . $captcha_code]) ||
			$_SESSION[SCaptcha::$session_prefix . $captcha_code] != $captcha_code
		){
			return false;
		}else{
			return true;
		}
	}
	static function del($captcha_code){
		if(	empty($_SESSION[SCaptcha::$session_prefix . $captcha_code])){
			unset ($_SESSION[SCaptcha::$session_prefix . $captcha_code]);
		}
	}
		

}
?>