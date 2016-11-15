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
	zend_string * real_file_name= zend_string_init(file_name, strlen(file_name), 1);
	if(zend_hash_exists(&EG(included_files),real_file_name)){
		zend_string_release(real_file_name);
		return SUCCESS;
	}else{
		zend_try {
			ret = zend_execute_scripts(ZEND_INCLUDE TSRMLS_CC, NULL, 1, &file_handle);
		} zend_end_try();
	}
	zend_string_release(real_file_name);
	return ret;
}


int slightphp_run(zval*zone,zval*class_name,zval*method,zval*return_value, int param_count,zval params[] TSRMLS_DC){
	zval object;
	int r;

	char *real_classname;
	spprintf(&real_classname,0,"%s_%s",Z_STRVAL_P(zone),Z_STRVAL_P(class_name));


	zend_string * real_classname_zval = zend_string_init(real_classname, strlen(real_classname), 1);
	zend_string * real_classname_check = zend_string_tolower(real_classname_zval);
	efree(real_classname);

	char *real_method;
	spprintf(&real_method,0,"page%s",Z_STRVAL_P(method));

	zval real_method_zval;
	ZVAL_STRING(&real_method_zval, real_method);
	efree(real_method);

	zend_class_entry * ce = NULL;//, **pce;
	ce = zend_hash_find_ptr(EG(class_table),real_classname_check);
	zend_string_release(real_classname_check);
	if(ce == NULL){
		debug("class[%s] not exists",ZSTR_VAL(real_classname_zval));
		zend_string_release(real_classname_zval);
		zval_dtor(&real_method_zval);
		zval_dtor(&object);
		return FAILURE;
	} else {
		object_init_ex(&object,ce);
		if (ce->constructor) {
			zval c_ret;
			zval tmp_method;
			ZVAL_STRING(&tmp_method, ZEND_CONSTRUCTOR_FUNC_NAME);
			if(call_user_function(NULL, &object, &tmp_method, &c_ret, param_count, params )!=SUCCESS){
				php_error_docref(NULL , E_ERROR, "Error calling constructor");
			}           
			zval_dtor(&tmp_method);
			zval_dtor(&c_ret);
		}   
		r=call_user_function(NULL, &object, &real_method_zval, return_value, param_count, params );
		if(r!=SUCCESS){
			debug("method[%s] not exists in class[%s]",Z_STRVAL(real_method_zval),ZSTR_VAL(real_classname_zval));
		}
	}
	zend_string_release(real_classname_zval);
	zval_dtor(&real_method_zval);
	zval_dtor(&object);
	return r;
}

