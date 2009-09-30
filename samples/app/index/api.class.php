<?php
/**
 * api samples code
 **/
class index_api{
	private $db;
	function __construct($zone="index"){
		$this->db = new index_db;
	}
	function addScore($username,$score){
		if(empty($username)){return -1;}
		if(empty($score)){return -2;}
		return $this->db->addScore($username,$score);
	}
}
?>