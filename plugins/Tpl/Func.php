<?php
namespace SlightPHP\Tpl;

/**
 * 内置模板函数的静态实现，也可在应用 PHP 代码中直接调用。
 *
 * 编译器将对应标签映射到本类，对 include/part 单独生成输出调用，
 * 保持父实例及真实上下文，普通返回值函数则由调用位置决定编码。
 * 本类不导出旧的 tpl_function_* 全局函数，不维护共享模板变量或全局 Tpl 实例。
 */
final class Func
{
	/**
	 * 将任意值转为可读文本，便于模板展示结构化调试信息。
	 * 返回原始 print_r 文本，不在函数内部编码；模板根据实际输出位置编码。
	 * @param mixed $mixed 任意值。
	 * @return string print_r 格式的文本。
	 */
	public static function tostring($mixed){
		return print_r($mixed,true);
	}
	/**
	 * 调用指定路径的控制器入口，保留模块直接输出，并追加其字符串返回值。
	 *
	 * 字符串（含 "0"）之外的返回值不作为 HTML 输出；函数本身返回 null。
	 * 直接调用不收集/管理输出缓冲，也不将父模板变量自动分配给模块创建的新 Tpl。
	 * 自动模式通过 Tpl 的专用 part 入口收集并跟踪该可信 HTML，模块仍负责内部编码。
	 * @param string $path 控制器路径；空字符串不调用入口。
	 * @return null 不返回片段内容；空路径不调用入口。
	 * @throws \Throwable 调度、控制器或模板异常原样抛出，已输出内容保留。
	 */
	public static function part($path){
		if(empty($path)) return;
		$result = \SlightPHP::run($path);
		if(is_string($result)) echo $result;
	}
	/**
	 * 用给定实例输出子模板，复用其变量表、Context 和当前渲染深度。
	 *
	 * 模板 include 标签由编译器提供当前实例；PHP 直接调用时必须显式传入。
	 * 已在渲染中的父实例会共享 Context；独立调用时 fetch 自行建立最外层渲染边界。
	 * @param string $tpl 相对于模板目录的文件名。
	 * @param \SlightPHP\Tpl $context 父模板使用的实例。
	 * @return null 子模板直接输出，不返回拼好的 HTML。
	 * @throws \Throwable 路径、编译和子模板执行异常原样传播。
	 */
	public static function include($tpl, \SlightPHP\Tpl $context){
		return $context->fetch($tpl);
	}
}
