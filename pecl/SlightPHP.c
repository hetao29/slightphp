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

#include "php_SlightPHP.h"

#if HAVE_SLIGHTPHP

typedef struct _op_item{
	time_t op_mtime;
	zend_op_array op_codes;
}op_item;
#include "SlightPHP_globals.h"
#include "SlightPHP_globals.c"
/* {{{ Class definitions */

/* {{{ Class SlightPHP */

static zend_class_entry * SlightPHP_ce_ptr = NULL;

/* {{{ Methods */


/* {{{ proto void setAppDir(mixed appDir)
   */
PHP_METHOD(SlightPHP, setAppDir)
{
	zend_class_entry * _this_ce;

	zval * _this_zval = NULL;
	zval * appDir = NULL;



	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "Oz/", &_this_zval, SlightPHP_ce_ptr, &appDir) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);


	do {
		zend_update_property_string(_this_ce, _this_zval, "appDir", sizeof("appDir")-1, Z_STRVAL_P(appDir) TSRMLS_CC);
	} while (0);
}
/* }}} setAppDir */



/* {{{ proto mixed getAppDir()
   */
PHP_METHOD(SlightPHP, getAppDir)
{
	zend_class_entry * _this_ce;

	zval * _this_zval = NULL;



	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "O", &_this_zval, SlightPHP_ce_ptr) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);


	do {
		if (Z_TYPE_P(_this_zval) == IS_OBJECT) {
			zval *data = zend_read_property(_this_ce,_this_zval,"appDir",sizeof("appDir")-1,1 TSRMLS_CC);
				RETURN_ZVAL(data,1,0);
		}
		return NULL;
	} while (0);
}
/* }}} getAppDir */



/* {{{ proto int loadFile(mixed filepath)
   */
PHP_METHOD(SlightPHP, loadFile)
{
	zend_class_entry * _this_ce;

	zval * _this_zval = NULL;
	zval * filepath = NULL;



	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "Oz/", &_this_zval, SlightPHP_ce_ptr, &filepath) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);


	do {
		zval *_debug_flag = zend_read_property(_this_ce,_this_zval,"_debug",sizeof("_debug")-1,1 TSRMLS_CC);
		int ret = SlightPHP_loadFile(filepath,_debug_flag TSRMLS_CC);
		ZVAL_LONG(return_value,ret);
	} while (0);
}
/* }}} loadFile */



/* {{{ proto int loadPlugin(mixed pluginName)
   */
PHP_METHOD(SlightPHP, loadPlugin)
{
	zend_class_entry * _this_ce;

	zval * _this_zval = NULL;
	zval * pluginName = NULL;



	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "Oz/", &_this_zval, SlightPHP_ce_ptr, &pluginName) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);


	do {
		zval *pluginsDir = zend_read_property(_this_ce,_this_zval,"pluginsDir",sizeof("pluginsDir")-1,1 TSRMLS_CC);
		zval *_debug_flag = zend_read_property(_this_ce,_this_zval,"_debug",sizeof("_debug")-1,1 TSRMLS_CC);
		char*inc_filename;
		spprintf(&inc_filename,0,"%s%c%s.class.php",Z_STRVAL_P(pluginsDir),DEFAULT_SLASH,Z_STRVAL_P(pluginName));
		zval file_name;
		ZVAL_STRING(&file_name,inc_filename,1);
		if(SlightPHP_loadFile(&file_name,_debug_flag TSRMLS_CC)==SUCCESS){;
			efree(inc_filename);
			RETURN_TRUE;
		}else{
			efree(inc_filename);
			RETURN_FALSE;
		}
	} while (0);
}
/* }}} loadPlugin */



/* {{{ proto void __construct([mixed version])
   */
PHP_METHOD(SlightPHP, __construct)
{
	zend_class_entry * _this_ce;
	zval * _this_zval;

	zval * version = NULL;



	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "|z/", &version) == FAILURE) {
		return;
	}

	_this_zval = getThis();
	_this_ce = Z_OBJCE_P(_this_zval);


	do {
	} while (0);
}
/* }}} __construct */



/* {{{ proto void run()
   */
