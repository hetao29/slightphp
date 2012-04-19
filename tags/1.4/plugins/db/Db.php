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
class Db{
		/**
		 * 
		 */
		private $engine="mysql";
		private $_allow_engines=array(
				"mysql","mysqli",
				"pdo_mysql","pdo_sqlite","pdo_cubrid",
				"pdo_dblib","pdo_firebird","pdo_ibm",
				"pdo_informix","pdo_sqlsrv","pdo_oci",
				"pdo_odbc","pdo_pgsql","pdo_4d"
		);
		private $_key;

		/**
		 *
		 */
		private $host;
		/**
		 *
		 */
		private $port=3306;
		/**
		 *
		 */
		private $user;
		/**
		 *
		 */
		private $password;
		/**
		 *
		 */
		private $database;
		/**
		 *
		 */
		private $charset;
		/**
		 *
		 */
		private $count=true;
		/**
		 *
		 */
		private $limit=0;
		/**
		 *
		 */
		private $page=1;
		/**
		 *
		 */
		private $error=array('code'=>0,'msg'=>"");
		/**
		 * @var array $_globals
		 */
		static $_globals;
		function __construct($engine="mysql"){
				$this->__setEngine($engine);
		}
		public function error(){
				return $this->error;
		}
		private function __setEngine($engine){
				if(in_array($engine,$this->_allow_engines)){
						$this->engine=$engine;
				}else{
						die("Db engine: $engine does not support!");
				}
		}
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
		 * @param int $param.port=3306
		 */
		public function init($params=array()){
				foreach($params as $key=>$value){
						if(in_array($key,array("host","user","password","port","database","charset"))){
								$this->$key = $value;
						}elseif(in_array($key,array("engine"))){
								$this->__setEngine($value);
						}
				}
				$this->_key = $this->engine.":".$this->host.":".$this->user.":".$this->password.":".$this->database.":".$this->port;
				if(!isset(Db::$_globals[$this->_key])) Db::$_globals[$this->_key] = "";
		}
		/**
		 * is count 
		 *
		 * @param boolean count
		 */
		public function setCount($count){
				if($count==true){
						$this->count=true;
				}else{
						$this->count=false;
				}
		}
		/**
		 * page number
		 *
		 * @param int page 
		 */
		public function setPage($page){
				if(!is_numeric($page) || $page<1){$page=1;}
				$this->page=$page;
		}
		/**
		 * page size
		 *
		 * @param int limit ,0 is all
		 */
		public function setLimit($limit){
				if(!is_numeric($limit) || $limit<0){$limit=0;}
				$this->limit=$limit;
		}
		/**
		 * select data from db
		 *
		 * @param string|array|object $table 
		 * @param string|array|object $condition
		 * @param string|array|object $item 
		 * @param string|array|object $groupby 
		 * @param string|array|object $orderby
		 * @param string|array|object $leftjoin
		 * @return DbData object || Boolean false
		 */
		public function select($table,$condition="",$item="",$groupby="",$orderby="",$leftjoin=""){
				//TABLE
				$table = $this->__array2string($table,true);
				//condition
				$condiStr = $this->__quote($condition,"AND");

				if($condiStr!=""){
						$condiStr=" WHERE ".$condiStr;
				}
				//ITEM
				if(empty($item)){
						$item="*";
				}else{
						$item  = $this->__array2string($item,true);
				}
				//GROUPBY
				if(!empty($groupby)){
						$groupby = "GROUP BY ".$this->__array2string($groupby);
				}
				//LEFTJOIN
				$join="";
				if(!empty($leftjoin)){
						if(is_array($leftjoin) || is_object($leftjoin)){
								foreach ($leftjoin as $key=>$value){
										$join.=" LEFT JOIN $key ON $value ";
								}
						}else{
								$join=" LEFT JOIN $leftjoin";
						}
				}
				//{{{ ORDERBY
				$orderby_sql="";
				if(!empty($orderby )){
						if(is_array($orderby) || is_object($orderby)){
								$orderby_sql_tmp = array();
								foreach($orderby as $key=>$value){
										if(!is_numeric($key)){
												$orderby_sql_tmp[]=$this->__addsqlslashes($key) ." ". $value;
										}else{
												$orderby_sql_tmp[]=$this->__addsqlslashes($value);
										}
								}
								if(count($orderby_sql_tmp)>0){
										$orderby_sql=" ORDER BY ".implode(",",$orderby_sql_tmp);
								}
						}else{
							$orderby_sql=" ORDER BY $orderby";
						}
				}

				/*
				 */
				//}}}

				$limit_sql = "";
				if($this->limit>0){
						$limit    =($this->page-1)*$this->limit;
						$limit_sql ="LIMIT $limit,$this->limit";
				}
				$sql="SELECT $item FROM $table $join $condiStr $groupby $orderby_sql $limit_sql";
				$start = microtime(true);

				$result = $this->__query($sql);
				if($result!==false){
						$data = new DbData;
						$data->page = $this->page;
						$data->limit = $this->limit;
						$data->items= $result;
						$data->pageSize = count($data->items);
						//{{{
						if($this->count==true){
								$countsql="SELECT count(1) totalSize FROM $table $join $condiStr $groupby";
								$result_count = $this->__query($countsql);
								if(!empty($result_count[0])){
									$data->totalSize = $result_count[0]['totalSize'];
									if($this->limit>0){
										$data->totalPage = ceil($data->totalSize/$data->limit);
									}else{
										$data->totalPage = 1;
									}
								}
						}
						//}}}
						$end = microtime(true);
						$data->totalSecond = $end-$start;
						$result = $data;
				}
				//{{{reset 
				$this->setPage(1);
				$this->setLimit(0);
				$this->setCount(false);
				//}}}
				return $result;
		}
		/**
		 * select one from select result 
		 *
		 */
		public function selectOne($table,$condition="",$item="",$groupby="",$orderby="",$leftjoin=""){
				$this->setLimit(1);
				$this->setCount(false);
				$data=$this->select($table,$condition,$item,$groupby,$orderby,$leftjoin);
				if(isset($data->items[0]))
						return $data->items[0];
				else return false;
		}

