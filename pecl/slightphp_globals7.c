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
int debug(char*format,...){
	TSRMLS_FETCH();
	zval *_debug_flag = zend_read_static_property(slightphp_ce_ptr,"_debug",sizeof("_debug")-1,1 );
	convert_to_long(_debug_flag);
	int size=0;
	if(Z_LVAL_P(_debug_flag))
	{
		va_list args;
		char *buffer;
		TSRMLS_FETCH();

		va_start(args, format);
		size = vspprintf(&buffer, 0, format, args);
		_php_error_log(0,buffer,NULL,NULL );
		zend_printf("<!--slightphp debug:%s-->",buffer);
		efree(buffer);
		va_end(args);
	}
	zval_dtor(_debug_flag);
	return size;
}


int slightphp_load(zval*appDir,zval*zone,zval*class_name TSRMLS_DC){
	char*inc_filename;
	int ret;
	spprintf(&inc_filename,0,"%s%c%s%c%s.page.php",
			Z_STRVAL_P(appDir),
			DEFAULT_SLASH,
			Z_STRVAL_P(zone),
			DEFAULT_SLASH,
			Z_STRVAL_P(class_name)
		);
	zval file_name;
	ZVAL_STRING(&file_name,inc_filename);
	efree(inc_filename);

	char resolved_path_buff[MAXPATHLEN];
	if (VCWD_REALPATH(Z_STRVAL(file_name), resolved_path_buff)) {
		ret = slightphp_loadFile((char*)&resolved_path_buff );
		zval_dtor(&file_name);
		return ret;
	}else{
		debug("file[%s] not exists",Z_STRVAL(file_name));
	}
	zval_dtor(&file_name);
	return FAILURE;
}
int slightphp_loadFile(char*file_name TSRMLS_DC){
	zend_file_handle file_handle;
	int ret;

	if(zend_stream_open(file_name, &file_handle )==SUCCESS){;
		if (!file_handle.opened_path) {
			file_handle.opened_path = zend_string_init(file_name, strlen(file_name),0 );
		}
		if(file_handle.opened_path) {
			if(zend_hash_exists(&EG(included_files),file_handle.opened_path)){
				zend_destroy_file_handle(&file_handle );
				return SUCCESS;
			}else{
				ret = zend_execute_scripts(ZEND_REQUIRE_ONCE , NULL, 1, &file_handle);
				zend_destroy_file_handle(&file_handle );
				return ret;
			}
		}
	}
	return FAILURE;
}
int slightphp_run(zval*zone,zval*class_name,zval*method,zval*return_value, int param_count,zval params[] TSRMLS_DC){
	zval object;

	char *real_classname;
	spprintf(&real_classname,0,"%s_%s",
			Z_STRVAL_P(zone),
			Z_STRVAL_P(class_name)
			);


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
		int r=call_user_function(NULL, &object, &real_method_zval, return_value, param_count, params );
		if(r!=SUCCESS){
			debug("method[%s] not exists in class[%s]",Z_STRVAL(real_method_zval),ZSTR_VAL(real_classname_zval));
			zend_string_release(real_classname_zval);
			zval_dtor(&real_method_zval);
			zval_dtor(&object);
			return FAILURE;
		}
	}
	zend_string_release(real_classname_zval);
	zval_dtor(&real_method_zval);
	zval_dtor(&object);
	return SUCCESS;
}

int preg_quote(zval *in_str,zval*out_str){
	if(Z_STRLEN_P(in_str)==0){
		return 0;
	}
	char	*tmp_str, *p, *q, c;

	tmp_str= safe_emalloc(4, Z_STRLEN_P(in_str), 1);

	for(p = Z_STRVAL_P(in_str), q = tmp_str; p != Z_STRVAL_P(in_str)+Z_STRLEN_P(in_str); p++) {
		c = *p;
		switch(c) {
			case '.':
			case '\\':
			case '+':
			case '*':
			case '?':
			case '[':
			case '^':
			case ']':
			case '$':
			case '(':
			case ')':
			case '{':
			case '}':
			case '=':
			case '!':
			case '>':
			case '<':
			case '|':
			case ':':
			case '/':
				*q++ = '\\';
				*q++ = c;
				break;

			case '\0':
				*q++ = '\\';
				*q++ = '0';
				*q++ = '0';
				*q++ = '0';
				break;

			default:
				*q++ = c;
				break;
		}
	}
	*q = '\0';
	ZVAL_STRING(out_str,tmp_str);
	efree(tmp_str);
	return Z_STRLEN_P(out_str);
}