PHP_METHOD(SlightPHP, run)
{
	zend_class_entry * _this_ce;

	zval * _this_zval = NULL;



	if (zend_parse_method_parameters(ZEND_NUM_ARGS() TSRMLS_CC, getThis(), "O", &_this_zval, SlightPHP_ce_ptr) == FAILURE) {
		return;
	}

	_this_ce = Z_OBJCE_P(_this_zval);


	do {
		zval *zone=NULL;
		zval *class_name=NULL;
		zval *method=NULL;

		zval **dest_entry;
		zval **token;
		zval *path=NULL;

		//{{{
		zval *_debug_flag = zend_read_property(_this_ce,_this_zval,"_debug",sizeof("_debug")-1,1 TSRMLS_CC);
		//}}}

		zend_is_auto_global("_SERVER", sizeof("_SERVER") - 1 TSRMLS_CC);
		if (zend_hash_find(&EG(symbol_table), "_SERVER",sizeof("_SERVER"), (void **) &dest_entry) == SUCCESS &&
				Z_TYPE_PP(dest_entry) == IS_ARRAY &&
				zend_hash_find(Z_ARRVAL_PP(dest_entry), "PATH_INFO", sizeof("PATH_INFO"), (void **) &token) == SUCCESS
		){
			path = token[0];
			}

		zval *path_array;
		MAKE_STD_ZVAL(path_array);
		array_init(path_array);

		if (path){
			zval delim;
				ZVAL_STRING(&delim, "/", 0);
			//{{{
			char                *regex="/\\//";
			pcre_cache_entry    *pce;
			if ((pce = pcre_get_compiled_regex_cache(regex, strlen(regex) TSRMLS_CC)) == NULL) {
					RETURN_FALSE;
				}
			php_pcre_split_impl(pce, Z_STRVAL_P(path),Z_STRLEN_P(path),path_array, -1, 1 TSRMLS_CC);
			
			//}}}
			int n_elems = zend_hash_num_elements(Z_ARRVAL_P(path_array));
			if(zend_hash_index_exists(Z_ARRVAL_P(path_array),0)){
				if(zend_hash_index_find(Z_ARRVAL_P(path_array), 0, (void **)&dest_entry) != FAILURE) {
					if(Z_STRLEN_PP(dest_entry)>0){
						zone = dest_entry[0];
					}
				}
			}
			if(zend_hash_index_exists(Z_ARRVAL_P(path_array),1)){
				if(zend_hash_index_find(Z_ARRVAL_P(path_array), 1, (void **)&dest_entry) != FAILURE) {
					if(Z_STRLEN_PP(dest_entry)>0){
						class_name = dest_entry[0];
					}
				}
			}
			if(zend_hash_index_exists(Z_ARRVAL_P(path_array),2)){
				if(zend_hash_index_find(Z_ARRVAL_P(path_array), 2, (void **)&dest_entry) != FAILURE) {
					if(Z_STRLEN_PP(dest_entry)>0){
						method = dest_entry[0];
					}
				}
			}
				
		}
		if(!zone){
			zone = zend_read_property(_this_ce,_this_zval,"defaultZone",sizeof("defaultZone")-1,1 TSRMLS_CC);
			zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&zone,sizeof(zval*),NULL);
		}
		if(!class_name){
			class_name = zend_read_property(_this_ce,_this_zval,"defaultClass",sizeof("defaultClass")-1,1 TSRMLS_CC);
			zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&class_name,sizeof(zval*),NULL);
		}
		if(!method){
			method = zend_read_property(_this_ce,_this_zval,"defaultMethod",sizeof("defaultMethod")-1,1 TSRMLS_CC);
			zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&method,sizeof(zval*),NULL);
		}

		zval *tmp_result=NULL;

		zval *appDir = zend_read_property(_this_ce,_this_zval,"appDir",sizeof("appDir")-1,1 TSRMLS_CC);
		
		zval **params[1];
		params[0]=&path_array;


		if(SlightPHP_load(appDir,zone,class_name,_debug_flag TSRMLS_CC) == SUCCESS){
			if(SlightPHP_run(zone,class_name,method,&tmp_result,1,params,_debug_flag TSRMLS_CC)==SUCCESS){
			};
		}
		FREE_ZVAL(path_array);

		if(tmp_result){
			*return_value = *tmp_result;
			zval_copy_ctor(return_value);
			zval_ptr_dtor(&tmp_result);
		}else{
			RETURN_FALSE;
		}
	} while (0);
}
/* }}} run */


