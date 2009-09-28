<?php
class index_smarty extends SGui{
	function pageEntry($inPath){
		$params['key']="value";
		$params['date'] = date("Y-m-d H:i:s");
		return $this->render("index/smarty.test.tpl",$params);
	}
	function pagePart($inPath){
		echo "PART";
		$params['key']="value";
		$params['date'] = date("Y-m-d H:i:s");
		return $this->render("index/smarty.test.part.tpl",$params);
	}
}
?>
