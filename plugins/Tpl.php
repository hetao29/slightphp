<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 * @subpackage Tpl
 */
namespace SlightPHP;
/** 将模板编译为 PHP，缓存编译文件并在当前输出流中执行。 */
class Tpl{
	/** @var array 当前实例的模板变量表；多次赋值与渲染之间保留。 */
	protected $_tpl_vars = array();
	/** 编译格式标记，用于自动重建仍访问静态变量的旧缓存。 */
	private const COMPILE_HEADER = '<?php /* SlightPHP Tpl instance-vars v2 */ ?>';
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
	public function assign($tpl_var, $value = null){
		if (is_array($tpl_var)){
			foreach ($tpl_var as $key => $val) {
				if ($key != '') {
					$this->_tpl_vars[$key] = $val;
				}
			}
		} else {
			if ($tpl_var != '')
				$this->_tpl_vars[$tpl_var] = $value;
		}
	}
	/**
	 * 必要时编译模板并直接包含编译文件，内容输出到当前输出流。
	 * 源文件及编译文件必须分别位于模板目录及编译目录内。
	 * @param string $tpl 相对于模板目录的文件名。
	 * @return void
	 * @throws \RuntimeException 路径越界、模板缺失或编译目录及文件无法创建。
	 */
	public function fetch($tpl){
		$template_dir = realpath(self::$template_dir);
		$tpl_real = realpath(self::$template_dir.DIRECTORY_SEPARATOR.$tpl);
		if($template_dir === false || $tpl_real === false || !is_file($tpl_real) ||
			!str_starts_with($tpl_real, rtrim($template_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)){
			throw new \RuntimeException('Template missing or outside template directory');
		}
		if(!is_dir(self::$compile_dir) && !@mkdir(self::$compile_dir,0700,true) && !is_dir(self::$compile_dir)){
			throw new \RuntimeException('Cannot create template compile directory');
		}
		$compile_dir = realpath(self::$compile_dir);
		if($compile_dir === false) throw new \RuntimeException('Invalid template compile directory');
		$compile_prefix = rtrim($compile_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$relative = substr($tpl_real, strlen(rtrim($template_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR));
		$parts = explode(DIRECTORY_SEPARATOR, dechex(crc32($template_dir)).DIRECTORY_SEPARATOR.$relative);
		$filename = array_pop($parts);
		$compiled_file_dir = $compile_dir;
		// 逐级创建并校验，避免经过指向编译目录外的链接创建子目录。
		foreach($parts as $part){
			$dir = $compiled_file_dir.DIRECTORY_SEPARATOR.$part;
			if(!is_dir($dir) && !@mkdir($dir,0700) && !is_dir($dir)){
				throw new \RuntimeException('Cannot create compiled template subdirectory');
			}
			$compiled_file_dir = realpath($dir);
			if($compiled_file_dir === false || ($compiled_file_dir !== $compile_dir && !str_starts_with($compiled_file_dir, $compile_prefix))){
				throw new \RuntimeException('Compiled template directory outside compile directory');
			}
		}
		$compiled_file = $compiled_file_dir.DIRECTORY_SEPARATOR.$filename;
		if(file_exists($compiled_file) || is_link($compiled_file)){
			$compiled_file = realpath($compiled_file);
			if($compiled_file === false || !is_file($compiled_file) || !str_starts_with($compiled_file, $compile_prefix)){
				throw new \RuntimeException('Compiled template outside compile directory');
			}
		}
		if(self::$force_compile || !is_file($compiled_file) || filemtime($tpl_real)>filemtime($compiled_file) ||
			file_get_contents($compiled_file, false, null, 0, strlen(self::COMPILE_HEADER)) !== self::COMPILE_HEADER){
			$compiled_contents = self::COMPILE_HEADER.self::_compile(file_get_contents($tpl_real));
			$previousMask = umask(0077);
			try {
				if(file_put_contents($compiled_file,$compiled_contents,LOCK_EX) === false){
					throw new \RuntimeException('Cannot write compiled template');
				}
				if(!chmod($compiled_file,0600)){
					throw new \RuntimeException('Cannot set compiled template permissions');
				}
			} finally {
				umask($previousMask);
			}
		}
		$compiled_file = realpath($compiled_file);
		if($compiled_file === false || !is_file($compiled_file) || !str_starts_with($compiled_file, $compile_prefix)){
			throw new \RuntimeException('Compiled template outside compile directory');
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
		$patterns = array('/\$(\w+)/ms','/^(?!(if|else|for|foreach|elseif))(\$?\w+)([^\=]+)$/ms');
		$replacements=array('$this->_tpl_vars["\1"]','echo \\0');
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
			if(strtolower($function[1]) === 'include'){
				// 在变量替换之后恢复 $this，避免被当作模板变量改写。
				$key = self::$_tmpPrefix.(self::$_tmpIndex++);
				self::$_tmpData[$key] = '$this';
				$params .= ','.$key;
			}
			if(function_exists($func))return "$func($params)";
			elseif(function_exists($function[1]))return "{$function[1]}($params)";
			else return "/* $func function not exists! */";
		}else{
			return "/* $function[0] is not a Tpl function */";
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
		return null;
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
	private static $_tmpData=[];
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

	/**
	 * 设置强制编译
	 * @param bool $force_compile 是否每次重新编译。
	 * @return void
	 */
	public static function setForceCompile($force_compile=false){
		self::$force_compile=$force_compile;
	}
	/**
	 * 设置是否在编译时替换模板中显式的 PHP 标签代码。
	 * 模板表达式仍会编译为 PHP，不应将此选项视为不可信模板的沙箱。
	 * @param bool $safe_mode 是否启用安全模式。
	 * @return void
	 */
	public static function setSafeMode($safe_mode=true){
		self::$safe_mode=$safe_mode;
	}
	/**
	 * 设置模板代码的左右分割符号
	 * @param string $left 左分隔符。
	 * @param string $right 右分隔符。
	 * @return void
	 */
	public static function setDelimter($left="{", $right="}"){
		self::$left_delimiter=$left;
		self::$right_delimiter=$right;
	}
	/**
	 * 设置模板编译路径
	 * @param string $path 目录路径；空字符串使用应用目录下的 templates_c。
	 * @return void
	 */
	public static function setCompileDir($path=""){
		if($path==""){
			self::$compile_dir = \SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
		}else{
			self::$compile_dir = $path;
		}
	}
	/**
	 * 设置模板文件路径
	 * @param string $path 目录路径；空字符串使用应用目录下的 templates。
	 * @return void
	 */
	public static function setTemplateDir($path=""){
		if($path==""){
			self::$template_dir= \SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";
		}else{
			self::$template_dir= $path;
		}
	}
	/**
	 * 渲染模板并直接输出内容。
	 * @param string $tpl 模板文件名。
	 * @param array $parames 分配给模板的变量。
	 * @return null
	 */
	public function render($tpl,$parames=array()){
		if(self::$template_dir==""){
			self::setTemplateDir();
		}
		if(self::$compile_dir==""){
			self::setCompileDir();
		}
		if(self::$left_delimiter=="" || self::$right_delimiter==""){
			self::setDelimter();
		}
		$this->assign($parames);
		return $this->fetch("$tpl");
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


