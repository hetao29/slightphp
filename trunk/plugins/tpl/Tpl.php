<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/

/**
 * @package SlightPHP
 */
require_once(dirname(__FILE__)."/Tpl.modifier.php");
require_once(dirname(__FILE__)."/Tpl.function.php");
class Tpl{
	static $_tpl_vars             = array();
	static $left_delimiter  =  '{';
	static $right_delimiter =  '}';
	static $template_dir    =  'templates';
	static $compile_dir     =  'templates_c';
	static $force_compile   =  false;
	static $safe_mode = true;
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
	static function fetch($tpl){
		$tpl_real  = self::$template_dir."/".($tpl);
		if(!is_dir(self::$compile_dir)){
			mkdir(self::$compile_dir,0777,true);
		}
		$compiled_file = self::$compile_dir."/".base64_encode($tpl).".%%.tpl";
		if(self::$force_compile || !is_file($compiled_file) || filemtime($tpl_real)>filemtime($compiled_file)){
			$compiled_contents = self::_compile(file_get_contents($tpl_real));
			file_put_contents($compiled_file,$compiled_contents,LOCK_EX);
		}
		include($compiled_file);
	}
	private static function _match($matches){
		$content = $matches[1];
		//替换特殊字符
		self::$_tmpData=array();
		self::$_tmpIndex=0;
		$content = preg_replace_callback("/([\'\"])(.+?)\\1/m",array("Tpl","_tmpData"),$content);
		//{{{if,elseif,/if; foreach,/foreach; for,/for
		$pattern = "/^(if|foreach|for)(([\s|\(]+)(.+))/msi";
		$content = preg_replace_callback($pattern,create_function('$m','$t = trim($m[3]);$v = trim($m[2]);if(empty($t)){return "{$m[1]}($v){";}else{return "{$m[1]}$v{";}'),$content);
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
			$content = preg_replace_callback($pattern,array("Tpl","_matchfunction"),$content,-1,$ct);
		}while(0);
		//modifier，支持多种格式，与多极modifier
		//{$v|modifer}
		//{$v|modifer:1:2}
		//{'v'|modifer:1:2|modifer:3}
		$pattern = "/(.+?)\\|([^\\|]+)/ms";
		do{
			$content = preg_replace_callback($pattern,array("Tpl","_matchmodifier"),$content,-1,$ct);
		}while($ct);
		//{{{替换变量,加ECHO
		$patterns = array('/\$(\w+)/ms','/^(?!(if|else|for|foreach|elseif))(\w+)([^\=]+)$/ms');
		$replacements=array('Tpl::$_tpl_vars["\1"]','echo \\0');
		$content = preg_replace($patterns,$replacements,$content);
		//还原特殊字符
		$content = str_replace(array_keys(self::$_tmpData),self::$_tmpData,$content);
		////}}}
		$content="<?php $content; ?>";
		return $content;
	}
	private static function _matchfunction($function){
		$r=preg_match_all("/(\\$?\w+|\".+?\"|\'.+?\')/",$function[2],$tmp);
		if($r>=1){
			$func="tpl_function_".$function[1];
			$params=implode(",",$tmp[0]);
			if(function_exists($func))return "$func($params)";
			elseif(function_exists($function[1]))return "{$function[1]}($params)";
			else return "/* $func function not exists! */";
		}else{
			return "/* $function[0] is not a STpl function */";
		}
	}
	private static $_tmpData;
	private static $_tmpIndex=0;
	private static $_tmpPrefix="TPL_TMP_PREFIX_";
	private static function _tmpData($matches){
		$key = self::$_tmpPrefix.(self::$_tmpIndex++);
		self::$_tmpData[$key]=$matches[1].$matches[2].$matches[1];
		return $key;
	}
	private static function _matchmodifier($modifier){
		$r=preg_match_all("/(\\$?\w+)/",$modifier[2],$keys);
		if($r>0){
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
		}
		return $str;
	}
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
		return preg_replace_callback($pattern,array("Tpl",'_match'),$content);
	}
}