		/**
		 * update data
		 *
		 * @param string|array|object $table
		 * @param string|array|object $condition
		 * @param string|array|object $item
		 * @return int|boolean
		 */
		public function update($table,$condition,$item){
				$table = $this->__array2string($table);
				$value = $this->__quote($item,",");
				$condiStr = $this->__quote($condition,"AND");
				if($condiStr!=""){
						$condiStr=" WHERE ".$condiStr;
				}
				$sql="UPDATE $table SET $value $condiStr";
				return $this->__query($sql);
		}
		/**
		 * delete
		 *
		 * @param string|array|object $table
		 * @param string|array|object $condition
		 * @return int|boolean
		 */
		public function delete($table,$condition){
				$table = $this->__array2string($table);
				$condiStr = $this->__quote($condition,"AND");
				if($condiStr!=""){
						$condiStr=" WHERE ".$condiStr;
				}
				$sql="DELETE FROM  $table $condiStr";
				return $this->__query($sql);
		}
		/**
		 * insert
		 * 
		 * @param string|array|object $table
		 * @param string|array|object $item 
		 * @param boolean $isreplace
		 * @param boolean $isdelayed
		 * @param string|array|object $update
		 * @return int|boolean int(lastInsertId or affectedRows)
		 */
		public function insert($table,$item="",$isreplace=false,$isdelayed=false,$update=array()){
				$table = $this->__array2string($table);
				if($isreplace==true){
						$command="REPLACE";
				}else{
						$command="INSERT";
				}
				if($isdelayed==true){
						$command.=" DELAYED ";
				}

				$f = $this->__quote($item,",");

				$sql="$command INTO $table SET $f ";
				$v = $this->__quote($update,",");
				if(!empty($v)){
						$sql.="ON DUPLICATE KEY UPDATE $v";
				}
				return $this->__query($sql);
		}

		/**
		 * query
		 *
		 * @param string $sql
		 * @return Array $result  || Boolean false
		 */

		private function __query($sql){
				//{{{
				//SQL MODE 默认为DELETE，INSERT，REPLACE 或 UPDATE,不需要返回值
				$sql_mode = 1;//1.更新模式 2.查询模式 3.插入模式

				if(stripos($sql,"INSERT")!==false){
						$sql_mode = 3;
				}else{
						$sql_result_query=array("SELECT","SHOW","DESCRIBE","EXPLAIN");
						foreach($sql_result_query as $query_type){
								if(stripos($sql,$query_type)!==false){
										$sql_mode = 2;
										break;
								}
						}
				}
				//}}}
				if(empty(Db::$_globals[$this->_key])){
						$this->__connect($forceReconnect=true);
				}
				if(defined("DEBUG")){
					trigger_error("{$this->engine} ( $sql )");
				}
				if($this->engine=="mysql"){
						$result = mysql_query($sql,Db::$_globals[$this->_key]);
						if(!$result){
								$this->error['code']=mysql_errno(Db::$_globals[$this->_key]);
								$this->error['msg']=mysql_error(Db::$_globals[$this->_key]);
						}elseif($sql_mode==2){//查询模式
								$data=array();
								while($row=mysql_fetch_array($result,MYSQL_ASSOC)){ $data[]=$row; }
								return $data;
						}elseif($sql_mode==3){//插入模式
								return mysql_insert_id(Db::$_globals[$this->_key]);
						}else{
								return mysql_affected_rows(Db::$_globals[$this->_key]);
						}

				}elseif($this->engine=="mysqli"){
						$result = Db::$_globals[$this->_key]->query($sql);
						if(!$result){
								$this->error['code']=Db::$_globals[$this->_key]->errno;
								$this->error['msg'] =Db::$_globals[$this->_key]->error;
						}elseif($sql_mode==2){
								$data=array();
								while($row= $result->fetch_assoc()){$data[]=$row;};
								return $data;
						}elseif($sql_mode==3){//插入模式
								return Db::$_globals[$this->_key]->insert_id;
						}else{
								return Db::$_globals[$this->_key]->affected_rows;
						}
				}else{
						$stmt = Db::$_globals[$this->_key]->prepare($sql);
						if(!$stmt){
								$this->error['code']=Db::$_globals[$this->_key]->errorCode ();
								$this->error['msg']=Db::$_globals[$this->_key]->errorInfo ();
						}
						if($stmt->execute ()){
								if($sql_mode==2){
										return $stmt->fetchAll (PDO::FETCH_ASSOC );
								}elseif($sql_mode==3){
										return Db::$_globals[$this->_key]->lastInsertId();
								}else{
										return $stmt->rowCount();
								}
						}else{
								$this->error['code']=$stmt->errorCode ();
								$this->error['msg']=$stmt->errorInfo ();
						}
				}
				if(defined("DEBUG")){
					trigger_error("DB ERROR!!! ( ".var_export($this->error['msg'],true)." ), CODE( {$this->error['code']} )",E_USER_WARNING);
				}
				return false;
		}
		/**
		 *
		 * @param string $sql
		 * @return boolean|int|array
		 */
		public function execute($sql){
				return $this->__query($sql);
		}

