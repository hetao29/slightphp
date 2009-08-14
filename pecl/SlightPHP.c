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
/* {{{ Class definitions */

/* {{{ Class SlightPHP */

static zend_class_entry * SlightPHP_ce_ptr = NULL;
#include "SlightPHP_globals.h"
#include "SlightPHP_globals.c"

/* {{{ Methods */


/* {{{ proto void setAppDir(mixed appDir)
   */
PHP_METHOD(SlightPHP, setAppDir)
{
	char* appDir;
	int appDir_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &appDir,&appDir_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "appDir", sizeof("appDir")-1, appDir TSRMLS_CC);
	RETURN_TRUE;
}
/* }}} setAppDir */



/* {{{ proto mixed getAppDir()
   */
PHP_METHOD(SlightPHP, getAppDir)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"appDir",sizeof("appDir")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}
/* }}} getAppDir */

PHP_METHOD(SlightPHP, setPluginsDir)
{
	char * pluginsDir = NULL;
	int pluginsDir_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &pluginsDir,&pluginsDir_len) == FAILURE) {
			RETURN_FALSE;
	}
	//if (Z_TYPE_P(pluginsDir)!= IS_STRING){ RETURN_FALSE; }

	zend_update_static_property_string(SlightPHP_ce_ptr, "pluginsDir", sizeof("pluginsDir")-1, pluginsDir TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getPluginsDir)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"pluginsDir",sizeof("pluginsDir")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}


PHP_METHOD(SlightPHP, setDefaultZone)
{
	char* defaultZone= NULL;
	int defaultZone_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &defaultZone,&defaultZone_len) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "defaultZone", sizeof("defaultZone")-1, defaultZone TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getDefaultZone)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"defaultZone",sizeof("defaultZone")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}




PHP_METHOD(SlightPHP, setDefaultClass)
{
	char * defaultClass= NULL;
	int defaultClass_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &defaultClass,&defaultClass_len) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "defaultClass", sizeof("defaultClass")-1, defaultClass TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getDefaultClass)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"defaultClass",sizeof("defaultClass")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}




PHP_METHOD(SlightPHP, setDefaultMethod)
{
	char * defaultMethod= NULL;
	int defaultMethod_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &defaultMethod,&defaultMethod_len) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "defaultMethod", sizeof("defaultMethod")-1, defaultMethod TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getDefaultMethod)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"defaultMethod",sizeof("defaultMethod")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}



PHP_METHOD(SlightPHP, setSplitFlag)
{
	char * splitFlag = NULL;
	int splitFlag_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &splitFlag, &splitFlag_len) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "splitFlag", sizeof("splitFlag")-1, splitFlag TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getSplitFlag)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"splitFlag",sizeof("splitFlag")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}



PHP_METHOD(SlightPHP, setDebug)
{
	int _debug;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &_debug) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_long(SlightPHP_ce_ptr, "_debug", sizeof("_debug")-1, _debug TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getDebug)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"_debug",sizeof("_debug")-1,1 TSRMLS_CC);
	convert_to_long(data);
	RETURN_BOOL(Z_LVAL_P(data));
}


/* {{{ proto int loadFile(mixed filepath)
   */
PHP_METHOD(SlightPHP, loadFile)
{
	zval * filepath = NULL;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z/", &filepath) == FAILURE) {
			RETURN_FALSE;
	}
	if (Z_TYPE_P(filepath)!= IS_STRING){
			RETURN_FALSE;
	}

	if(SlightPHP_loadFile(filepath TSRMLS_CC)==SUCCESS){
		RETURN_TRUE;
	}else{
		RETURN_FALSE;
	}
}
/* }}} loadFile */



/* {{{ proto int loadPlugin(mixed pluginName)
   */
