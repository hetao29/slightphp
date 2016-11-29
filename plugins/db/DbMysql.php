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
class DbMysql implements DbEngine{
	private $_mysql;
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
		if($this->_persistent){
			$this->_mysql = mysql_pconnect($this->_host.":".$this->_port,$this->_user,$this->_password);
		}else{
			$this->_mysql = mysql_connect($this->_host.":".$this->_port,$this->_user,$this->_password,true);
		}
		if(!$this->_mysql){
			return false;
		}
		if($this->_database!=""){
			mysql_select_db($this->_database,$this->_mysql);
		}
		if($this->_charset){
			mysql_query("SET NAMES ".$this->_charset,$this->_mysql);
		}
		return true;
	}
	public function query($sql){
			return $this->_result=mysql_query($sql,$this->_mysql);
	}
	public function getAll(){
		$data=array();
		while($row=mysql_fetch_array($this->_result,MYSQL_ASSOC)){ $data[]=$row; }
		return $data;
	}
	public function count(){
		return mysql_affected_rows($this->_mysql);
	}
	public function lastId(){
		return mysql_insert_id($this->_mysql);
	}
	public function error(){
		return mysql_error($this->_mysql);
	}
	public function errno(){
		$error = mysql_errno($this->_mysql);
		if($error=='2006'){
			$this->connectionError=true;
		}else{
			$this->connectionError=false;
		}
		return $error;
	}
}
