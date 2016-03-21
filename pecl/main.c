#include <main/php_version.h>
#if PHP_MAJOR_VERSION == 5
#include "slightphp.c"
#else
#include "slightphp7.c"
#endif
