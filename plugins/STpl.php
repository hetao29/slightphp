<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
require_once(SLIGHTPHP_PLUGINS_DIR."/tpl/Tpl.php");
/**
 * 模板引擎的配置及渲染入口。
 * @package SlightPHP
 */
class STpl extends SlightPHP\Tpl{
	/**
	 * 设置强制编译
	 * @param bool $force_compile 是否每次重新编译。
	 * @return void
	 */
	public static function setForceCompile($force_compile=false){
		parent::$force_compile=$force_compile;
	}
	/**
	 * 设置是否在编译时替换模板中显式的 PHP 标签代码。
	 * 模板表达式仍会编译为 PHP，不应将此选项视为不可信模板的沙箱。
	 * @param bool $safe_mode 是否启用安全模式。
	 * @return void
	 */
	public static function setSafeMode($safe_mode=true){
		parent::$safe_mode=$safe_mode;
	}
	/**
	 * 设置模板代码的左右分割符号
	 * @param string $left 左分隔符。
	 * @param string $right 右分隔符。
	 * @return void
	 */
	public static function setDelimter($left="{", $right="}"){
		parent::$left_delimiter=$left;
		parent::$right_delimiter=$right;
	}
	/**
	 * 设置模板编译路径
	 * @param string $path 目录路径；空字符串使用应用目录下的 templates_c。
	 * @return void
	 */
	public static function setCompileDir($path=""){
		if($path==""){
			parent::$compile_dir = SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
		}else{
			parent::$compile_dir = $path;
		}
	}
	/**
	 * 设置模板文件路径
	 * @param string $path 目录路径；空字符串使用应用目录下的 templates。
	 * @return void
	 */
	public static function setTemplateDir($path=""){
		if($path==""){
			parent::$template_dir= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";
		}else{
			parent::$template_dir= $path;
		}
	}
	/**
	 * 渲染模板并直接输出内容。
	 * @param string $tpl 模板文件名。
	 * @param array $parames 分配给模板的变量。
	 * @return null
	 */
	public function render($tpl,$parames=array()){
		if(parent::$template_dir==""){
			self::setTemplateDir();
		}
		if(parent::$compile_dir==""){
			self::setCompileDir();
		}
		if(parent::$left_delimiter=="" || parent::$right_delimiter==""){
			self::setDelimter();
		}
		parent::assign($parames);
		return parent::fetch("$tpl");
	}

	/**
	 * 别名 render
	 * @param string $tpl 模板文件名。
	 * @param array $parames 分配给模板的变量。
	 * @return null
	 */
	public function display($tpl,$parames=array()){
		return $this->render($tpl, $parames);
	}
	/**
	 * 302 redirect
	 * @param string $url 跳转地址。
	 * @return never
	 */
	public function redirect($url) {
		header('Location:'.$url);
		exit;
	}
}
?>
