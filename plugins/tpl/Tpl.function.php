<?php
/**
 * @param mixed $mixed 任意值。
 * @return string print_r 格式的文本。
 */
function tpl_function_tostring($mixed){
	return print_r($mixed,true);
}
/**
 * 调用指定路径的控制器入口，收集直接输出及字符串返回值。
 * @param string $path 控制器路径；空字符串不调用入口。
 * @return string 片段 HTML；空路径或未匹配入口时为空字符串。
 * @throws \RuntimeException 模块遗留无法移除的输出缓冲。
 * @throws \Throwable 控制器异常会在清理可移除的输出缓冲后继续抛出。
 */
function tpl_function_part($path){
	if(empty($path)) return '';
	$level = ob_get_level();
	ob_start();
	try {
		$result = SlightPHP::run($path);
		if(is_string($result)) echo $result;
		// 将模块遗留的内部缓冲按顺序合入本次片段。
		while(ob_get_level() > $level + 1){
			$status = ob_get_status();
			if(($status['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE) === 0 || !ob_end_flush()){
				throw new \RuntimeException('Cannot remove fragment output buffer');
			}
		}
		return ob_get_contents();
	} finally {
		while(ob_get_level() > $level){
			$status = ob_get_status();
			// 无法移除的缓冲仍由 PHP 持有，停止清理并保留原异常。
			if(($status['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE) === 0 || !ob_end_clean()) break;
		}
	}
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
