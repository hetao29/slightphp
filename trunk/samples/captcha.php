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
 * 这个例子演示如何使用验证码
 * 更多文档请看docs说明
 */
require_once("global.php");

/**
 * 初始化类
 */
$cap = new SCaptcha();

/**
 * 生成图片，返回验证码
 */
$code = $cap->CreateImage();
/**
 * 检验验证码
 */
if(SCaptcha::check($code)){
	error_log(true);
}else{
	error_log(false);
}

?>
