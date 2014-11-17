<?php
class index_rest extends SRestServer{
	function __construct(){
	}
	function __destruct(){
	}
	function getEntry($inPath){
		echo "Get Test";
	}
	function postEntry($inPath){
		echo "Post Test";
	}
	function putEntry($inPath){
		echo "Put Test";
	}
	function headEntry($inPath){
		echo "Head Test";
	}
	function DeleteEntry($inPath){
		echo "Delete Test";
	}
	function OptionsEntry($inPath){
		echo "Options Test";
	}
}
?>
