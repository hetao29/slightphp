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
class SUtil{

	/**
	 * Get Request Value
	 * @param array $data ($_GET,$_POST)
	 * @param string $key
	 * @param bool $isnum true|false
	 */
	static function getRequestValue(array $data,$key,$isnum=false,$default=null,$minLength=0,$maxLength=100){
		if(!isset($data[$key]) || empty($data[$key]) || ($isnum && !is_numeric($data[$key])) ||
			strlen($data[$key])<$minLength || strlen($data[$key])>$maxLength) {
			return $default;
		}
		return $data[$key];
	}
	/**
	 *
	 */
	static function log($logFile,$data){
		error_log("[".date("Y-m-d H:i:s")."]$data\r\n",3,$logFile);
	}
	/**
	 *
	 */
	static function getIP($long=false) {
		$cip = getenv('HTTP_CLIENT_IP');
		$xip = getenv('HTTP_X_FORWARDED_FOR');
		$rip = getenv('REMOTE_ADDR');
		$srip = @$_SERVER['REMOTE_ADDR'];
		if($cip && strcasecmp($cip, 'unknown')) {
			$ip = $cip;
		} elseif($xip && strcasecmp($xip, 'unknown')) {
			$ip = $xip;
		} elseif($rip && strcasecmp($rip, 'unknown')) {
			$ip = $rip;
		} elseif($srip && strcasecmp($srip, 'unknown')) {
			$ip = $srip;
		}
		preg_match("/[\d\.]{7,15}/", $ip, $match);
		$ip = $match[0] ? $match[0] : 'unknown';
		if($long){
			return sprintf("%u",ip2long($ip));
		}
		return $ip;
	}

	static function validEmail($email){
		return preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])*(\.([a-z0-9])([-a-z0-9_-])([a-z0-9])+)*$/i',$email);
	}
	/**
	 * get substr support chinese
	 * return $str
	 */
	static function getSubStr($str,$length,$postfix='...',$encoding='UTF-8') {
		$realLen = mb_strwidth($str,$encoding);
		if(!is_numeric($length) or $length*2>=$realLen) {
			return htmlspecialchars($str, ENT_QUOTES,$encoding);
		}
        $str = mb_strimwidth($str,0,$length*2,$postfix,$encoding);
		return htmlspecialchars($str, ENT_QUOTES,$encoding);
	}
	/**
	 * get rand string
	 */
	static function getRandString($len) {
		$chars = array(
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
			'w', 'x', 'y', 'z',
			'0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
		);
		$charsLen = count($chars) - 1;
		shuffle($chars);
		$output = '';
		for ($i=0; $i<$len; $i++) {
			$output .= $chars[mt_rand(0, $charsLen)];
		}
		return $output;
	}

}
?>