PHP_METHOD(SlightPHP, loadPlugin)
{
	//zval * pluginName = NULL;
	char * pluginName = NULL;
	int pluginName_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &pluginName,&pluginName_len) == FAILURE) {
			RETURN_FALSE;
	}	

	zval *pluginsDir = zend_read_static_property(SlightPHP_ce_ptr,"pluginsDir",sizeof("pluginsDir")-1,1 TSRMLS_CC);
	char*inc_filename;
	spprintf(&inc_filename,0,"%s%c%s.class.php",Z_STRVAL_P(pluginsDir),DEFAULT_SLASH,pluginName);
	zval file_name;
	ZVAL_STRING(&file_name,inc_filename,1);
	if(SlightPHP_loadFile(&file_name TSRMLS_CC)==SUCCESS){;
		efree(inc_filename);
		RETURN_TRUE;
	}else{
		efree(inc_filename);
		RETURN_FALSE;
	}
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
}
/* }}} __construct */



/* {{{ proto void run()
   */
PHP_METHOD(SlightPHP, run)
{
	zval *zone=NULL;
	zval *class_name=NULL;
	zval *method=NULL;

	zval **token;
	zval *script_name=NULL;
	zval *request_uri=NULL;
	zval *server=NULL;
	zval *path_array;

	//{{{
	//}}}

	zend_is_auto_global("_SERVER", sizeof("_SERVER") - 1 TSRMLS_CC);
	server = PG(http_globals)[TRACK_VARS_SERVER];
	if(!server){
		RETURN_FALSE;
	}
	if(zend_hash_find(HASH_OF(server), "SCRIPT_NAME", sizeof("SCRIPT_NAME"), (void **) &token) == SUCCESS
	){
		script_name = *token;
	}
	if(zend_hash_find(HASH_OF(server), "REQUEST_URI", sizeof("REQUEST_URI"), (void **) &token) == SUCCESS
	){
		request_uri = *token;
	}
	if(script_name && request_uri && Z_STRLEN_P(script_name)==Z_STRLEN_P(script_name)){
		zval replace;
		INIT_ZVAL(replace);
		ZVAL_STRING(&replace, "", 0);
		php_str_replace_in_subject(script_name,&replace,request_uri,1,NULL)
	}

	MAKE_STD_ZVAL(path_array);
	array_init(path_array);

	if (request_uri){
		//{{{
		zval quotedFlag;
		regex_t re;
		char	*regex;
		regmatch_t subs[1];
		int err,size;
		char *strp = Z_STRVAL_P(request_uri);
		char *endp = strp + Z_STRLEN_P(request_uri);
		zval *splitFlag = zend_read_static_property(SlightPHP_ce_ptr,"splitFlag",sizeof("splitFlag")-1,1 TSRMLS_CC);

		if(preg_quote(splitFlag,&quotedFlag)>0){
			spprintf(&regex,0,"[%s\\/]",Z_STRVAL(quotedFlag));
		}else{
			spprintf(&regex,0,"[\\/]");
		}
		err = regcomp(&re, regex, REG_ICASE);
		if (err) {
		}else{
			while (!(err = regexec(&re, strp, 1, subs, 0))) {
				if (subs[0].rm_so == 0 && subs[0].rm_eo) {
					//ignore empty string 
					strp += subs[0].rm_eo;
				}else if (subs[0].rm_so == 0 && subs[0].rm_eo == 0) {
				}else{
						size = subs[0].rm_so;
			            add_next_index_stringl(path_array, strp, size, 1);
			            strp += size;
			
				}
			}
			if (!err || err == REG_NOMATCH) {
				size = endp - strp;
				if(size>0) add_next_index_stringl(path_array, strp, size, 1);
			}
			regfree(&re);
		}
		efree(regex);
		//}}}
		/*
		zval quotedFlag;
		char	*regex;
		pcre_cache_entry    *pce;
		zval *splitFlag = zend_read_static_property(SlightPHP_ce_ptr,"splitFlag",sizeof("splitFlag")-1,1 TSRMLS_CC);
		if(preg_quote(splitFlag,&quotedFlag)>0){
			spprintf(&regex,0,"/[%s\\/]/",Z_STRVAL(quotedFlag));
		}else{
			spprintf(&regex,0,"/[\\/]/");
		}
		//{{{
		if ((pce = pcre_get_compiled_regex_cache(regex, strlen(regex) TSRMLS_CC)) == NULL) {
			efree(regex);
				RETURN_FALSE;
			}
		efree(regex);
		php_pcre_split_impl(pce, Z_STRVAL_P(path),Z_STRLEN_P(path),path_array, -1, 1 TSRMLS_CC);
		
		//}}}
		*/
		int n_elems = zend_hash_num_elements(Z_ARRVAL_P(path_array));
		if(zend_hash_index_find(Z_ARRVAL_P(path_array), 0, (void **)&token) != FAILURE) {
			zone = *token;
		}
		if(zend_hash_index_find(Z_ARRVAL_P(path_array), 1, (void **)&token) != FAILURE) {
			class_name = *token;
		}
		if(zend_hash_index_find(Z_ARRVAL_P(path_array), 2, (void **)&token) != FAILURE) {
			method = *token;
		}
			
	}
	if(!zone){
		zone = zend_read_static_property(SlightPHP_ce_ptr,"defaultZone",sizeof("defaultZone")-1,1 TSRMLS_CC);
		zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&zone,sizeof(zval*),NULL);
	}
	if(!class_name){
		class_name = zend_read_static_property(SlightPHP_ce_ptr,"defaultClass",sizeof("defaultClass")-1,1 TSRMLS_CC);
		zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&class_name,sizeof(zval*),NULL);
	}
	if(!method){
		method = zend_read_static_property(SlightPHP_ce_ptr,"defaultMethod",sizeof("defaultMethod")-1,1 TSRMLS_CC);
		zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&method,sizeof(zval*),NULL);
	}

	zval *tmp_result=NULL;

	zval *appDir = zend_read_static_property(SlightPHP_ce_ptr,"appDir",sizeof("appDir")-1,1 TSRMLS_CC);
	
	zval **params[1];
	params[0]=&path_array;


	if(SlightPHP_load(appDir,zone,class_name TSRMLS_CC) == SUCCESS){
		if(SlightPHP_run(zone,class_name,method,&tmp_result,1,params TSRMLS_CC)==SUCCESS){
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
}
/* }}} run */


static zend_function_entry SlightPHP_methods[] = {

	PHP_ME(SlightPHP, setAppDir, SlightPHP__setAppDir_args, /**/ZEND_ACC_STATIC|ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getAppDir, NULL, /**/ZEND_ACC_STATIC|ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setPluginsDir, SlightPHP__setPluginsDir_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getPluginsDir, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDefaultZone , SlightPHP__setDefaultZone_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDefaultZone, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDefaultClass , SlightPHP__setDefaultClass_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDefaultClass, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDefaultMethod, SlightPHP__setDefaultMethod_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDefaultMethod, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDebug, SlightPHP__setDebug_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDebug, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setSplitFlag, SlightPHP__setSplitFlag_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getSplitFlag, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, loadFile, SlightPHP__loadFile_args, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, loadPlugin, SlightPHP__loadPlugin_args, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, __construct, NULL, /**/ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
	PHP_ME(SlightPHP, run, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	{ NULL, NULL, NULL }
};

/* }}} Methods */

static void class_init_SlightPHP(TSRMLS_D)
{
	zend_class_entry ce;

	INIT_CLASS_ENTRY(ce, "SlightPHP", SlightPHP_methods);
	SlightPHP_ce_ptr = zend_register_internal_class(&ce TSRMLS_CC);
	SlightPHP_ce_ptr->ce_flags |= ZEND_ACC_FINAL_CLASS;

	/* {{{ Property registration */

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"appDir", 6, ".", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"pluginsDir", 10, "plugins", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultZone", 11, "index", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultClass", 12, "default", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultMethod", 13, "entry", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"splitFlag", 9, "", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_long(SlightPHP_ce_ptr, 
		"_debug", 6, 0, 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

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
	REGISTER_STRINGL_CONSTANT("SlightPHP_VERSION", "0.2", 3, CONST_PERSISTENT | CONST_CS);
	class_init_SlightPHP(TSRMLS_C);
	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION(SlightPHP)
{
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
