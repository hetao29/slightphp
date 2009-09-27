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

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."smarty/Smarty.class.php");
/**
 * @package SlightPHP
 */
class SGui extends Smarty{
	public function SGui(){
		parent::__construct();

		array_push($this->plugins_dir,"plugins_slightphp");
		$this->compile_dir	= SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates_c";
		$this->template_dir = SlightPHP::$appDir.DIRECTORY_SEPARATOR."templates";

		//$this->left_delimiter = "{{{";
		//$this->right_delimiter = "}}}";

	}
	public function render($tpl,$parames=array()){
		foreach($parames as $key=>$value){
			$this->assign($key,$value);
		}
		return $this->fetch($tpl);
		
	}
}
?>