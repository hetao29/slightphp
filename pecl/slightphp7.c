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

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include <regex.h>
#include "ext/standard/info.h"
#include "ext/standard/url.h"
#include "php_slightphp7.h"

/*
static void print_flat_hash(HashTable *ht) 
{
	zval *tmp;
	zend_string *string_key;
	zend_ulong num_key;
	int i = 0;

	ZEND_HASH_FOREACH_KEY_VAL_IND(ht, num_key, string_key, tmp) {
		if (i++ > 0) {
			ZEND_PUTS(",");
		}
		ZEND_PUTS("[");
		if (string_key) {
			ZEND_WRITE(ZSTR_VAL(string_key), ZSTR_LEN(string_key));
		} else {
			zend_printf(ZEND_ULONG_FMT, num_key);
		}
		ZEND_PUTS("] => ");
		zend_print_flat_zval_r(tmp);
	} ZEND_HASH_FOREACH_END();
}
*/

typedef struct _op_item{
	time_t op_mtime;
	zend_op_array op_codes;
}op_item;
/* {{{ Class definitions */

/* {{{ Class slightphp */

static zend_class_entry * slightphp_ce_ptr = NULL;
#include "slightphp_globals7.h"
#include "slightphp_globals7.c"

/* {{{ Methods */


/* {{{ proto void setAppDir(mixed appDir)
 */
PHP_METHOD(slightphp, setAppDir)
{
	char* appDir;
	size_t appDir_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &appDir,&appDir_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(slightphp_ce_ptr, "appDir", sizeof("appDir")-1, appDir );
	RETURN_TRUE;
}
/* }}} setAppDir */
/* {{{ proto void setPathInfo(mixed pathInfo)
 */
PHP_METHOD(slightphp, setPathInfo)
{
	char* pathInfo;
	size_t pathInfo_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &pathInfo,&pathInfo_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(slightphp_ce_ptr, "pathInfo", sizeof("pathInfo")-1, pathInfo );
	RETURN_TRUE;
}
/* }}} setPathInfo */



/* {{{ proto mixed getAppDir()
 */
PHP_METHOD(slightphp, getAppDir)
{
	zval *data = zend_read_static_property(slightphp_ce_ptr,"appDir",sizeof("appDir")-1,1 );
	RETURN_ZVAL(data,1,0);
}
/* }}} getAppDir */


PHP_METHOD(slightphp, setDefaultZone)
{
	char* defaultZone= NULL;
	size_t defaultZone_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &defaultZone,&defaultZone_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(slightphp_ce_ptr, "defaultZone", sizeof("defaultZone")-1, defaultZone );
	RETURN_TRUE;
}
PHP_METHOD(slightphp, getDefaultZone)
{
	zval *data = zend_read_static_property(slightphp_ce_ptr,"defaultZone",sizeof("defaultZone")-1,1 );
	RETURN_ZVAL(data,1,0);
}




PHP_METHOD(slightphp, setDefaultPage)
{
	char * defaultPage= NULL;
	size_t defaultPage_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &defaultPage,&defaultPage_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(slightphp_ce_ptr, "defaultPage", sizeof("defaultPage")-1, defaultPage );
	RETURN_TRUE;
}
PHP_METHOD(slightphp, getDefaultPage)
{
	zval *data = zend_read_static_property(slightphp_ce_ptr,"defaultPage",sizeof("defaultPage")-1,1 );
	RETURN_ZVAL(data,1,0);
}




PHP_METHOD(slightphp, setDefaultEntry)
{
	char * defaultEntry= NULL;
	size_t defaultEntry_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &defaultEntry,&defaultEntry_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(slightphp_ce_ptr, "defaultEntry", sizeof("defaultEntry")-1, defaultEntry );
	RETURN_TRUE;
}
PHP_METHOD(slightphp, getDefaultEntry)
{
	zval *data = zend_read_static_property(slightphp_ce_ptr,"defaultEntry",sizeof("defaultEntry")-1,1 );
	RETURN_ZVAL(data,1,0);
}



