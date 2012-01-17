<?php
class index_rest extends SRest{
	function postEntry($inPath){
		echo __LINE__;
		return "X";
	}
	function getEntry($inPath){
		print_r($inPath);
		echo __LINE__;
		return "X";
	}
}
