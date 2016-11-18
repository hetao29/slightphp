<?php
/**
 * db samples code
 **/
class index_db_user{
	private $_db;
	function __construct($zone="index"){
		$this->_db = new SDb;
		$this->_db->useConfig($zone,"main");
	}
	function init(){
		$sql="CREATE TABLE IF NOT EXISTS `test` ( `id` int not null primary key auto_increment, `name` varchar(300) default NULL, `password` varchar(300) default NULL, KEY `name` (`name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		return $this->_db->execute($sql);
	}
	function add(){
		return $this->_db->insert($table = "test",$items=array("name"=>"testName","password"=>"testPassword".date("Y-m-d H:i:s")));
	}
	function get($id){
		return $this->_db->selectOne($table = "test",$condition=array("id"=>$id),$items=array("id","name","password"));
	}
	/**
	 * ORM
	 */
	function getV2($id){
		$this->_db->setTable("test");//设置表名
		$this->_db->id=$id;//设置条件
		return $this->_db->get();//获取数据
	}
	function getAll(){
		return $this->_db->select($table = "test",$condition=array(),$items=array("id","name","password"));
	}
}