PHP_METHOD(slightphp, setSplitFlag)
{
	char * splitFlag = NULL;
	size_t splitFlag_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &splitFlag, &splitFlag_len) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_string(slightphp_ce_ptr, "splitFlag", sizeof("splitFlag")-1, splitFlag );
	RETURN_TRUE;
}
PHP_METHOD(slightphp, getSplitFlag)
{
	zval *data = zend_read_static_property(slightphp_ce_ptr,"splitFlag",sizeof("splitFlag")-1,1 );
	RETURN_ZVAL(data,1,0);
}


PHP_METHOD(slightphp, setZoneAlias)
{
	char *zone, *alias;
	size_t zone_len, alias_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "ss", &zone, &zone_len, &alias ,&alias_len) == FAILURE) {
		RETURN_FALSE;
	}
	zval *zoneAlias = zend_read_static_property(slightphp_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 );
	if(!zoneAlias){ RETURN_FALSE; }

	if(Z_TYPE_P(zoneAlias)!=IS_ARRAY){
		array_init(zoneAlias);
	}
	add_assoc_string(zoneAlias,zone,alias);
	zend_update_static_property(slightphp_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,zoneAlias );
	RETURN_TRUE;
}

PHP_METHOD(slightphp, getZoneAlias)
{
	char * zone= NULL;
	size_t zone_len;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "s", &zone, &zone_len) == FAILURE) {
		RETURN_FALSE;
	}
	zval *zoneAlias = zend_read_static_property(slightphp_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 );
	if(!zoneAlias || Z_TYPE_P(zoneAlias)!=IS_ARRAY){ RETURN_FALSE; }
	zval *token;
	if((token = zend_hash_find(Z_ARRVAL_P(zoneAlias),zend_string_init(zone, strlen(zone) , 0))) != NULL) {
		*return_value = *token;
		zval_copy_ctor(return_value);
	}else{
		RETURN_FALSE;
	}
}

PHP_METHOD(slightphp, setDebug)
{
	zend_long _debug;
	if (zend_parse_parameters(ZEND_NUM_ARGS() , "l", &_debug) == FAILURE) {
		RETURN_FALSE;
	}
	zend_update_static_property_long(slightphp_ce_ptr, "_debug", sizeof("_debug")-1, _debug );
	RETURN_TRUE;
}
PHP_METHOD(slightphp, getDebug)
{
	zval *data = zend_read_static_property(slightphp_ce_ptr,"_debug",sizeof("_debug")-1,1 );
	convert_to_long(data);
	RETURN_BOOL(Z_LVAL_P(data));
}


/* {{{ proto void __construct([mixed version])
 */
PHP_METHOD(slightphp, __construct)
{
	//zend_class_entry * _this_ce;
	//zval * _this_zval;

	//zval * version = NULL;

	//if (zend_parse_parameters(ZEND_NUM_ARGS() , "|z/", &version) == FAILURE) {
	//	return;
	//}

	//_this_zval = getThis();
	//_this_ce = Z_OBJCE_P(_this_zval);
}
/* }}} __construct */



/* {{{ proto void run()
 */
