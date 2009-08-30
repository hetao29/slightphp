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

/*
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
*/


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




PHP_METHOD(SlightPHP, setDefaultPage)
{
	char * defaultPage= NULL;
	int defaultPage_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &defaultPage,&defaultPage_len) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "defaultPage", sizeof("defaultPage")-1, defaultPage TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getDefaultPage)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"defaultPage",sizeof("defaultPage")-1,1 TSRMLS_CC);
	RETURN_ZVAL(data,1,0);
}




PHP_METHOD(SlightPHP, setDefaultEntry)
{
	char * defaultEntry= NULL;
	int defaultEntry_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &defaultEntry,&defaultEntry_len) == FAILURE) {
			RETURN_FALSE;
	}
	zend_update_static_property_string(SlightPHP_ce_ptr, "defaultEntry", sizeof("defaultEntry")-1, defaultEntry TSRMLS_CC);
	RETURN_TRUE;
}
PHP_METHOD(SlightPHP, getDefaultEntry)
{
	zval *data = zend_read_static_property(SlightPHP_ce_ptr,"defaultEntry",sizeof("defaultEntry")-1,1 TSRMLS_CC);
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


PHP_METHOD(SlightPHP, setZoneAlias)
{
	char *zone, *alias;
	int zone_len, alias_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss", &zone, &zone_len, &alias ,&alias_len) == FAILURE) {
			RETURN_FALSE;
	}
	zval *zoneAlias = zend_read_static_property(SlightPHP_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 TSRMLS_CC);
	if(!zoneAlias){ RETURN_FALSE; }

	if(Z_TYPE_P(zoneAlias)!=IS_ARRAY){
		array_init(zoneAlias);
	}
	add_assoc_string(zoneAlias,zone,alias,1);
	zend_update_static_property(SlightPHP_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,zoneAlias TSRMLS_CC);
	RETURN_TRUE;
}

PHP_METHOD(SlightPHP, getZoneAlias)
{
	char * zone= NULL;
	int zone_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &zone, &zone_len) == FAILURE) {
			RETURN_FALSE;
	}
	zval *zoneAlias = zend_read_static_property(SlightPHP_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 TSRMLS_CC);
	if(!zoneAlias || Z_TYPE_P(zoneAlias)!=IS_ARRAY){ RETURN_FALSE; }
	zval **token;
	if(zend_hash_find(Z_ARRVAL_P(zoneAlias),zone, zone_len+1, (void**)&token) == SUCCESS) {
		*return_value = **token;
		zval_copy_ctor(return_value);
	}else{
		RETURN_FALSE;
	}
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
/*
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
*/
/* }}} loadFile */



/* {{{ proto int loadPlugin(mixed pluginName)
   */
