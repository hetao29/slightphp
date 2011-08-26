<?php
$br = (php_sapi_name() == "cli")? "":"<br>";

if(!extension_loaded('slightphp')) {
	dl('slightphp.' . PHP_SHLIB_SUFFIX);
}
var_dump(new slightphp);
