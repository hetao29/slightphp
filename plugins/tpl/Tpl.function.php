<?php
function tpl_function_tostring($mixed){
	return var_export($mixed,true);
}
function tpl_function_part($path){
	ob_start();
	return !empty($path)?SlightPHP::run($path):"";
}
function tpl_function_include($tpl){
	return \SlightPHP\Tpl::fetch($tpl);
}
