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
 * @subpackage samples
 */
/**
 * 这个例子演示如何使用json
 */
require_once("global.php");

$sm = new SJson;
$testObject = new stdclass;
$testObject->name="SlightPHP";
$testObject->value=array("min"=>1,"max"=>999);
/**
 * 编码
 */
print_r($tmp = $sm->encode($testObject));
echo "\n";
/**
 * 解码
 */
print_r($sm->decode($tmp));	
?>
