<?php
/**
 * 这个例子演示如何使用json
 */
class index_json{
	function pageEntry($inPath){
		$sm = new SJson;
		$testObject = new stdclass;
		$testObject->name="SlightPHP";
		$testObject->value=array("min"=>1,"max"=>999);
		/**
		 * 编码
		 */
		print_r($tmp = $sm->encode($testObject));
		echo "\n";
		/**
		 * 解码
		 */
		print_r($sm->decode($tmp));	

	}
}

?>
