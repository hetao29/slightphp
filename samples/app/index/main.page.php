<?php
class index_main extends SGui{
	function __construct(){
		echo $this->render("head.tpl");
	}
	function __destruct(){
		echo $this->render("footer.tpl");
	}
	function pageEntry($inPath){
		echo $this->render("index/index.tpl");
	}
	function pageDb($inPath){
		$api = new index_api;
		$api->addScore("name",3);
	}
}
?>
