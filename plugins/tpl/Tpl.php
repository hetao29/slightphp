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
	static function _match($matches){
		$content = $matches[1];
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
		$pattern = "/^(\w+)\\s?(.*)/ms";
		$content = preg_replace_callback($pattern,create_function('$m','$r=preg_match_all("/(\\\\$?\w+|\".+?\"|\'.+?\')/",$m[2],$tmp);$func="tpl_function_".$m[1];if($r>=1){$params=implode(",",$tmp[0]);if(function_exists($func))return "echo $func($params)";elseif(function_exists($m[1]))return "echo {$m[1]}($params)";else return "/* $func function not exists! */";}else{return "/* $m[0] is not a STpl function */";}'),$content);
		//modifier，支持多种格式
		//{$v|modifer}
		//{$v|modifer:1:2}
		//{'v'|modifer:1:2}
		$pattern = "/^(.+)\\|(.+)/ms";
		$content = preg_replace_callback($pattern,create_function('$m','$r=preg_match_all("/(\\\\$?\w+|\".+?\"|\'.+?\')/",$m[2],$tmp); if($r>=0){$eg=$tmp[0];$func="tpl_modifier_".$eg[0]; $eg[0]=$m[1];$params=implode(",",$eg);if(function_exists($func))return "echo $func($params)";else return "/* $func function not exists! */";}else{return "/* {$m[0]} is not a STpl modifier */";}'),$content);
		//{{{替换变量,加ECHO
		$patterns = array("/\\$(\w+)/ms","/^(Tpl::\\$\S+)$/ms");
		$replacements=array('Tpl::$_tpl_vars["\1"]','echo \\1');
		$content = preg_replace($patterns,$replacements,$content);
		////}}}
		$content="<?php $content; ?>";
		return $content;
	}
	function _compile($content){

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