PHP_METHOD(slightphp, run)
{
	zval *zone=NULL;
	zval *page=NULL;
	zval *entry=NULL;

	zval path_array;

	//{{{
	int isPart;
	zval * path = NULL;
	zval path_7;
	if (ZEND_NUM_ARGS()>0 && zend_parse_parameters(ZEND_NUM_ARGS() , "z/", &path) != FAILURE) {
		if (Z_TYPE_P(path)!= IS_STRING){
			RETURN_FALSE;
		}
		isPart = 1;
	}else{
		isPart = 0;
		path = zend_read_static_property(slightphp_ce_ptr,"pathInfo",sizeof("pathInfo")-1,1 );
		int s = Z_STRLEN_P(path);
		if(s==0){

			zend_string *server = zend_string_init("_SERVER", sizeof("_SERVER") - 1, 0);
			zend_is_auto_global(server);
			zval *server_vars;
			zval *token= NULL;
			if ((server_vars = zend_hash_find(&EG(symbol_table), server)) != NULL && Z_TYPE_P(server_vars) == IS_ARRAY){
				if((path = zend_hash_str_find(Z_ARRVAL_P(server_vars), "PATH_INFO", sizeof("PATH_INFO")-1))!=NULL && Z_TYPE_P(path) == IS_STRING) {
				}else if((token = zend_hash_str_find(Z_ARRVAL_P(server_vars), "REQUEST_URI", sizeof("REQUEST_URI")-1))!=NULL && Z_TYPE_P(token) == IS_STRING) {
					php_url *resource=NULL;
					resource = php_url_parse(Z_STRVAL_P(token));
					if(resource != NULL && resource->path != NULL){
						ZVAL_STRING(&path_7, resource->path);
						path = &path_7;
					}else{
						path = token;
					}
					if (resource) {
						php_url_free(resource);	
					}
				}
			}


			zend_string_release(server);
			if(server_vars)zval_dtor(server_vars);
			if(token)zval_dtor(token);
		}
		//zend_print_flat_zval_r(path);
	}
	//}}}

	array_init(&path_array);

	if( path != NULL){
		//{{{
		zval quotedFlag;
		regex_t re;
		char	*regex;
		regmatch_t subs[1];
		int err,size;
		char *strp = Z_STRVAL_P(path);
		char *endp = strp + Z_STRLEN_P(path);
		zval *splitFlag = zend_read_static_property(slightphp_ce_ptr,"splitFlag",sizeof("splitFlag")-1,1 );

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
					add_next_index_stringl(&path_array, strp, size);
					strp += size;

				}
			}
			if (!err || err == REG_NOMATCH) {
				size = endp - strp;
				if(size>0) add_next_index_stringl(&path_array, strp, size);
			}
			regfree(&re);
		}
		efree(regex);
		//print_flat_hash(Z_ARRVAL_P(&path_array));
		//}}}
		//int n_elems = zend_hash_num_elements(Z_ARRVAL_P(&path_array));
		if((zone = zend_hash_index_find(Z_ARRVAL_P(&path_array), 0)) != NULL ) {
		}
		if((page = zend_hash_index_find(Z_ARRVAL_P(&path_array), 1)) != NULL ) {
		}
		if((entry = zend_hash_index_find(Z_ARRVAL_P(&path_array), 2)) != NULL) {
		}

	}
	if(!zone){
		zone = zend_read_static_property(slightphp_ce_ptr,"defaultZone",sizeof("defaultZone")-1,1 );
		zend_hash_next_index_insert(Z_ARRVAL_P(&path_array),zone);
	}
	if(!page){
		page = zend_read_static_property(slightphp_ce_ptr,"defaultPage",sizeof("defaultPage")-1,1 );
		zend_hash_next_index_insert(Z_ARRVAL_P(&path_array),page);
	}
	if(!entry){
		entry = zend_read_static_property(slightphp_ce_ptr,"defaultEntry",sizeof("defaultEntry")-1,1 );
		zend_hash_next_index_insert(Z_ARRVAL_P(&path_array),entry);
	}
	//zend_print_flat_zval_r(zone);
	//zend_print_flat_zval_r(page);
	//zend_print_flat_zval_r(entry);
	//{{{
	zval *zoneAlias = zend_read_static_property(slightphp_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 );
	if(zoneAlias && Z_TYPE_P(zoneAlias)==IS_ARRAY){
		zend_ulong num_key;
		zend_string *string_key= NULL;
		HashPosition pos;

		zval *entry2=NULL;
		zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(zoneAlias), &pos);
		while ((pos = zend_hash_get_current_data_ex(Z_ARRVAL_P(zoneAlias), (void *)entry2 ) == SUCCESS)) {
			if(strcmp(Z_STRVAL_P(entry2) ,Z_STRVAL_P(zone))==0){
				switch (pos = zend_hash_get_current_key_ex(Z_ARRVAL_P(zoneAlias), &string_key, &num_key,&pos)) {
					case HASH_KEY_IS_STRING:
						ZVAL_NEW_STR(zone,string_key);
						break;
				}
			}
			zend_hash_move_forward_ex(Z_ARRVAL_P(zoneAlias), &pos);
		}
		if(entry2)zval_ptr_dtor(entry2);
		if(string_key)zend_string_release(string_key);
	}
	//}}}
	if(!isPart){
		zend_update_static_property(slightphp_ce_ptr,"zone",sizeof("zone")-1,zone );
		zend_update_static_property(slightphp_ce_ptr,"page",sizeof("page")-1,page );
		zend_update_static_property(slightphp_ce_ptr,"entry",sizeof("entry")-1,entry );
	}else{
		if(
				strcmp(Z_STRVAL_P(zone),Z_STRVAL_P(zend_read_static_property(slightphp_ce_ptr,"zone",sizeof("zone")-1,1 )))==0 
				&&
				strcmp(Z_STRVAL_P(page),Z_STRVAL_P(zend_read_static_property(slightphp_ce_ptr,"page",sizeof("page")-1,1 )))==0 
				&&
				strcmp(Z_STRVAL_P(entry),Z_STRVAL_P(zend_read_static_property(slightphp_ce_ptr,"entry",sizeof("entry")-1,1 )))==0 
		  ){
			debug("part ignored [%s]",Z_STRVAL_P(path));
			return;
		}
	}


	zval *appDir = zend_read_static_property(slightphp_ce_ptr,"appDir",sizeof("appDir")-1,1 );

	zval params[1];
	params[0]=path_array;


	if(slightphp_load(appDir,zone,page ) == SUCCESS){
		if(slightphp_run(zone,page,entry,return_value,1,params )==SUCCESS){
			zval_dtor(&path_array);
			if(appDir)zval_dtor(appDir);
			if(zone)zval_dtor(zone);
			if(page)zval_dtor(page);
			if(entry)zval_dtor(entry);
			if(zoneAlias)zval_dtor(zoneAlias);
			if(path)zval_dtor(path);
			zval_dtor(params);
			RETURN_ZVAL(return_value,1,0);
		};
	}
	if(appDir)zval_dtor(appDir);
	if(zone)zval_dtor(zone);
	if(page)zval_dtor(page);
	if(entry)zval_dtor(entry);
	if(zoneAlias)zval_dtor(zoneAlias);
	if(path)zval_dtor(path);
	zval_dtor(&path_array);
	zval_dtor(params);
	RETURN_FALSE;
}
/* }}} run */


