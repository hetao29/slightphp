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
//
// $Id: sphinxapi.php 1418 2008-08-28 15:30:05Z shodan $
//

//
// Copyright (c) 2001-2008, Andrew Aksyonoff. All rights reserved.
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License. You should have
// received a copy of the GPL license along with this program; if you
// did not, you can find it at http://www.gnu.org/
//

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