<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP \STpl
 */
namespace SlightPHP;
require_once(dirname(__FILE__)."/Tpl.modifier.php");
require_once(dirname(__FILE__)."/Tpl.function.php");
/** 将模板编译为 PHP，缓存编译文件并在当前输出流中执行。 */
class Tpl{
	/** @var array 模板变量表。 */
	static $_tpl_vars             = array();
	/** @var string 模板标签左分隔符。 */
	static $left_delimiter  =  '';//{
	/** @var string 模板标签右分隔符。 */
	static $right_delimiter =  '';//}
	/** @var string 模板源文件目录。 */
	static $template_dir    =  '';//templates
	/** @var string 编译文件目录。 */
	static $compile_dir     =  '';//templates_c
	/** @var bool 是否每次都重新编译。 */
	static $force_compile   =  false;
	/** @var bool 是否在编译时替换显式的 PHP 标签代码；不限制模板表达式。 */
	static $safe_mode = true;
	/**
	 * 分配一个变量或一组变量；空名称会被忽略。
	 * @param string|array $tpl_var 变量名或名称到值的映射。
	 * @param mixed $value 单个变量的值。
	 * @return void
	 */
	static function assign($tpl_var, $value = null){
		if (is_array($tpl_var)){
			foreach ($tpl_var as $key => $val) {
				if ($key != '') {
					self::$_tpl_vars[$key] = $val;
				}
			}
		} else {
			if ($tpl_var != '')
				self::$_tpl_vars[$tpl_var] = $value;
		}
	}
	/**
	 * 必要时编译模板并直接包含编译文件，内容输出到当前输出流。
	 * @param string $tpl 相对于模板目录的文件名。
	 * @return void
	 */
	static function fetch($tpl){
		$tpl_real  = self::$template_dir.DIRECTORY_SEPARATOR.$tpl;
		$compiled_file = self::$compile_dir.DIRECTORY_SEPARATOR.dechex(crc32(realpath(self::$template_dir))).DIRECTORY_SEPARATOR.$tpl;
		$compiled_file_dir = dirname($compiled_file);
		if(!is_dir($compiled_file_dir)){
			mkdir($compiled_file_dir,0777,true);
		}
		if(self::$force_compile || !is_file($compiled_file) || filemtime($tpl_real)>filemtime($compiled_file)){
			$compiled_contents = self::_compile(file_get_contents($tpl_real));
			file_put_contents($compiled_file,$compiled_contents,LOCK_EX);
		}
		include($compiled_file);
	}
	/**
	 * 编译一个模板标签。
	 * @param array $matches 正则匹配结果。
	 * @return string 生成的 PHP 代码。
	 */
	private static function _match(array $matches): string{
		$content = $matches[1];
		//替换特殊字符
		self::$_tmpData=array();
		self::$_tmpIndex=0;
		$content = preg_replace_callback("/([\'\"])(.+?)\\1/m",array("SlightPHP\Tpl","_tmpData"),$content);
		//{{{if,elseif,/if; foreach,/foreach; for,/for
		$pattern = "/^(if|foreach|for)(([\s|\(]+)(.+))/msi";
		$content = preg_replace_callback($pattern,function($m){
			$t = trim($m[3]);
			$v = trim($m[2]);
			if(empty($t)){
				return "{$m[1]}($v){";
			}else{
				return "{$m[1]}$v{";
			}
		},$content);
		$patterns = array("/^(elseif)([\s*|\\(].*)/msi","/^(else)/msUi","/^\/(if|foreach|for)/msi");
		$replacements=array('}\\1(\\2){','}\\1{','}');
		$content = preg_replace($patterns,$replacements,$content);
		//}}}
		//function
		//{fun $param }
		//{fun $param1 $param2 "param3" }
		//{fun($param1,$param2,"param3")}
		//{fun ($param1,$param2,"param3")}
		$pattern = "/^(\w+)\\s+(.*)/ms";
		do{
			$content = preg_replace_callback($pattern,array("SlightPHP\Tpl","_matchfunction"),$content,-1,$ct);
		}while(0);
		//modifier，支持多种格式，与多极modifier
		//{$v|modifer}
		//{$v|modifer:1:2}
		//{'v'|modifer:1:2|modifer:3}
		$pattern = "/(.+?)\\|([^\\|]+)/ms";
		do{
			$content = preg_replace_callback($pattern,array("SlightPHP\Tpl","_matchmodifier"),$content,-1,$ct);
		}while($ct);
		//{{{替换变量,加ECHO
		$patterns = array('/\$(\w+)/ms','/^(?!(if|else|for|foreach|elseif))(\w+)([^\=]+)$/ms');
		$replacements=array('SlightPHP\Tpl::$_tpl_vars["\1"]','echo \\0');
		$content = preg_replace($patterns,$replacements,$content);
		//还原特殊字符
		$content = str_replace(array_keys(self::$_tmpData),self::$_tmpData,$content);
		////}}}
		$content="<?php $content; ?>";
		return $content;
	}
	/**
	 * 编译模板函数调用。
	 * @param array $function 正则匹配结果。
	 * @return string PHP 调用或说明函数不存在的注释。
	 */
	private static function _matchfunction(array $function): string{
		$r=preg_match_all("/(\\$?\S+|\".+?\"|\'.+?\')/",$function[2],$tmp);
		if($r){
			$func="tpl_function_".$function[1];
			$params=implode(",",$tmp[0]);
			if(function_exists($func))return "$func($params)";
			elseif(function_exists($function[1]))return "{$function[1]}($params)";
			else return "/* $func function not exists! */";
		}else{
			return "/* $function[0] is not a STpl function */";
		}
	}
	/**
	 * 编译模板修饰器调用。
	 * @param array $modifier 正则匹配结果。
	 * @return string|null PHP 调用、错误注释或没有匹配时的 null。
	 */
	private static function _matchmodifier(array $modifier): ?string{
		$r=preg_match_all("/(\\$?\w+)/",$modifier[2],$keys);
		if($r){
			$func = array_shift($keys[0]);
			$func_m = "tpl_modifier_".$func;
			array_unshift($keys[0],$modifier[1]);
			$params = implode(",",$keys[0]);
			if(function_exists($func_m)){
				$str = "$func_m($params)";
			}elseif(function_exists($func)){
				$str = "$func($params)";
			}else{
				$str = "/* $func_m function not exists! */";
			}
			return $str;
		}
	}
	/**
	 * 编译模板文本，安全模式下先替换其中的 PHP 代码。
	 * @param string $content 模板源文本。
	 * @return string|null 编译后的 PHP 文本，正则失败时为 null。
	 */
	private static function _compile($content){
		$left_delimiter= self::$left_delimiter;
		$right_delimiter= self::$right_delimiter;
		$left_delimiter_quote = preg_quote($left_delimiter);
		$right_delimiter_quota= preg_quote($right_delimiter);
		$php_left = preg_quote("<?php ");
		$php_right= preg_quote(" ?>");
		//安全模式，替换php可执行代码
		if(self::$safe_mode){
			$pattern="/\\<\\?.*\\?>/msUi";
			$content = preg_replace(
				$pattern,
				'<!-- PHP CODE REPLACED ON SAFE MODE -->',
				$content
			);
		}
		//替换注释
		$pattern="/{$left_delimiter_quote}\*(.*)\*{$right_delimiter_quota}/msU";
		$content = preg_replace($pattern,"<?php /*\\1*/?>",$content);
		$pattern="/{$left_delimiter_quote}([\S].*){$right_delimiter_quota}/msU";
		return preg_replace_callback($pattern,array("SlightPHP\Tpl",'_match'),$content);
	}
	private static $_tmpData;
	private static $_tmpIndex=0;
	private static $_tmpPrefix="TPL_TMP_PREFIX_";
	/**
	 * 暂存标签中的引号字符串。
	 * @param array $matches 正则匹配结果。
	 * @return string 占位符。
	 */
	private static function _tmpData(array $matches): string{
		$key = self::$_tmpPrefix.(self::$_tmpIndex++);
		self::$_tmpData[$key]=$matches[1].$matches[2].$matches[1];
		return $key;
	}
}
