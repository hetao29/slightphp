<?php

namespace SlightPHP;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * JSON 编解码及最近一次错误的便捷接口。
 * @package SlightPHP
 */
class Json{
	/**
	 * 将值编码为 JSON。
	 *
	 * @param mixed $object 待编码的值。
	 * @param int $options json_encode 选项位掩码。
	 * @param int $depth 最大嵌套深度。
	 * @return string|false JSON 字符串，编码失败时为 false。
	 * @throws \JsonException 设置 JSON_THROW_ON_ERROR 且编码失败时。
	 * @throws \ValueError 深度参数超出有效范围时。
	 * @access   public
	 */
	public static function encode($object, $options = 0, $depth = 512){
		return json_encode($object, $options, $depth);
	}
	/**
	 * 将 JSON 字符串解码为 PHP 值。
	 *
	 * @param string $str JSON 字符串。
	 * @param bool|null $assoc 是否将对象解码为关联数组；null 使用选项中的设置。
	 * @param int $depth 最大嵌套深度。
	 * @param int $options json_decode 选项位掩码。
	 * @return mixed 解码后的值；失败时通常为 null。
	 * @throws \JsonException 设置 JSON_THROW_ON_ERROR 且解码失败时。
	 * @throws \ValueError 深度参数超出有效范围时。
	 * @access public
	 */
	public static function decode($str, $assoc = false, $depth = 512, $options = 0){
		return json_decode($str, $assoc, $depth, $options);
	}
	/**
	 * get last json encode/decode error code
	 * @return int
	 * @access public
	 */
	public static function error(){
		return json_last_error();
	}
	/**
	 * get last json encode/decode error msg
	 * @return string
	 * @access public
	 */
	public static function errorMsg(){
		if (!function_exists('json_last_error_msg')){
			$ERRORS = array(
				JSON_ERROR_NONE => 'No error',
				JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
				JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
				JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
				JSON_ERROR_SYNTAX => 'Syntax error',
				JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
			);

			$error = json_last_error();
			return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
		}
		return json_last_error_msg();
	}
}

