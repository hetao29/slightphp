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
	var $left_delimiter  =  '{';
	var $right_delimiter =  '}';
	var $template_dir    =  'templates';
	var $compile_dir     =  'templates_c';
	var $force_compile   =  true;
	var $safe_mode = true;
	function assign($tpl_var, $value = null){
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
	function fetch($tpl){
		$tpl_real  = $this->template_dir."/".($tpl);
		if(!is_dir($this->compile_dir)){
			mkdir($this->compile_dir,0777,true);
		}
		$compiled_file = $this->compile_dir."/".base64_encode($tpl).".%%.tpl";
		if($this->force_compile || !file_exists($compiled_file) || filemtime($tpl_real)>filemtime($compiled_file)){
			$compiled_contents = $this->_compile(file_get_contents($tpl_real));
			file_put_contents($compiled_file,$compiled_contents);
		}
		include($compiled_file);
	}
	function _match($matches){
		$content = $matches[1];
		//{{{if,elseif,/if; foreach,/foreach; for,/for*/
		$pattern="/^(if|foreach|for)(.*)/msi";
		//$content = preg_replace_callback($pattern,create_function('$m','print_r($m);'),$content);
		$content = preg_replace(
			$pattern,
			'\\1(\\2){',
			$content
		);
		$pattern="/^(elseif)([\s*|\\(].*)/msi";
		$content = preg_replace(
			$pattern,
			'}\\1(\\2){',
			$content
		);
		$pattern="/^(else)/msUi";
		$content = preg_replace(
			$pattern,
			'}\\1{',
			$content
		);
		$pattern="/^\/(if|foreach|for)/msi";
		$content = preg_replace(
			$pattern,
			'}',
			$content
		);
		//}}}
		//function
		//{fun $param }
		//{fun $param1 $param2 "param3" }
		$pattern="/^(\w+)\\s+(.*)/ms";
		$content = preg_replace_callback($pattern,create_function('$m','$tmp=explode(" ",trim($m[2]));$func="tpl_function_".$m[1]; $params=implode(",",$tmp);if(function_exists($func))return "echo $func($params)";else return "$func function not exists!";'),$content);
		//modifier，支持多种格式
		//{$v|modifer}
		//{$v|modifer:1:2}
		//{'v'|modifer:1:2}
		$pattern="/^(\S+)\\|(\S+)/ms";
		$content = preg_replace_callback($pattern,create_function('$m','$tmp=explode(":",trim($m[2]));$func="tpl_modifier_".$tmp[0]; $tmp[0] = $m[1]; $params=implode(",",$tmp);if(function_exists($func))return "echo $func($params)";else return "$func function not exists!";'),$content);
		//{{{加ECHO
		$pattern="/^(\\$\w+)$/ms";
		$content = preg_replace(
			$pattern,
			'echo \\1',
			$content
		);
		//}}}
		//{{{替换变量
		$pattern="/\\$(\w+)/ms";
		$content = preg_replace(
			$pattern,
			'Tpl::$_tpl_vars["\1"]',
			$content
		);
		$content="<?php $content; ?>";
		//}}}
		return $content;
	}
	function _compile($content){

		$left_delimiter= $this->left_delimiter;
		$right_delimiter= $this->right_delimiter;
		$left_delimiter_quote = preg_quote($left_delimiter);
		$right_delimiter_quota= preg_quote($right_delimiter);
		$php_left = preg_quote("<?php ");
		$php_right= preg_quote(" ?>");
		//安全模式，替换php可执行代码
		if($this->safe_mode){
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
		return preg_replace_callback($pattern,'Tpl::_match',$content);
	}
}
