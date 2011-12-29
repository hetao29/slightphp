/*{{{LICENSE
+-----------------------------------------------------------------------+
| slightphp Framework                                                   |
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
/* $ Id: $ */ 

#ifndef PHP_SLIGHTPHP_H
#define PHP_SLIGHTPHP_H

extern zend_module_entry slightphp_module_entry;
#define phpext_slightphp_ptr &slightphp_module_entry

#ifdef PHP_WIN32
#define PHP_SLIGHTPHP_API __declspec(dllexport)
#else
#define PHP_SLIGHTPHP_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINIT_FUNCTION(slightphp);
PHP_MSHUTDOWN_FUNCTION(slightphp);
PHP_RINIT_FUNCTION(slightphp);
PHP_RSHUTDOWN_FUNCTION(slightphp);
PHP_MINFO_FUNCTION(slightphp);


#define FREE_RESOURCE(resource) zend_list_delete(Z_LVAL_P(resource))

#define PROP_GET_LONG(name)    Z_LVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_LONG(name, l) zend_update_property_long(_this_ce, _this_zval, #name, strlen(#name), l TSRMLS_CC)

#define PROP_GET_DOUBLE(name)    Z_DVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_DOUBLE(name, d) zend_update_property_double(_this_ce, _this_zval, #name, strlen(#name), d TSRMLS_CC)

#define PROP_GET_STRING(name)    Z_STRVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_GET_STRLEN(name)    Z_STRLEN_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_STRING(name, s) zend_update_property_string(_this_ce, _this_zval, #name, strlen(#name), s TSRMLS_CC)
#define PROP_SET_STRINGL(name, s, l) zend_update_property_stringl(_this_ce, _this_zval, #name, strlen(#name), s, l TSRMLS_CC)


PHP_METHOD(slightphp, setPathInfo);
#if PHP_MINOR_VERSION<=2
static 
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setPathInfo_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, pathInfo)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, setAppDir);
#if PHP_MINOR_VERSION<=2
static 
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setAppDir_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, appDir)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, getAppDir);
#if PHP_MINOR_VERSION<=2
static 
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getAppDir_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, setDefaultZone);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setDefaultZone_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, defaultZone)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, getDefaultZone);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getDefaultZone_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, setDebug);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setDebug_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, _debug)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, getDebug);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getDebug_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, setDefaultPage);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setDefaultPage_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, defaultPage)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, getDefaultPage);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getDefaultPage_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()




PHP_METHOD(slightphp, setDefaultEntry);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setDefaultEntry_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, defaultEntry)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, getDefaultEntry);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getDefaultEntry_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()



PHP_METHOD(slightphp, setSplitFlag);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setSplitFlag_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, splitFlag)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, getSplitFlag);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getSplitFlag_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()


PHP_METHOD(slightphp, setZoneAlias);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__setZoneAlias_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, zone)
  ZEND_ARG_INFO(0, alias)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, getZoneAlias);
#if PHP_MINOR_VERSION<=2
static
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__getZoneAlias_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
  ZEND_ARG_INFO(0, zone)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, __construct);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp____construct_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

PHP_METHOD(slightphp, run);
#if PHP_MINOR_VERSION<=2
static  
#endif
ZEND_BEGIN_ARG_INFO_EX(slightphp__run_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()

#ifdef ZTS
#define SLIGHTPHP_G(v) TSRMG(slightphp_globals_id, zend_slightphp_globals *, v)
#else
#define SLIGHTPHP_G(v) (slightphp_globals.v)
#endif

#endif	/* PHP_SLIGHTPHP_H */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
