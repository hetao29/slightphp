<?php
class index_db{
	public function pageTest($inPath){
		$db = new index_db_user;
		if($db->init()){
			echo "create table ok<br />\n";
		}else{
			echo "table exists<br />\n";
		};
		$id = $db->add();
		echo "add data , id $id<br />\n";
		$data=$db->get($id);
		print_r($data);
		$data=$db->getV2($id);
		print_r($data);
		$data=$db->getAll();
		echo "all data :<br />\n";
		print_r($data);
	}
}
