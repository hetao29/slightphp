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


require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."captcha/captcha.php");
class SCaptcha extends SimpleCaptcha{
	static $session_prefix="SCaptcha_";
	function __construct(){
		$this->wordsFile = "";
		$this->session_var = SCaptcha::$session_prefix ;
		$this->minWordLength = 4;
		$this->maxWordLength = 5;
		$this->width = 140;
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
		if(	!empty($_SESSION[SCaptcha::$session_prefix . $captcha_code])){
			unset ($_SESSION[SCaptcha::$session_prefix . $captcha_code]);
		}
	}
		

}
?>