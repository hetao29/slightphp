<?php
class zone_default extends SlightPHP{
	function pageEntry($inPath){
		print_r($this);
		print_r($inPath);
		print_r($_GET);
		$a = new Smarty;
		print_r($a);
	}
}
?>
