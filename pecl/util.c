int debug(char*format,... TSRMLS_DC){
	TSRMLS_FETCH();
	int cc=0;
	zval *_debug_flag = zend_read_static_property(slightphp_ce_ptr,"_debug",sizeof("_debug")-1,1 TSRMLS_CC);
	convert_to_long(_debug_flag);
	if(Z_LVAL_P(_debug_flag))
	{
		va_list args;
		char *buffer;
		TSRMLS_FETCH();

		va_start(args, format);
		cc = vspprintf(&buffer, 0, format, args);
		_php_error_log(0,buffer,NULL,NULL TSRMLS_CC);
		zend_printf("<!--slightphp debug:%s-->",buffer);
		efree(buffer);
		va_end(args);
	}
	return cc;
}
int slightphp_load(zval*appDir,zval*zone,zval*class_name TSRMLS_DC){
	int ret = FAILURE;
	char*inc_filename;
	spprintf(&inc_filename,0,"%s%c%s%c%s.page.php",
			Z_STRVAL_P(appDir),
			DEFAULT_SLASH,
			Z_STRVAL_P(zone),
			DEFAULT_SLASH,
			Z_STRVAL_P(class_name)
			);
	zval file_name;
	S_ZVAL_STRING(&file_name,inc_filename);
	efree(inc_filename);

	char resolved_path_buff[MAXPATHLEN];
	if (VCWD_REALPATH(Z_STRVAL(file_name), resolved_path_buff)) {
		ret = slightphp_loadFile((char*)&resolved_path_buff TSRMLS_CC);
	}else{
		debug("file[%s] not exists",Z_STRVAL(file_name));
	}
	zval_dtor(&file_name);
	return ret;
}

int preg_quote(zval *in_str,zval*out_str TSRMLS_DC){
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
	S_ZVAL_STRING(out_str,tmp_str);
	efree(tmp_str);
	return Z_STRLEN_P(out_str);
}
