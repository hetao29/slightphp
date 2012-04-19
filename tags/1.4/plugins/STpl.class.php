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

if(!defined("SLIGHTPHP_PLUGINS_DIR"))define("SLIGHTPHP_PLUGINS_DIR",dirname(__FILE__));
require_once(SLIGHTPHP_PLUGINS_DIR."/tpl/Tpl.php");
/**
 * @package SlightPHP
 */
class STpl extends Tpl{
	static $engine;
	/**
	 * render a .tpl
	 */
	public function render($tpl,$parames=array()){
		parent::$compile_dir = SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
		parent::$template_dir= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";
		parent::assign($parames);
		return parent::fetch("$tpl");
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