/*
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
*/
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
	zval *page=NULL;
	zval *entry=NULL;

	zval **token;
	//zval *path=NULL;
	zval *server=NULL;
	zval *path_array;

	//{{{
	int isPart;
	zval * path = NULL;
	if (ZEND_NUM_ARGS()>0 && zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z/", &path) != FAILURE) {
		if (Z_TYPE_P(path)!= IS_STRING){
				RETURN_FALSE;
		}
		isPart = 1;
	}else{
		isPart = 0;
		zend_is_auto_global("_SERVER", sizeof("_SERVER") - 1 TSRMLS_CC);
		server = PG(http_globals)[TRACK_VARS_SERVER];
		if(!server){
			RETURN_FALSE;
		}
		if(zend_hash_find(HASH_OF(server), "PATH_INFO", sizeof("PATH_INFO"), (void **) &token) == SUCCESS
		){
			path = *token;
		}
	}

	//}}}

	MAKE_STD_ZVAL(path_array);
	array_init(path_array);

	if (path){
		//{{{
		zval quotedFlag;
		regex_t re;
		char	*regex;
		regmatch_t subs[1];
		int err,size;
		char *strp = Z_STRVAL_P(path);
		char *endp = strp + Z_STRLEN_P(path);
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
		int n_elems = zend_hash_num_elements(Z_ARRVAL_P(path_array));
		if(zend_hash_index_find(Z_ARRVAL_P(path_array), 0, (void **)&token) != FAILURE) {
			zone = *token;
		}
		if(zend_hash_index_find(Z_ARRVAL_P(path_array), 1, (void **)&token) != FAILURE) {
			page = *token;
		}
		if(zend_hash_index_find(Z_ARRVAL_P(path_array), 2, (void **)&token) != FAILURE) {
			entry = *token;
		}
			
	}
	if(!zone){
		zone = zend_read_static_property(SlightPHP_ce_ptr,"defaultZone",sizeof("defaultZone")-1,1 TSRMLS_CC);
		zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&zone,sizeof(zval*),NULL);
	}
	if(!page){
		page = zend_read_static_property(SlightPHP_ce_ptr,"defaultPage",sizeof("defaultPage")-1,1 TSRMLS_CC);
		zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&page,sizeof(zval*),NULL);
	}
	if(!entry){
		entry = zend_read_static_property(SlightPHP_ce_ptr,"defaultEntry",sizeof("defaultEntry")-1,1 TSRMLS_CC);
		zend_hash_next_index_insert(Z_ARRVAL_P(path_array),&entry,sizeof(zval*),NULL);
	}
	//{{{
	zval *zoneAlias = zend_read_static_property(SlightPHP_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 TSRMLS_CC);
	if(zoneAlias && Z_TYPE_P(zoneAlias)==IS_ARRAY){
		char *string_key;uint str_key_len;ulong num_key;
		HashPosition pos;
		zval **entry;
		zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(zoneAlias), &pos);
		while (zend_hash_get_current_data_ex(Z_ARRVAL_P(zoneAlias), (void **)&entry, &pos) == SUCCESS) {
			if(strcmp(Z_STRVAL_PP(entry) ,Z_STRVAL_P(zone))==0){
				switch (zend_hash_get_current_key_ex(Z_ARRVAL_P(zoneAlias), &string_key, &str_key_len, &num_key, 0, &pos)) {
					case HASH_KEY_IS_STRING:
						ZVAL_STRING(zone,string_key,1);
						break;
				}
			}
			zend_hash_move_forward_ex(Z_ARRVAL_P(zoneAlias), &pos);
		}
	}
	//}}}
	if(!isPart){
		zend_update_static_property(SlightPHP_ce_ptr,"zone",sizeof("zone")-1,zone TSRMLS_CC);
		zend_update_static_property(SlightPHP_ce_ptr,"page",sizeof("page")-1,page TSRMLS_CC);
		zend_update_static_property(SlightPHP_ce_ptr,"entry",sizeof("entry")-1,entry TSRMLS_CC);
	}else{
		if(
			strcmp(Z_STRVAL_P(zone),Z_STRVAL_P(zend_read_static_property(SlightPHP_ce_ptr,"zone",sizeof("zone")-1,1 TSRMLS_CC)))==0 
			&&
			strcmp(Z_STRVAL_P(page),Z_STRVAL_P(zend_read_static_property(SlightPHP_ce_ptr,"page",sizeof("page")-1,1 TSRMLS_CC)))==0 
			&&
			strcmp(Z_STRVAL_P(entry),Z_STRVAL_P(zend_read_static_property(SlightPHP_ce_ptr,"entry",sizeof("entry")-1,1 TSRMLS_CC)))==0 
			){
			debug("part ignored [%s]",Z_STRVAL_P(path));
			return;
		}
	}

	zval *tmp_result=NULL;

	zval *appDir = zend_read_static_property(SlightPHP_ce_ptr,"appDir",sizeof("appDir")-1,1 TSRMLS_CC);
	
	zval **params[1];
	params[0]=&path_array;


	if(SlightPHP_load(appDir,zone,page TSRMLS_CC) == SUCCESS){
		if(SlightPHP_run(zone,page,entry,&tmp_result,1,params TSRMLS_CC)==SUCCESS){
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

	//PHP_ME(SlightPHP, setPluginsDir, SlightPHP__setPluginsDir_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	//PHP_ME(SlightPHP, getPluginsDir, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDefaultZone , SlightPHP__setDefaultZone_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDefaultZone, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDefaultPage, SlightPHP__setDefaultPage_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDefaultPage, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDefaultEntry, SlightPHP__setDefaultEntry_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDefaultEntry, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setDebug, SlightPHP__setDebug_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getDebug, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setSplitFlag, SlightPHP__setSplitFlag_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getSplitFlag, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

	PHP_ME(SlightPHP, setZoneAlias, SlightPHP__setZoneAlias_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, getZoneAlias, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	//PHP_ME(SlightPHP, loadFile, SlightPHP__loadFile_args, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	//PHP_ME(SlightPHP, loadPlugin, SlightPHP__loadPlugin_args, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
	PHP_ME(SlightPHP, __construct, NULL, /**/ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
	PHP_ME(SlightPHP, run, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC | ZEND_ACC_FINAL)
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

	//zend_declare_property_string(SlightPHP_ce_ptr, 
	//	"pluginsDir", 10, "plugins", 
	//	ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultZone", 11, "zone", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"zone", sizeof("zone")-1, "", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"page", sizeof("page")-1, "", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"entry", sizeof("entry")-1, "", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultPage", sizeof("defaultPage")-1, "page", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"defaultEntry", sizeof("defaultEntry")-1, "entry", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);

	zend_declare_property_string(SlightPHP_ce_ptr, 
		"splitFlag", 9, "/", 
		ZEND_ACC_STATIC|ZEND_ACC_PUBLIC TSRMLS_CC);


	zend_declare_property_null(SlightPHP_ce_ptr, 
		"zoneAlias", sizeof("zoneAlias")-1,
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
	php_info_print_table_start();
	php_info_print_table_colspan_header(2,"SlightPHP Framework");
	php_info_print_table_row(2, "Version", "0.9 stable (r135) (2009-08-25)" );
	php_info_print_table_row(2, "Authors", "admin@slightphp.com, hetao@hetao.name" );
	php_info_print_table_row(2, "Supports", "http://www.slightphp.com" );
	php_info_print_table_end();
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
