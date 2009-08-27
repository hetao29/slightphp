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
#ifdef HAVE_CONFIG_H
	#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "php_main.h"
#include "php_globals.h"
#include "php_streams.h"
#include "ext/standard/info.h"

#include "standard/php_string.h"
#include "standard/basic_functions.h"


int debug(char*format,...);
int SlightPHP_load(zval*appDir,zval*zone,zval*class_name TSRMLS_DC);
int SlightPHP_loadFile(zval *file_name TSRMLS_DC);
int SlightPHP_run (zval*zone,zval*class_name,zval*method,zval**return_value ,int param_count,zval **params[] TSRMLS_DC);
int preg_quote(zval *in_str,zval*out_str);
