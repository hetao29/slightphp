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
if(version_compare(PHP_VERSION,"5.2",">=")){
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."smarty3/Smarty.class.php");
}else{
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."smarty/Smarty.class.php");
}
class SGui extends Smarty{
	var $PLUGINS_DIR_ADDED=false;
	public function __construct(){
	}
	/**
	 * render a .tpl
	 */
	public function render($tpl,$parames=array()){
		if(!$this->PLUGINS_DIR_ADDED){
			$this->plugins_dir=array();
			$plugins_dir = SMARTY_DIR."/plugins_slightphp/";
			array_push($this->plugins_dir,$plugins_dir);
			$plugins_dir = SMARTY_DIR."/plugins/";
			array_push($this->plugins_dir,$plugins_dir);
			$this->PLUGINS_DIR_ADDED=true;
		}
		$this->compile_dir	= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
		$this->template_dir 	= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";
		foreach($parames as $key=>$value){
			$this->assign($key,$value);
		}
		return $this->fetch($tpl);
		
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
