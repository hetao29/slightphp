/*
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.0 of the PHP license,       |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | http://www.php.net/license/3_0.txt.                                  |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Authors: Hetal <admin@slightphp.com>                                 |
   +----------------------------------------------------------------------+
*/

/* $ Id: $ */ 

#ifndef PHP_SLIGHTPHP_H
#define PHP_SLIGHTPHP_H

#ifdef  __cplusplus
extern "C" {
#endif

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>

#ifdef HAVE_SLIGHTPHP

#include <php_ini.h>
#include <SAPI.h>
#include <ext/standard/info.h>
#include <Zend/zend_extensions.h>
#ifdef  __cplusplus
} // extern "C" 
#endif
#ifdef  __cplusplus
extern "C" {
#endif

extern zend_module_entry SlightPHP_module_entry;
#define phpext_SlightPHP_ptr &SlightPHP_module_entry

#ifdef PHP_WIN32
#define PHP_SLIGHTPHP_API __declspec(dllexport)
#else
#define PHP_SLIGHTPHP_API
#endif

PHP_MINIT_FUNCTION(SlightPHP);
PHP_MSHUTDOWN_FUNCTION(SlightPHP);
PHP_RINIT_FUNCTION(SlightPHP);
PHP_RSHUTDOWN_FUNCTION(SlightPHP);
PHP_MINFO_FUNCTION(SlightPHP);

#ifdef ZTS
#include "TSRM.h"
#endif

#define FREE_RESOURCE(resource) zend_list_delete(Z_LVAL_P(resource))

#define PROP_GET_LONG(name)    Z_LVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_LONG(name, l) zend_update_property_long(_this_ce, _this_zval, #name, strlen(#name), l TSRMLS_CC)

#define PROP_GET_DOUBLE(name)    Z_DVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_DOUBLE(name, d) zend_update_property_double(_this_ce, _this_zval, #name, strlen(#name), d TSRMLS_CC)

#define PROP_GET_STRING(name)    Z_STRVAL_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_GET_STRLEN(name)    Z_STRLEN_P(zend_read_property(_this_ce, _this_zval, #name, strlen(#name), 1 TSRMLS_CC))
#define PROP_SET_STRING(name, s) zend_update_property_string(_this_ce, _this_zval, #name, strlen(#name), s TSRMLS_CC)
#define PROP_SET_STRINGL(name, s, l) zend_update_property_stringl(_this_ce, _this_zval, #name, strlen(#name), s, l TSRMLS_CC)


PHP_METHOD(SlightPHP, setAppDir);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setAppDir_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, appDir)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__setAppDir_args NULL
#endif

PHP_METHOD(SlightPHP, getAppDir);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getAppDir_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__getAppDir_args NULL
#endif

/*
PHP_METHOD(SlightPHP, setPluginsDir);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setPluginsDir_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, pluginsDir)
ZEND_END_ARG_INFO()
#else
#define SlightPHP__setPluginsDir_arg NULL
#endif


PHP_METHOD(SlightPHP, getPluginsDir);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getPluginsDir_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else
#define SlightPHP__getPluginsDir_args NULL
#endif
*/


PHP_METHOD(SlightPHP, setDefaultZone);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setDefaultZone_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, defaultZone)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__setDefaultZone_arg NULL
#endif


PHP_METHOD(SlightPHP, getDefaultZone);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getDefaultZone_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__getDefaultZone_args NULL
#endif


PHP_METHOD(SlightPHP, setDebug);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setDebug_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, _debug)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__setDebug_arg NULL
#endif


PHP_METHOD(SlightPHP, getDebug);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getDebug_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__getDebug_args NULL
#endif



PHP_METHOD(SlightPHP, setDefaultPage);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setDefaultPage_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, defaultPage)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__setDefaultPage_arg NULL
#endif


PHP_METHOD(SlightPHP, getDefaultPage);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getDefaultPage_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__getDefaultPage_args NULL
#endif




PHP_METHOD(SlightPHP, setDefaultEntry);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setDefaultEntry_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, defaultEntry)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__setDefaultEntry_arg NULL
#endif


PHP_METHOD(SlightPHP, getDefaultEntry);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getDefaultEntry_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__getDefaultEntry_args NULL
#endif



PHP_METHOD(SlightPHP, setSplitFlag);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__setSplitFlag_arg, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, splitFlag)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__setSplitFlag_arg NULL
#endif


PHP_METHOD(SlightPHP, getSplitFlag);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__getSplitFlag_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__getSplitFlag_args NULL
#endif






/*
PHP_METHOD(SlightPHP, loadFile);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__loadFile_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, filepath)
ZEND_END_ARG_INFO()
#else 
#define SlightPHP__loadFile_args NULL
#endif
*/
/*
PHP_METHOD(SlightPHP, loadPlugin);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__loadPlugin_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 1)
  ZEND_ARG_INFO(0, pluginName)
ZEND_END_ARG_INFO()
#else 
#define SlightPHP__loadPlugin_args NULL
#endif
*/

PHP_METHOD(SlightPHP, __construct);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP____construct_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP____construct_args NULL
#endif

PHP_METHOD(SlightPHP, run);
#if (PHP_MAJOR_VERSION >= 5)
ZEND_BEGIN_ARG_INFO_EX(SlightPHP__run_args, ZEND_SEND_BY_VAL, ZEND_RETURN_VALUE, 0)
ZEND_END_ARG_INFO()
#else /* PHP 4.x */
#define SlightPHP__run_args NULL
#endif

#ifdef  __cplusplus
} // extern "C" 
#endif

#endif /* PHP_HAVE_SLIGHTPHP */

#endif /* PHP_SLIGHTPHP_H */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
