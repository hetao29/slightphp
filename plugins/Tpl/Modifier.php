<?php
namespace SlightPHP\Tpl;

/**
 * 兼容模式的内置修饰器集合，保留历史参数和返回行为。
 *
 * 兼容编译器以被修饰值作为第一个参数调用；在 PHP 代码中也可直接使用静态方法。
 * 自动模式同样支持这些修饰器，先转换数据再按实际输出位置编码。escape 的输出
 * 意图由 ModifierValue 传递给 Context，避免重复 HTML/URL 编码；raw 应放在链末尾。
 */
final class Modifier
{
	/**
	 * 返回原值，不执行编码。模板管道末尾使用 raw 表示最终结果是调用方负责的可信原文。
	 * PHP 直接调用只返回数据本身，不会赋予字符串跨上下文的安全属性。
	 * @param mixed $value 待输出的可信值。
	 * @return mixed 原值，实际原文输出仍遵守 Tpl 对标量/Stringable 的要求。
	 */
	public static function raw($value) { return $value; }

	/**
	 * 返回任意值的 print_r 文本，不自动添加 HTML 编码。
	 * @param mixed $mixed 任意值。
	 * @return string print_r 格式的文本。
	 */
	public static function tostring($mixed){
		return print_r($mixed,true);
	}
	/**
	 * 使用 Language 的当前语言及词条回退规则翻译，不编码翻译结果。
	 * @param string $string 翻译原文。
	 * @param string $zone 语言文件分区。
	 * @return string 翻译结果或原文。
	 */
	public static function tr($string,$zone="main"){
		return \SlightPHP\Language::tr($string,$zone);
	}
	/**
	 * 手动 HTML 文本/带引号属性编码，或将输入作为一个 URL 组成部分编码。
	 *
	 * 仅 type 为小写 url 时使用 rawurlencode；其他类型沿用 HTML 分支，固定 UTF-8、
	 * 包含单双引号并替换无效字节。不检查 URL 协议，也不用于 JS/CSS 字符串编码。
	 * @param string $string 待转义文本。
	 * @param string $type url 使用 URL 编码，其他值使用 UTF-8 HTML 转义，包含单双引号。
	 * @return string 转义后的文本。
	 */
	public static function escape($string,$type="html"){
		switch($type){
		case "url":return rawurlencode($string);
		default:
		case "html":return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		}
	}
	/**
	 * 使用 PHP empty 语义选择缺省值，不只判断是否定义或是否为 null。
	 * false、0、"0"、空字符串和空数组等都会落到缺省分支；不改变该历史兼容行为。
	 * @param mixed $input 输入值。
	 * @param mixed $default 输入为空时使用的默认值。
	 * @return mixed 输入值或默认值。
	 */
	public static function default($input,$default=""){
		return empty($input)?$default:$input;
	}
	/**
	 * 直接在原字符串末尾追加问号和版本文本，保留历史拼接方式。
	 * 不解析已有查询参数/片段，不校验 URL 协议，也不对结果做 HTML 编码。
	 * @param string $string 原字符串。
	 * @param string $version 追加在问号后的版本字符串。
	 * @return string 拼接结果。
	 */
	public static function version($string,$version="1.0") {
	    return $string."?".$version;
	}
}
