<?php
/**
 * @param mixed $mixed 任意值。
 * @return string print_r 格式的文本。
 */
function tpl_function_tostring($mixed){
	return print_r($mixed,true);
}
/**
 * 开启输出缓冲并调用指定路径的控制器入口。
 * @param string $path 控制器路径；空字符串不调用入口。
 * @return mixed 入口返回值或空字符串。
 */
function tpl_function_part($path){
	ob_start();
	return !empty($path)?SlightPHP::run($path):"";
}
/**
 * 输出指定模板。
 * @param string $tpl 相对于模板目录的文件名。
 * @return null
 */
function tpl_function_include($tpl){
	return \SlightPHP\Tpl::fetch($tpl);
}
