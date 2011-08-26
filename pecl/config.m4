dnl $Id$
dnl config.m4 for extension slightphp

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

dnl If your extension references something external, use with:

PHP_ARG_WITH(slightphp, for slightphp support,
[  --with-slightphp             Include slightphp support])

dnl Otherwise use enable:

PHP_ARG_ENABLE(slightphp, whether to enable slightphp support,
[  --enable-slightphp           Enable slightphp support])

if test "$PHP_SLIGHTPHP" != "no"; then
  dnl Write more examples of tests here...

  dnl # --with-slightphp -> check with-path
  dnl SEARCH_PATH="/usr/local /usr"     # you might want to change this
  dnl SEARCH_FOR="/include/slightphp.h"  # you most likely want to change this
  dnl if test -r $PHP_SLIGHTPHP/$SEARCH_FOR; then # path given as parameter
  dnl   SLIGHTPHP_DIR=$PHP_SLIGHTPHP
  dnl else # search default path list
  dnl   AC_MSG_CHECKING([for slightphp files in default path])
  dnl   for i in $SEARCH_PATH ; do
  dnl     if test -r $i/$SEARCH_FOR; then
  dnl       SLIGHTPHP_DIR=$i
  dnl       AC_MSG_RESULT(found in $i)
  dnl     fi
  dnl   done
  dnl fi
  dnl
  dnl if test -z "$SLIGHTPHP_DIR"; then
  dnl   AC_MSG_RESULT([not found])
  dnl   AC_MSG_ERROR([Please reinstall the slightphp distribution])
  dnl fi

  dnl # --with-slightphp -> add include path
  dnl PHP_ADD_INCLUDE($SLIGHTPHP_DIR/include)

  dnl # --with-slightphp -> check for lib and symbol presence
  dnl LIBNAME=slightphp # you may want to change this
  dnl LIBSYMBOL=slightphp # you most likely want to change this 

  dnl PHP_CHECK_LIBRARY($LIBNAME,$LIBSYMBOL,
  dnl [
  dnl   PHP_ADD_LIBRARY_WITH_PATH($LIBNAME, $SLIGHTPHP_DIR/lib, SLIGHTPHP_SHARED_LIBADD)
  dnl   AC_DEFINE(HAVE_SLIGHTPHPLIB,1,[ ])
  dnl ],[
  dnl   AC_MSG_ERROR([wrong slightphp lib version or lib not found])
  dnl ],[
  dnl   -L$SLIGHTPHP_DIR/lib -lm -ldl
  dnl ])
  dnl
  dnl PHP_SUBST(SLIGHTPHP_SHARED_LIBADD)

  PHP_NEW_EXTENSION(slightphp, slightphp.c, $ext_shared)
fi
