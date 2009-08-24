<?php
require_once("global.php");



$cap = new SCaptcha();

$code = $cap->CreateImage();
if(SCaptcha::check($code)){
	error_log(true);
}else{
	error_log(false);
}

?>
