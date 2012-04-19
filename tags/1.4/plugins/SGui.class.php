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
 * @deprecated
 */
if(version_compare(PHP_VERSION,"5.2",">=")){
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."smarty3/Smarty.class.php");
}else{
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."smarty/Smarty.class.php");
}
class SGui{
	static $engine;
	/**
	 * render a .tpl
	 */
	public function render($tpl,$parames=array()){
		if(!self::$engine){
			self::$engine = new Smarty;
			self::$engine->plugins_dir = array(SMARTY_DIR."/plugins_slightphp/",SMARTY_DIR."/plugins/");
			self::$engine->compile_dir = SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
			self::$engine->template_dir= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";
		}
		foreach($parames as $key=>$value){
			self::$engine->assign($key,$value);
		}
		return self::$engine->fetch($tpl,$parames);
		
	}
	/**
	 * 302 redirect
	 */
	public function redirect($url) {
		header('Location:'.$url);
		exit;
	}
}
?>
