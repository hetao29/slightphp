<?php
class index_main{
	function pageEntry($inPath){
		echo "Hello, SlightPHP";
	}
	function pageDb($inPath){
		$api = new index_api;
		$api->addScore("name",3);
	}
}
?>
