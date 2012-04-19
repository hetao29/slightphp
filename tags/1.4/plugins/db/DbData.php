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
 * @subpackage SDb
 */
class DbData{
	/**
	 * @var int
	 */
	var $page=1;
	/**
	 * @var int
	 */
	var $pageSize=0;
	/**
	 * @var int
	 */
	var $limit=0;
	/**
	 * @var int
	 */
	var $totalPage=0;
	/**
	 * @var int
	 */
	var $totalSize=0;
	/**
	 * @var int
	 */
	var $totalSecond=0;
	/**
	 * @var array
	 */
	var $items;//array(array(),array());

}
?>