#include <main/php_version.h>
#if PHP_MAJOR_VERSION == 5
#define S_ZVAL_STRING(a,b) ZVAL_STRING(a,b,1)

#include "php5/slightphp.c"

#else
#define TSRMLS_CC
#define TSRMLS_DC
#define S_ZVAL_STRING(a,b) ZVAL_STRING(a,b)

#include "php7/slightphp.c"
#endif
#include "util.c"
