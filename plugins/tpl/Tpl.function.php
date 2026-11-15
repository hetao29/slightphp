<?php
/**
 * @param mixed $mixed 任意值。
 * @return string print_r 格式的文本。
 */
function tpl_function_tostring($mixed){
	return print_r($mixed,true);
}
/**
 * 调用指定路径的控制器入口，直接输出模块内容及字符串返回值。
 * @param string $path 控制器路径；空字符串不调用入口。
 * @return null 不返回片段内容；空路径或未匹配入口时不输出。
 * @throws \Throwable 控制器或模板异常原样抛出，已输出内容保留。
 */
function tpl_function_part($path){
	if(empty($path)) return;
	$result = SlightPHP::run($path);
	if(is_string($result)) echo $result;
}
/**
 * 使用指定实例输出子模板；模板 include 标签会自动传入当前实例。
 * @param string $tpl 相对于模板目录的文件名。
 * @param \SlightPHP\Tpl $context 父模板使用的实例。
 * @return null
 */
function tpl_function_include($tpl, \SlightPHP\Tpl $context){
	return $context->fetch($tpl);
}
