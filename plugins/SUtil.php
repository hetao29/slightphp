<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * 请求参数、日志、地址和字符串辅助方法。
 * @package SlightPHP
 */
class SUtil{

	/**
	 * 读取并检查请求值；PHP empty 判定为空的值（包括 0 和 "0"）、
	 * 非数字或长度不合要求时返回默认值。
	 * @param array $data 请求数据，如 $_GET 或 $_POST。
	 * @param string|int $key 参数键。
	 * @param bool $isnum 是否要求值为数字或数字字符串。
	 * @param mixed $default 校验失败时返回的值。
	 * @param int $minLength 最小字节长度。
	 * @param int $maxLength 最大字节长度，0 表示不限。
	 * @return mixed 原值或默认值。
	 */
	static function getRequestValue(array $data,$key,$isnum=false,$default=null,$minLength=0,$maxLength=0){
		if(!isset($data[$key]) || empty($data[$key]) || ($isnum && !is_numeric($data[$key])) ||
			strlen($data[$key])<$minLength || ($maxLength!=0 && strlen($data[$key])>$maxLength)) {
			return $default;
		}
		return $data[$key];
	}
	/**
	 * 追加带时间戳的日志。
	 * @param string $logFile 日志文件路径。
	 * @param string $data 日志内容。
	 * @return void
	 */
	static function log($logFile,$data){
		error_log("[".date("Y-m-d H:i:s")."]$data\r\n",3,$logFile);
	}
	/**
	 * 按 CLIENT_IP、X_FORWARDED_FOR、REMOTE_ADDR 顺序提取类似 IPv4 的文本。
	 * 该方法不验证转发头的可信度，也不完整验证 IP 地址。
	 * @param bool $long 是否返回无符号十进制形式。
	 * @return string 提取到的文本、无符号十进制字符串或 unknown（仅非 long 模式）。
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

	/**
	 * 使用旧版 eregi 检查邮箱；PHP 7 及以后需由调用方提供兼容实现。
	 * @param string $email 待验证地址。
	 * @return int|false 正则匹配结果。
	 */
	static function validEmail($email){
		return eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email);
	}
	/**
	 * 按显示宽度截取并转义 HTML 特殊字符。
	 * @param string $str 原字符串。
	 * @param int|float|string $length 截取长度，按两倍显示宽度计算；非数字时不截取。
	 * @param string $postfix 截断时附加的字符串。
	 * @param string $encoding 字符编码。
	 * @return string 转义后的字符串。
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
	 * 生成小写字母和数字组成的随机字符串。
	 * @param int $len 字符数。
	 * @return string 随机字符串（不适用于密码学用途）。
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