		private function __connect($forceReconnect=false){
				if(empty(Db::$_globals[$this->_key]) || $forceReconnect){
						if(!empty(Db::$_globals[$this->_key])){
								unset(Db::$_globals[$this->_key]);
						}
						if($this->engine=="mysql"){
								Db::$_globals[$this->_key] = mysql_connect($this->host.":".$this->port,$this->user,$this->password,true);
								if(!Db::$_globals[$this->_key]){
										if(defined("DEBUG")){
												trigger_error("CONNECT DATABASE ERROR ( ".mysql_error()." ) ",E_USER_WARNING);
										}else{
												trigger_error("CONNECT DATABASE ERROR!!!",E_USER_WARNING);
										}
										return false;
								}
								if($this->database!=""){
										mysql_select_db($this->database,Db::$_globals[$this->_key]);
								}
						}elseif($this->engine=="mysqli"){
								Db::$_globals[$this->_key] = new mysqli($this->host,$this->user,$this->password,$this->database,$this->port);
								if(Db::$_globals[$this->_key]->connect_errno) {
										if(defined("DEBUG")){
												trigger_error("CONNECT DATABASE ERROR ( ".Db::$_globals[$this->_key]->connect_error." ) ",E_USER_WARNING);
										}else{
												trigger_error("CONNECT DATABASE ERROR!!!",E_USER_WARNING);
										}
										return false;
								}
						}else{
								$tmp = explode("_",$this->engine);
								$driver =$tmp[1];
								try{
										Db::$_globals[$this->_key] = new PDO($driver .":dbname=".$this->database.";host=".$this->host.";port=".$this->port,$this->user,$this->password);
								}catch(Exception $e){
										if(defined("DEBUG")){
												trigger_error("CONNECT DATABASE ERROR ( ".$e->getMessage()." ) ",E_USER_WARNING);
										}else{
												trigger_error("CONNECT DATABASE ERROR!!!",E_USER_WARNING);
										}
										return false;
								}
						}
				}
				if(!empty($this->charset)){
						$this->execute("SET NAMES ".$this->charset);
				}
		}
		private function __quote($condition,$split="AND"){
				$condiStr = "";
				if(is_array($condition) || is_object($condition)){
						$v1=array();
						$i=1;
						foreach($condition as $k=>$v){
								if(!is_numeric($k)){
									if(strpos($k,".")===false){
										$k = $this->__addsqlslashes($k);
									}
									$v = addslashes($v);
									$v1[]="$k = \"$v\"";
								}else{
									$v1[]=($v);
								}
						}
						if(count($v1)>0){
								$condiStr=implode(" ".$split." ",$v1);

						}
				}else{
						$condiStr=$condition;
				}
				return $condiStr;
		}
		private function __addsqlslashes($k){
			if(strpos($k,"(")!==false || strpos($k,")")!==false || strpos($k,".")!==false){
				return $k;
			}else{
				return "`$k`";
			}
		}
		private function __array2string($mixed,$alais=false){
				$r="";
				if(is_array($mixed) || is_object($mixed)){
						$tmp=array();
						foreach($mixed as $k=>$t){
								if($t!="*"){
										if(!is_numeric($k) && $alais){
												$tmp[]=$this->__addsqlslashes($t)."  ".$this->__addsqlslashes($k);
										}else{
												$tmp[]=$this->__addsqlslashes($t);
										}
								}else{
										$tmp[]="*";
								}
						}
						$r=implode(" , ",$tmp);
				}else{
						if($mixed!="*")$r=$this->__addsqlslashes($mixed);else $r="*";
				}
				return $r;
		}
}
?>
