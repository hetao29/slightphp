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


require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."sphinx/sphinxapi.php");
class SSphinx extends SphinxClient{
	private $_page=1;

	function setPage($page){
		$this->_page = $page;
		$this->SetLimits (($this->_page-1)*$this->_limit,$this->_limit);
	}
	function setLimit($limit){
		$this->_limit = $limit;
		$this->SetLimits (($this->_page-1)*$this->_limit,$this->_limit);
	}
}
?>