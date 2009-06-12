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


class SUtil{

	/**
	 *
	 */
	static function getRequestValue(array $data,$key,$minLength,$maxLength){
		if(empty($data[$key]) || strlen($data[$key])<$minLength || strlen($data[$key])>$maxLength){
			return false;
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
}
?>