<?php
class SlightPHPLog{
	public static function write($str){
		$tmp = "[".date("Y-m-d H:i:s")."] ".$str."\n";
		if(defined("FRAMEWORK_DEBUG_DISPLAY")){
			echo $tmp;
		}
		error_log($tmp,3,FRAMEWORK_LOG);
	}
}
?>