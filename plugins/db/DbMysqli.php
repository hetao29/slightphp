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
	private $_stmt;
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
	public function __destruct(){
		if($this->_stmt){
			$this->_stmt->close();
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
		$this->_mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE,true);
		if(!empty($this->_charset)){
			$this->_mysqli->query("SET NAMES ".$this->_charset);
		}
		return true;
	}
	public function exec($sql){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->multi_query($sql);
	}
	public function query($sql, $params=[]){
		if($this->_mysqli->connect_errno){
			return false;
		}
		$this->_stmt = $this->_mysqli->prepare($sql);
		if($this->_stmt===false){
			return false;
		}
		$r = $this->_stmt->execute($params);
		if($r===false){
			return false;
		}
		$this->_result = $this->_stmt->get_result();
		return true;
	}
	public function getAll(){
		if(!$this->_result)return false;
		return $this->_result->fetch_all(MYSQLI_ASSOC);
	}
	public function count(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		if(!$this->_stmt)return false;
		return $this->_stmt->affected_rows;
	}
	public function escape($str){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->real_escape_string($str);
	}
	public function lastId(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		if(!$this->_stmt)return false;
		return $this->_stmt->insert_id;
	}
	public function error(){
		if($this->_mysqli->connect_error){
			return $this->_mysqli->connect_error;
		}
		if(!$this->_stmt)return false;
		return $this->_stmt->error;
	}
	public function begin(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->begin_transaction();
	}
	public function commit(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->commit();
	}
	public function rollback(){
		if($this->_mysqli->connect_errno){
			return false;
		}
		return $this->_mysqli->rollback();
	}
	public function errno(){
		$error=0;
		if($this->_mysqli->connect_errno){
			$error = $this->_mysqli->connect_errno;
		}else{
			if($this->_stmt){
				$error = $this->_stmt->errno;
			}
		}
		if($error=='2006'){
			$this->connectionError=true;
		}else{
			$this->connectionError=false;
		}
		return $error;
	}
}
