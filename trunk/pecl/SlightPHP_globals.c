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
#include<SlightPHP_globals.h>
int debug(zval*_debug_flag,char*format,...){
	convert_to_long(_debug_flag);
	if(Z_LVAL_P(_debug_flag))
	{
		va_list args;
		char *buffer;
		int size;
		TSRMLS_FETCH();
		
		va_start(args, format);
		size = vspprintf(&buffer, 0, format, args);
		_php_error_log(0,buffer,NULL,NULL TSRMLS_CC);
		zend_printf("<!--slightphp debug:%s-->",buffer);
		efree(buffer);
		va_end(args);
	}
}


int SlightPHP_load(zval*appDir,zval*zone,zval*class_name,zval * _debug_flag TSRMLS_DC){
	char*inc_filename;
	int ret;
	spprintf(&inc_filename,0,"%s%c%s%c%s.class.php",Z_STRVAL_P(appDir),DEFAULT_SLASH,Z_STRVAL_P(zone),DEFAULT_SLASH,Z_STRVAL_P(class_name));
	zval file_name;
	ZVAL_STRING(&file_name,inc_filename,1);
	ret = SlightPHP_loadFile(&file_name , _debug_flag TSRMLS_CC);
	efree(inc_filename);
	return ret;
}
int SlightPHP_loadFile(zval *file_name,zval *_debug_flag TSRMLS_DC){
	int dummy = 1;
	zend_file_handle file_handle;
	zend_op_array *new_op_array;
	zval *result = NULL;
	int ret;
	ret = zend_stream_open(Z_STRVAL_P(file_name), &file_handle TSRMLS_CC);
	if (ret == SUCCESS) {
		if (!file_handle.opened_path) {
			file_handle.opened_path = estrndup(Z_STRVAL_P(file_name), Z_STRLEN_P(file_name));
		}
		if (zend_hash_add(&EG(included_files), file_handle.opened_path, strlen(file_handle.opened_path)+1, (void *)&dummy, sizeof(int), NULL)==SUCCESS) {
			new_op_array = zend_compile_file(&file_handle, ZEND_REQUIRE TSRMLS_CC);
			zend_destroy_file_handle(&file_handle TSRMLS_CC);
		} else {
			new_op_array = NULL;
			zend_file_handle_dtor(&file_handle);
		}
		if (new_op_array) {
			zend_execute(new_op_array TSRMLS_CC);
			destroy_op_array(new_op_array TSRMLS_CC);
			efree(new_op_array);
			return SUCCESS;
		}
	}else{
		debug(_debug_flag,"file[%s] not exists",Z_STRVAL_P(file_name));
	}
	return FAILURE;
}
int SlightPHP_run(zval*zone,zval*class_name,zval*method,zval**return_value, zval*_debug_flag TSRMLS_DC){
	zval *rt;
	zval *object;

	char *real_classname;
	spprintf(&real_classname,0,"%s_%s",Z_STRVAL_P(zone),Z_STRVAL_P(class_name));

	zval real_classname_zval;
	ZVAL_STRING(&real_classname_zval, real_classname, 1);
	efree(real_classname);

	char *real_method;
	spprintf(&real_method,0,"page%s",Z_STRVAL_P(method));

	zval real_method_zval;
	ZVAL_STRING(&real_method_zval, real_method, 1);
	efree(real_method);

	zend_class_entry * ce = NULL, **pce;
	if(zend_hash_find(EG(class_table), Z_STRVAL(real_classname_zval), Z_STRLEN(real_classname_zval)+1, (void **) &pce) ==FAILURE){
		debug(_debug_flag,"class[%s] not exists",Z_STRVAL(real_classname_zval));
		return FAILURE;
	} else {
		ce = *pce;
		MAKE_STD_ZVAL(object);
		object_init_ex(object,ce);

		if (ce->constructor) {
			zval tmp_method;
		    	ZVAL_STRING(&tmp_method, ce->constructor->common.function_name, 0);

		    	if(call_user_function_ex(NULL, &object, &tmp_method, &rt, 0, NULL, 0,NULL TSRMLS_CC)!=SUCCESS){
				FREE_ZVAL(object);
				return FAILURE;
			}
			if(rt) zval_dtor(rt);
		}

		if(zend_hash_exists(&Z_OBJCE_P(object)->function_table, Z_STRVAL(real_method_zval), Z_STRLEN(real_method_zval)+1)){
			if(call_user_function_ex(&Z_OBJCE_P(object)->function_table, &object, &real_method_zval, return_value, 0, NULL, 0,NULL TSRMLS_CC)!=SUCCESS){
			}else{
			}
		}else{
			debug(_debug_flag,"method[%s] not exists in class[%s]",Z_STRVAL(real_method_zval),Z_STRVAL(real_classname_zval));
			return FAILURE;
		}
		FREE_ZVAL(object);
	}
	return SUCCESS;
}
