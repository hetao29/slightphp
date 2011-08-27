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
		zval *_debug_flag = zend_read_static_property(slightphp_ce_ptr,"_debug",sizeof("_debug")-1,1 TSRMLS_CC);
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
		ZVAL_STRING(&file_name,inc_filename,1);
		efree(inc_filename);

		char resolved_path_buff[MAXPATHLEN];
		if (VCWD_REALPATH(Z_STRVAL(file_name), resolved_path_buff)) {
				ret = slightphp_loadFile((char*)&resolved_path_buff TSRMLS_CC);
				return ret;
		}else{
				debug("file[%s] not exists",Z_STRVAL(file_name));
		}
		return FAILURE;
}
int slightphp_loadFile(char*file_name TSRMLS_DC){
		int dummy = 1;
		zend_file_handle file_handle;
		int ret;

		if(zend_stream_open(file_name, &file_handle TSRMLS_CC)==SUCCESS){;
				if (!file_handle.opened_path) {
						file_handle.opened_path = estrndup(file_name, strlen(file_name));
				}
				if(file_handle.opened_path) {
						if(zend_hash_exists(&EG(included_files),file_handle.opened_path, strlen(file_handle.opened_path)+1)){
								return SUCCESS;
						}else{
								return zend_execute_scripts(ZEND_REQUIRE_ONCE TSRMLS_CC, NULL, 1, &file_handle);
						}
				}
		}
		return FAILURE;
}
int slightphp_run(zval*zone,zval*class_name,zval*method,zval*return_value, int param_count,zval * params[] TSRMLS_DC){
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
		if(zend_hash_find(EG(class_table),Z_STRVAL(real_classname_zval),Z_STRLEN(real_classname_zval)+1,(void **)&pce)==FAILURE){
				debug("class[%s] not exists",Z_STRVAL(real_classname_zval));
				return FAILURE;
		} else {

				ce = *pce;
				MAKE_STD_ZVAL(object);
				object_init_ex(object,ce);
				zval c_ret;
				INIT_ZVAL(c_ret);
        		if (ce->constructor) {
						zval tmp_method;
        		        ZVAL_STRING(&tmp_method, ce->constructor->common.function_name, 1);
        		        if(call_user_function(NULL, &object, &tmp_method, &c_ret, 0, NULL TSRMLS_CC)!=SUCCESS){
								php_error_docref(NULL TSRMLS_CC, E_ERROR, "Error calling constructor");
        		    	}           
						zval_dtor(&tmp_method);
        		}   
				zval_dtor(&c_ret);
				int r=call_user_function(NULL, &object, &real_method_zval, return_value, param_count, params TSRMLS_CC);
				zval_ptr_dtor(&object);
				if(r!=SUCCESS){
						debug("method[%s] not exists in class[%s]",Z_STRVAL(real_method_zval),Z_STRVAL(real_classname_zval));
						return FAILURE;
				}
		}
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
		ZVAL_STRING(out_str,tmp_str,1);
		efree(tmp_str);
		return Z_STRLEN_P(out_str);
}
