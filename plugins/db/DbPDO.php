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
class DbPDO implements DbEngine{
	private $_pdo;
	private $_stmt;

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

		/**
		 * fix pdo bug
		 * https://bugs.php.net/bug.php?id=73475
		 */
		if($this->_persistent && $this->_password == ""){$this->_password=null;}
	}
	public function connect(){
		$tmp = explode("_",$this->_engine);
		$driver =$tmp[1];
		try{
			$options = array(
				\PDO::ATTR_PERSISTENT => $this->_persistent,
				\PDO::ATTR_STRINGIFY_FETCHES => false,
				\PDO::ATTR_EMULATE_PREPARES => false,
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_WARNING,
				\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
			);
			if(!empty($this->_charset)){
				$options[\PDO::MYSQL_ATTR_INIT_COMMAND]="SET CHARACTER SET {$this->_charset}, NAMES {$this->_charset}";
			}
			$this->_pdo = new \PDO(
				$driver .":dbname=".$this->_database.";host=".$this->_host.";port=".$this->_port,
				$this->_user,
				$this->_password,
				$options,
			);
		}catch(\PDOException  $e){
			trigger_error("CONNECT DATABASE ERROR ( ".$e->getMessage()." ) ",E_USER_WARNING);
			return false;
		}
		return true;
	}
	public function exec($sql){
		if(!$this->_pdo)return false;
		return $this->_pdo->exec($sql);
	}
	public function query($sql,$params=[]){
		if(!$this->_pdo)return false;
		$this->_stmt = $this->_pdo->prepare($sql);
		if($this->_stmt){
			return $this->_stmt->execute($params);
		}else{
			trigger_error("CONNECT DATABASE ERROR ( ".implode("\n",$this->_pdo->errorInfo())." ) ",E_USER_WARNING);
		}
		return false;
	}
	public function getAll(){
		if(!$this->_stmt)return false;
		return $this->_stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
	public function count(){
		if(!$this->_stmt)return false;
		return $this->_stmt->rowCount();
	}
	public function begin(){
		if(!$this->_pdo)return false;
		return $this->_pdo->beginTransaction();
	}
	public function commit(){
		if(!$this->_pdo)return false;
		return $this->_pdo->commit();
	}
	public function rollback(){
		if(!$this->_pdo)return false;
		return $this->_pdo->rollBack();
	}
	public function escape($str){
		if(!$this->_pdo)return false;
		$str = $this->_pdo->quote($str);
		return substr($str,1,strlen($str)-2);
	}
	public function lastId(){
		if(!$this->_pdo)return false;
		return $this->_pdo->lastInsertId();
	}
	public function error(){
		if(!$this->_pdo)return false;
		if($this->_stmt){
			return $this->_stmt->errorInfo();
		}
		return $this->_pdo->errorInfo();
	}
	public function errno(){
		if(!$this->_pdo)return false;
		if($this->_stmt){
			$error = $this->_stmt->errorCode();
		}else{
			$error = $this->_pdo->errorCode();
		}
		if($error=='HY000'){
			$this->connectionError=true;
		}else{
			$this->connectionError=false;
		}
		return $error;
	}
}
