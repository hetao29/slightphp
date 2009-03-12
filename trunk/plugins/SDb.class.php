<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/DbData.php");
require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/DbObject.php");
class SDb{
	static $engines=array("mysql");
	/**
	 * @param string $engine enum("mysql");
	 * @return DbObject
	 */
	static function getDbEngine($engine){
		$engine = strtolower($engine);
		if(!in_array($engine,SDb::$engines)){
			return false;
		}
		if($engine=="mysql" && extension_loaded("mysql")){
			require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."db/Db_Mysql.php");
			return new Db_Mysql;
		}
	}
}
?>
