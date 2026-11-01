<?php
/**
 * @param mixed $mixed 任意值。
 * @return string print_r 格式的文本。
 */
function tpl_modifier_tostring($mixed){
	return print_r($mixed,true);
}
/**
 * @param string $string 翻译原文。
 * @param string $zone 语言文件分区。
 * @return string 翻译结果或原文。
 */
function tpl_modifier_tr($string,$zone="main"){
	return SLanguage::tr($string,$zone);
}
/**
 * @param string $string 待转义文本。
 * @param string $type url 使用 URL 编码，其他值使用 HTML 转义。
 * @return string 转义后的文本。
 */
function tpl_modifier_escape($string,$type="html"){
	switch($type){
	case "url":return rawurlencode($string);
	default:
	case "html":return htmlspecialchars($string);
	}
}
/**
 * @param mixed $input 输入值。
 * @param mixed $default 输入为空时使用的默认值。
 * @return mixed 输入值或默认值。
 */
function tpl_modifier_default($input,$default=""){
	return empty($input)?$default:$input;
}
/**
 * @param string $string 原字符串。
 * @param string $version 追加在问号后的版本字符串。
 * @return string 拼接结果。
 */
function tpl_modifier_version($string,$version="1.0") {
    return $string."?".$version;
}
