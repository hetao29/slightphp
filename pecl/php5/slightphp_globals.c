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


int slightphp_loadFile(char*file_name TSRMLS_DC){
	zend_file_handle file_handle;
	int ret;
	file_handle.type = ZEND_HANDLE_FILENAME;
	file_handle.handle.fd = 0;
	file_handle.filename = file_name;
	file_handle.opened_path = NULL;
	file_handle.free_filename = 0;

	if(zend_hash_exists(&EG(included_files),file_name, strlen(file_name)+1)){
		return SUCCESS;
	}else{
		zend_try {
			ret = zend_execute_scripts(ZEND_INCLUDE TSRMLS_CC, NULL, 1, &file_handle);
		} zend_end_try();
	}
	return ret;
}


int slightphp_run(zval*zone,zval*class_name,zval*method,zval*return_value, int param_count,zval * params[] TSRMLS_DC){
	zval *object;
	int r;

	char *real_classname;
	spprintf(&real_classname,0,"%s_%s",Z_STRVAL_P(zone),Z_STRVAL_P(class_name));

	char *real_classname_lower = zend_str_tolower_dup(real_classname,strlen(real_classname));

	zval real_classname_zval;
	S_ZVAL_STRING(&real_classname_zval, real_classname);

	zval real_classname_check;
	S_ZVAL_STRING(&real_classname_check, real_classname_lower);
	efree(real_classname);
	efree(real_classname_lower);

	char *real_method;
	spprintf(&real_method,0,"page%s",Z_STRVAL_P(method));

	zval real_method_zval;
	S_ZVAL_STRING(&real_method_zval, real_method);
	efree(real_method);

	zend_class_entry * ce = NULL, **pce;



	if(zend_hash_find(EG(class_table),Z_STRVAL(real_classname_check),Z_STRLEN(real_classname_check)+1,(void **)&pce)==FAILURE){
		debug("class[%s] not exists",Z_STRVAL(real_classname_zval));
		zval_dtor(&real_classname_zval);
		zval_dtor(&real_classname_check);
		zval_dtor(&real_method_zval);
		return FAILURE;
	} else {

		ce = *pce;
		MAKE_STD_ZVAL(object);
		object_init_ex(object,ce);



		if (ce->constructor) {
			zval c_ret;
			INIT_ZVAL(c_ret);
			zval tmp_method;
			S_ZVAL_STRING(&tmp_method, ce->constructor->common.function_name);
			if(call_user_function(NULL, &object, &tmp_method, &c_ret, param_count, params TSRMLS_CC)!=SUCCESS){
				php_error_docref(NULL TSRMLS_CC, E_ERROR, "Error calling constructor");
			}           
			zval_dtor(&tmp_method);
			zval_dtor(&c_ret);
		}   
		r=call_user_function(NULL, &object, &real_method_zval, return_value, param_count, params TSRMLS_CC);
		if(r!=SUCCESS){
			debug("method[%s] not exists in class[%s]",Z_STRVAL(real_method_zval),Z_STRVAL(real_classname_zval));
		}
	}
	zval_dtor(&real_classname_zval);
	zval_dtor(&real_classname_check);
	zval_dtor(&real_method_zval);
	zval_ptr_dtor(&object);
	return r;
}

