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
#include "php_slightphp.h"

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
#include "slightphp_globals.h"
#include "slightphp_globals.c"

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
	if ((token= zend_hash_str_find(Z_ARRVAL_P(zoneAlias), zone,zone_len)) != NULL){
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
	zval *path;
	if (ZEND_NUM_ARGS()>0 && zend_parse_parameters(ZEND_NUM_ARGS() , "z/", &path) != FAILURE) {
		if (Z_TYPE_P(path)!= IS_STRING){
			RETURN_FALSE;
		}
		isPart = 1;
	}else{
		isPart = 0;

		zend_is_auto_global_str(ZEND_STRL("_SERVER"));
		zval *server_vars;
		if ((server_vars = zend_hash_str_find(&EG(symbol_table), ZEND_STRL("_SERVER"))) != NULL && Z_TYPE_P(server_vars) == IS_ARRAY){
			if((path= zend_hash_str_find(Z_ARRVAL_P(server_vars), ZEND_STRL("PATH_INFO")))!=NULL && Z_TYPE_P(path) == IS_STRING) {
				//
			}else if((path= zend_hash_str_find(Z_ARRVAL_P(server_vars), ZEND_STRL("REQUEST_URI")))!=NULL && Z_TYPE_P(path) == IS_STRING) {
				//
			}else{
				debug("path not set in params or server.path_info, server.request_uri");
				RETURN_FALSE;
			}
		}
	}
	/* Skip leading / */
	int len = Z_STRLEN_P(path);
	int start=0;
	for(start=0;start<len;start++){
		if(*(Z_STRVAL_P(path)+start) != '/'){
			break;
		}
	}
	zval url;
	php_url *resource=NULL;
	resource = php_url_parse(Z_STRVAL_P(path)+start);
	if(resource != NULL){
		if(resource->path != NULL){
			ZVAL_STRING(&url,resource->path);
		}else{
			ZVAL_STRING(&url,Z_STRVAL_P(path));
		}
		php_url_free(resource);	
	}else{
		ZVAL_STRING(&url,Z_STRVAL_P(path));
	}
	zend_update_static_property(slightphp_ce_ptr,"pathInfo",sizeof("pathInfo")-1,&url TSRMLS_CC);
	//zend_print_flat_zval_r(path);
	//}}}

	array_init(&path_array);

	{
		//{{{
		zval quotedFlag;
		regex_t re;
		char	*regex;
		regmatch_t subs[1];
		int err,size;
		char *strp = Z_STRVAL(url);
		char *endp = strp + Z_STRLEN(url);
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
		zval_dtor(&quotedFlag);
		//}}}
		if((zone = zend_hash_index_find(Z_ARRVAL(path_array), 0)) != NULL ) {
		}
		if((page = zend_hash_index_find(Z_ARRVAL(path_array), 1)) != NULL ) {
		}
		if((entry = zend_hash_index_find(Z_ARRVAL(path_array), 2)) != NULL) {
		}

	}
	if(!zone){
		zone = zend_read_static_property(slightphp_ce_ptr,"defaultZone",sizeof("defaultZone")-1,1 );
		add_next_index_string(&path_array, Z_STRVAL_P(zone));
	}
	if(!page){
		page = zend_read_static_property(slightphp_ce_ptr,"defaultPage",sizeof("defaultPage")-1,1 );
		add_next_index_string(&path_array, Z_STRVAL_P(page));
	}
	if(!entry){
		entry = zend_read_static_property(slightphp_ce_ptr,"defaultEntry",sizeof("defaultEntry")-1,1 );
		add_next_index_string(&path_array, Z_STRVAL_P(entry));
	}
	//{{{
	zval *zoneAlias = zend_read_static_property(slightphp_ce_ptr,"zoneAlias",sizeof("zoneAlias")-1,1 );
	if(zoneAlias && Z_TYPE_P(zoneAlias)==IS_ARRAY){
		zend_ulong num_key;
		zend_string *string_key= NULL;
		HashPosition pos;

		zval *entry2=NULL;
		zend_hash_internal_pointer_reset_ex(Z_ARRVAL_P(zoneAlias), &pos);
		for (;; zend_hash_move_forward_ex(Z_ARRVAL_P(zoneAlias), &pos)) {
			if (NULL == (entry2= zend_hash_get_current_data_ex(Z_ARRVAL_P(zoneAlias), &pos))) {
				break;
			}
			if(strcmp(Z_STRVAL_P(entry2) ,Z_STRVAL_P(zone))==0){
				switch (pos = zend_hash_get_current_key_ex(Z_ARRVAL_P(zoneAlias), &string_key, &num_key,&pos)) {
					case HASH_KEY_IS_STRING:
						ZVAL_STR_COPY(zone,string_key);
						break;
				}
			}
		}
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
			debug("part ignored [%s]",Z_STRVAL(url));
			zval_dtor(&path_array);
			zval_dtor(&url);
			return;
		}
	}

	zval *appDir = zend_read_static_property(slightphp_ce_ptr,"appDir",sizeof("appDir")-1,1 );
	if(slightphp_load(appDir,zone,page ) == SUCCESS){
		zval ret;
		if(slightphp_run(zone,page,entry,&ret,1,&path_array)==SUCCESS){
			zval_dtor(&path_array);
			zval_dtor(&url);
			RETURN_ZVAL(&ret,0,1);
		};
	}
	zval_dtor(&url);
	zval_dtor(&path_array);
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
	php_info_print_table_row(2, "Version", "3.4 stable(2016-11-07)" );
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
