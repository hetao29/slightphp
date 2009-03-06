<?php
class zone_smarty{
	function pageEntry($inPath){
		$a = new SSmarty;
		$a->template_dir = "zone/tpl";
		$a->compile_dir = "zone/tpl";

		$a->assign("key","value");
		$a->assign("date",date("Y-m-d H:i:s"));
		$a->display("smarty.test.tpl");
	}
}
?>
