<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @deprecated
 * recommend https://github.com/Gregwar/Captcha
 */

if(session_status()!=PHP_SESSION_ACTIVE)session_start();

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."captcha/captcha.php");
/**
 * @package SlightPHP
 */
class SCaptcha extends SlightPHP\SimpleCaptcha{
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
		$this->transprent=false;
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
