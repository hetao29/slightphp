<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/

/**
 * @package SlightPHP
 * @subpackage SDb
 */
namespace SlightPHP;
require_once(SLIGHTPHP_PLUGINS_DIR."/db/DbEngine.php");
class DbMysqli implements DbEngine{
	private $_mysqli;
	private $_result;

	private $_engine;

	private $_host="localhost";
	private $_port="3306";
	private $_user;
	private $_password;
	private $_database;

	private $_persistent;
	private $_charset;
	public $connectionError=false;
	/**
	 * construct
	 *
	 * @param array $params
	 * @param string $params.host
	 * @param string $params.user
	 * @param string $params.password
	 * @param string $params.database
	 * @param string $params.charset
	 * @param string $params.engine
	 * @param bool $params.persistent 
	 * @param int $param.port=3306
	 */
	public function init($params=array()){
		foreach($params as $key=>$value){
			$this->{"_".$key} = $value;
		}
	}
	public function connect(){
		$host = $this->_host;
		if($this->_persistent){
			$host="p:".$this->_host;
		}
		$this->_mysqli = new \mysqli($host,$this->_user,$this->_password,$this->_database,$this->_port);
		if($this->_mysqli->connect_errno){
			return false;
		}
		if(!empty($this->_charset)){
			$this->_mysqli->query("SET NAMES ".$this->_charset);
		}
		return true;
	}
	public function query($sql){
		if($this->_mysqli->connect_errno){
			return false;
		}
		$this->_result= $this->_mysqli->query($sql);
		if($this->_result){
			return true;
		}
		return false;
	}
	public function getAll(){
		if(!$this->_result)return false;
		$data=array();
		while($row= $this->_result->fetch_assoc()){$data[]=$row;};
		return $data;
	}
	public function count(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->affected_rows;
	}
	public function lastId(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->insert_id;
	}
	public function error(){
		if($this->_mysqli->connect_error){
			return $this->_mysqli->connect_error;
		}
		return $this->_mysqli->error;
	}
	public function errno(){
		$error=0;
		if($this->_mysqli->connect_errno){
			$error = $this->_mysqli->connect_errno;
		}else{
			$error = $this->_mysqli->errno;
		}
		if($error=='2006'){
			$this->connectionError=true;
		}else{
			$this->connectionError=false;
		}
		return $error;
	}
}