static zend_function_entry slightphp_methods[] = {

	PHP_ME(slightphp, setAppDir, slightphp__setAppDir_args, /**/ZEND_ACC_STATIC|ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, setPathInfo , slightphp__setPathInfo_args, /**/ZEND_ACC_STATIC|ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getAppDir, NULL, /**/ZEND_ACC_STATIC|ZEND_ACC_PUBLIC)

		//PHP_ME(slightphp, setPluginsDir, slightphp__setPluginsDir_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		//PHP_ME(slightphp, getPluginsDir, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

		PHP_ME(slightphp, setDefaultZone , slightphp__setDefaultZone_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getDefaultZone, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

		PHP_ME(slightphp, setDefaultPage, slightphp__setDefaultPage_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getDefaultPage, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

		PHP_ME(slightphp, setDefaultEntry, slightphp__setDefaultEntry_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getDefaultEntry, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

		PHP_ME(slightphp, setDebug, slightphp__setDebug_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getDebug, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

		PHP_ME(slightphp, setSplitFlag, slightphp__setSplitFlag_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getSplitFlag, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)

		PHP_ME(slightphp, setZoneAlias, slightphp__setZoneAlias_arg, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, getZoneAlias, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		//PHP_ME(slightphp, loadFile, slightphp__loadFile_args, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		//PHP_ME(slightphp, loadPlugin, slightphp__loadPlugin_args, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC)
		PHP_ME(slightphp, __construct, NULL, /**/ZEND_ACC_PUBLIC | ZEND_ACC_CTOR)
		PHP_ME(slightphp, run, NULL, /**/ZEND_ACC_STATIC | ZEND_ACC_PUBLIC | ZEND_ACC_FINAL)
		{ NULL, NULL, NULL }
};

/* }}} Methods */

static void class_init_slightphp(TSRMLS_D)
{
	zend_class_entry ce;

	INIT_CLASS_ENTRY(ce, "slightphp", slightphp_methods);
	slightphp_ce_ptr = zend_register_internal_class(&ce );
	slightphp_ce_ptr->ce_flags |= ZEND_ACC_FINAL;

	/* {{{ Property registration */

	zend_declare_property_string(slightphp_ce_ptr, 
			"appDir", sizeof("appDir")-1 , ".", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"pathInfo", 8, "", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );
	//zend_declare_property_string(slightphp_ce_ptr, 
	//	"pluginsDir", 10, "plugins", 
	//	ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"defaultZone", 11, "zone", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"zone", sizeof("zone")-1, "", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"page", sizeof("page")-1, "", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"entry", sizeof("entry")-1, "", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"defaultPage", sizeof("defaultPage")-1, "page", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"defaultEntry", sizeof("defaultEntry")-1, "entry", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_string(slightphp_ce_ptr, 
			"splitFlag", 9, "/", 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );


	zend_declare_property_null(slightphp_ce_ptr, 
			"zoneAlias", sizeof("zoneAlias")-1,
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	zend_declare_property_long(slightphp_ce_ptr, 
			"_debug", 6, 0, 
			ZEND_ACC_STATIC|ZEND_ACC_PUBLIC );

	/* }}} Property registration */

}

/* }}} Class slightphp */

/* }}} Class definitions*/

/* {{{ slightphp_functions[] */
static zend_function_entry slightphp_functions[] = {
	{ NULL, NULL, NULL }
};
/* }}} */

/* {{{ cross-extension dependencies */

#if ZEND_EXTENSION_API_NO >= 220050617
static zend_module_dep slightphp_deps[] = {
	ZEND_MOD_OPTIONAL("apc")
	{NULL, NULL, NULL, 0}
};
#endif
/* }}} */

/* {{{ slightphp_module_entry
 */
zend_module_entry slightphp_module_entry = {
#if ZEND_EXTENSION_API_NO >= 220050617
	STANDARD_MODULE_HEADER_EX, NULL,
	slightphp_deps,
#else
	STANDARD_MODULE_HEADER,
#endif

	"SlightPHP",
	slightphp_functions,
	PHP_MINIT(slightphp),     /* Replace with NULL if there is nothing to do at php startup   */ 
	PHP_MSHUTDOWN(slightphp), /* Replace with NULL if there is nothing to do at php shutdown  */
	PHP_RINIT(slightphp),     /* Replace with NULL if there is nothing to do at request start */
	PHP_RSHUTDOWN(slightphp), /* Replace with NULL if there is nothing to do at request end   */
	PHP_MINFO(slightphp),
	"0.1", 
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_SLIGHTPHP
ZEND_GET_MODULE(slightphp)
#endif


	/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(slightphp)
{
	REGISTER_STRINGL_CONSTANT("slightphp_VERSION", "0.2", 3, CONST_PERSISTENT | CONST_CS);
	class_init_slightphp(TSRMLS_C);
	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION(slightphp)
{
	return SUCCESS;
}
/* }}} */


/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION(slightphp)
{

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION(slightphp)
{

	return SUCCESS;
}
/* }}} */


/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(slightphp)
{
	php_info_print_table_start();
	php_info_print_table_colspan_header(2,"SlightPHP Framework");
	php_info_print_table_row(2, "Version", "3.0 stable(2016-3-18)" );
	php_info_print_table_row(2, "Authors", "hetao@hetao.name" );
	php_info_print_table_row(2, "Supports", "https://github.com/hetao29/slightphp" );
	php_info_print_table_end();
	/* add your stuff here */

}
/* }}} */



/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
