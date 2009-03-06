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
#ifdef HAVE_CONFIG_H
	#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "php_main.h"
#include "php_globals.h"
#include "php_streams.h"
#include "ext/standard/info.h"
#include "ext/pcre/php_pcre.h"

#include "standard/php_string.h"
#include "standard/basic_functions.h"


int debug(zval*_debug_flag,char*format,...);
int SlightPHP_load(zval*appDir,zval*zone,zval*class_name,zval*_debug_flag TSRMLS_DC);
int SlightPHP_loadFile(zval *file_name ,zval*_debug_flag TSRMLS_DC);
int SlightPHP_run (zval*zone,zval*class_name,zval*method,zval**return_value ,int param_count,zval **params[],zval *_debug_flag TSRMLS_DC);
int preg_quote(zval *in_str,zval*out_str,zval * _debug_flag);
