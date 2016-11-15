#include <main/php_version.h>
#if PHP_MAJOR_VERSION == 5
#include "php5/slightphp.c"
#else
#define TSRMLS_CC
#include "php7/slightphp.c"
#endif
