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
 * @subpackage SGui
 */

/**
 * Smarty {part} function part
 *
 * Type:     function<br>
 * Name:     part<br>
 * Purpose:  include slightphp part<br>
 * @author   Hetal <hetao at hetao dot name>
 * @param array
 * @param Smarty
 * @return string
 */
function smarty_function_part($params, &$smarty)
{
	return !empty($params['path'])?SlightPHP::run($params['path']):"";
}

/* vim: set expandtab: */

?>