static zend_function_entry SlightPHP_methods[] = {
	PHP_ME(SlightPHP, setAppDir, SlightPHP__setAppDir_args, /**/ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getAppDir, NULL, /**/ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, loadFile, SlightPHP__loadFile_args, /**/ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, loadPlugin, SlightPHP__loadPlugin_args, /**/ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, __construct, NULL, /**/ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
	PHP_ME(SlightPHP, run, NULL, /**/ZEND_ACC_PUBLIC)
	{ NULL, NULL, NULL }
};

/* }}} Methods */

static void class_init_SlightPHP(TSRMLS_D)
{
	zend_class_entry ce;

	INIT_CLASS_ENTRY(ce, "SlightPHP", SlightPHP_methods);
	SlightPHP_ce_ptr = zend_register_internal_class(&ce TSRMLS_CC);

	/* {{{ Property registration */

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"appDir", 6, ".", 
		ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"pluginsDir", 10, "plugins", 
		ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultZone", 11, "index", 
		ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultClass", 12, "default", 
		ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultMethod", 13, "entry", 
		ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_long(SlightPHP_ce_ptr, 
		"_debug", 6, 0, 
		ZEND_ACC_PUBLIC TSRMLS_CC);

	/* }}} Property registration */

}

/* }}} Class SlightPHP */

/* }}} Class definitions*/

/* {{{ SlightPHP_functions[] */
function_entry SlightPHP_functions[] = {
	{ NULL, NULL, NULL }
};
/* }}} */

/* {{{ cross-extension dependencies */

#if ZEND_EXTENSION_API_NO >= 220050617
static zend_module_dep SlightPHP_deps[] = {
	ZEND_MOD_OPTIONAL("apc")
	{NULL, NULL, NULL, 0}
};
#endif
/* }}} */

/* {{{ SlightPHP_module_entry
 */
zend_module_entry SlightPHP_module_entry = {
#if ZEND_EXTENSION_API_NO >= 220050617
		STANDARD_MODULE_HEADER_EX, NULL,
		SlightPHP_deps,
#else
		STANDARD_MODULE_HEADER,
#endif

	"SlightPHP",
	SlightPHP_functions,
	PHP_MINIT(SlightPHP),     /* Replace with NULL if there is nothing to do at php startup   */ 
	PHP_MSHUTDOWN(SlightPHP), /* Replace with NULL if there is nothing to do at php shutdown  */
	PHP_RINIT(SlightPHP),     /* Replace with NULL if there is nothing to do at request start */
	PHP_RSHUTDOWN(SlightPHP), /* Replace with NULL if there is nothing to do at request end   */
	PHP_MINFO(SlightPHP),
	"0.1", 
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_SLIGHTPHP
ZEND_GET_MODULE(SlightPHP)
#endif


/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(SlightPHP)
{
	REGISTER_STRINGL_CONSTANT("SlightPHP_VERSION", "0.1", 3, CONST_PERSISTENT | CONST_CS);
	class_init_SlightPHP(TSRMLS_C);
	do {
	} while (0);

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION(SlightPHP)
{
	do {
	} while (0);

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION(SlightPHP)
{

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION(SlightPHP)
{

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(SlightPHP)
{
	php_info_print_box_start(0);
	php_printf("<p>SlightPHP Framework</p>\n");
	php_printf("<p>Version 0.1stable (2009-02-24)</p>\n");
	php_printf("<p><b>Authors:</b></p>\n");
	php_printf("<p>Hetal &lt;admin@slightphp.com&gt; (lead)</p>\n");
	php_info_print_box_end();
	/* add your stuff here */

}
/* }}} */

#endif /* HAVE_SLIGHTPHP */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
