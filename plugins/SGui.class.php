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
class SGui{
	
	/**
	 * get smarty engine
	 */
	private function getSmartyEngine() {
		require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."smarty/Smarty.class.php");
		$smarty = new Smarty();
		array_push($smarty->plugins_dir,"plugins_slightphp");
		$smarty->compile_dir	= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
		$smarty->template_dir = SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";
		return $smarty;
	}
	/**
	 * render a .tpl
	 */
	public function render($tpl,$parames=array()){
		$smarty = $this->getSmartyEngine();
		foreach($parames as $key=>$value){
			$smarty->assign($key,$value);
		}
		return $smarty->fetch($tpl);
		
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