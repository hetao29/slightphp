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
		public $engine="mysql";
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
		public $host;
		/**
		 *
		 */
		public $port=3306;
		/**
		 *
		 */
		public $user;
		/**
		 *
		 */
		public $password;
		/**
		 *
		 */
		public $database;
		/**
		 *
		 */
		public $charset;
		/**
		 *
		 */
		public $count=true;
		/**
		 *
		 */
		public $limit=0;
		/**
		 *
		 */
		public $page=1;
		/**
		 *
		 */
		public $error=array('code'=>0,'msg'=>"");
		/**
		 * @var array $globals
		 */
		static $globals;
		function __construct($engine="mysql"){
				$this->__setEngine($engine);
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
		 * @param array params
		 * @param string p.host
		 * @param string p.user
		 * @param string p.password
		 * @param string p.database
		 * @param string p.charset
		 * @param string p.engine
		 * @param int p.port=3306
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
				if(!isset(Db::$globals[$this->_key])) Db::$globals[$this->_key] = "";
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
				$condiStr = $this->__quote($condition,"AND",$bind);

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
						//$orderby_sql = "ORDER BY ".$this->__array2string($orderby);
						if(is_array($orderby) || is_object($orderby)){
								$orderby_sql_tmp = array();
								foreach($orderby as $key=>$value){
										if(!is_numeric($key)){
												$orderby_sql_tmp[]="`".$key."` ".$value;
										}else{
												$orderby_sql_tmp[]="`".$value."`";
										}
								}
								if(count($orderby_sql_tmp)>0){
										$orderby_sql=" ORDER BY ".implode(",",$orderby_sql_tmp);
								}
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

				$result = $this->__query($sql,$bind);
				if($result){
						$data = new DbData;
						$data->page = $this->page;
						$data->limit = $this->limit;
						$data->items= $result;
						$data->pageSize = count($data->items);
						//{{{
						if($this->count==true){
								$countsql="SELECT count(1) totalSize FROM $table $join $condiStr $groupby";
								$result_count = $this->__query($countsql,$bind);
								$data->totalSize = $result_count[0]['totalSize'];
								if($this->limit>0){
										$data->totalPage = ceil($data->totalSize/$data->limit);
								}else{
										$data->totalPage = 1;
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
		public function update($table,$condition="",$item=""){
				$table = $this->__array2string($table);
				$value = $this->__quote($item,",",$bind_v);
				$condiStr = $this->__quote($condition,"AND",$bind_c);
				if($condiStr!=""){
						$condiStr=" WHERE ".$condiStr;
				}
				$sql="UPDATE $table SET $value $condiStr";
				return $this->__query($sql,$bind_v,$bind_c);
		}
		/**
		 * delete
		 *
		 * @param string|array|object $table
		 * @param string|array|object $condition
		 * @return int|boolean
		 */
		public function delete($table,$condition=""){
				$table = $this->__array2string($table);
				$condiStr = $this->__quote($condition,"AND",$bind);
				if($condiStr!=""){
						$condiStr=" WHERE ".$condiStr;
				}
				$sql="DELETE FROM  $table $condiStr";
				return $this->__query($sql,$bind);
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

				$f = $this->__quote($item,",",$bind_f);

				$sql="$command INTO $table SET $f ";
				$v = $this->__quote($update,",",$bind_v);
				if(!empty($v)){
						$sql.="ON DUPLICATE KEY UPDATE $v";
				}
				return $this->__query($sql,$bind_f,$bind_v);
		}

		/**
		 * query
		 *
		 * @param string $sql
		 * @return Array $result  || Boolean false
		 */

		private function __query($sql,$bind1=array(),$bind2=array()){
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
				if(empty(Db::$globals[$this->_key])){
						$this->__connect($forceReconnect=true);
				}
				if($this->engine=="mysql"){
						//BIND参数
						if($bind1){
								foreach($bind1 as $v){
										$sql = preg_replace("/\?/","\"".mysql_real_escape_string($v,Db::$globals[$this->_key])."\"",$sql,1);
								}
						}
						if($bind2){
								foreach($bind2 as $v){
										$sql = preg_replace("/\?/","\"".mysql_real_escape_string($v,Db::$globals[$this->_key])."\"",$sql,1);
								}
						}
						if(defined("DEBUG")){
								echo "SQL:$sql\n";
								print_r($bind1);
								print_r($bind2);
						}
						$result = mysql_query($sql,Db::$globals[$this->_key]);
						if(!$result){
								$this->error['code']=mysql_errno(Db::$globals[$this->_key]);
								$this->error['msg']=mysql_error(Db::$globals[$this->_key]);
						}elseif($sql_mode==2){//查询模式
								$data=array();
								while($row=mysql_fetch_array($result,MYSQL_ASSOC)){ $data[]=$row; }
								return $data;
						}elseif($sql_mode==3){//插入模式
								return mysql_insert_id(Db::$globals[$this->_key]);
						}else{
								return mysql_affected_rows(Db::$globals[$this->_key]);
						}

				}elseif($this->engine=="mysqli"){
						//BIND参数
						if($bind1){
								foreach($bind1 as $v){
										$sql = preg_replace("/\?/","\"".Db::$globals[$this->_key]->real_escape_string($v)."\"",$sql,1);
								}
						}
						if($bind2){
								foreach($bind2 as $v){
										$sql = preg_replace("/\?/","\"".Db::$globals[$this->_key]->real_escape_string($v)."\"",$sql,1);
								}
						}
						if(defined("DEBUG")){
								echo "SQL:$sql\n";
								print_r($bind1);
								print_r($bind2);
						}
						$result = Db::$globals[$this->_key]->query($sql);
						if(!$result){
								$this->error['code']=Db::$globals[$this->_key]->errno;
								$this->error['msg'] =Db::$globals[$this->_key]->error;
						}elseif($sql_mode==2){
								$data=array();
								while($row= $result->fetch_assoc()){$data[]=$row;};
								return $data;
						}elseif($sql_mode==3){//插入模式
								return Db::$globals[$this->_key]->insert_id;
						}else{
								return Db::$globals[$this->_key]->affected_rows;
						}
				}else{
						if(defined("DEBUG")){
								echo "SQL:$sql\n";
								print_r($bind1);
								print_r($bind2);
						}
						//PDO
						$stmt = Db::$globals[$this->_key]->prepare($sql);
						if(!$stmt){
								$this->error['code']=Db::$globals[$this->_key]->errorCode ();
								$this->error['msg']=Db::$globals[$this->_key]->errorInfo ();
						}
						if(!empty($bind1)){
								foreach($bind1 as $k=>$v){
										$stmt->bindValue($k,$v);
								}
						}
						if(!empty($bind2)){
								foreach($bind2 as $k=>$v){
										$stmt->bindValue($k + count($bind1),$v);
								}
						}
						if($stmt->execute ()){
								if($sql_mode==2){
										return $stmt->fetchAll (PDO::FETCH_ASSOC );
								}elseif($sql_mode==3){
										return Db::$globals[$this->_key]->lastInsertId();
								}else{
										return $stmt->rowCount();
								}
						}else{
								$this->error['code']=$stmt->errorCode ();
								$this->error['msg']=$stmt->errorInfo ();
						}
				}
				if(defined("DEBUG")){
						print_r($this->error);
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
				if(empty(Db::$globals[$this->_key]) || $forceReconnect){
						if(!empty(Db::$globals[$this->_key])){
								unset(Db::$globals[$this->_key]);
						}
						if($this->engine=="mysql"){
								Db::$globals[$this->_key] = mysql_connect($this->host.":".$this->port,$this->user,$this->password,true);
								if(!Db::$globals[$this->_key]){
										if(defined("DEBUG")){
												die("connect database error:\n".mysql_error(Db::$globals[$this->_key]));
										}else{
												die("connect database error:");
										}
								}
								if($this->database!=""){
										mysql_select_db($this->database,Db::$globals[$this->_key]);
								}
						}elseif($this->engine=="mysqli"){
								Db::$globals[$this->_key] = new mysqli($this->host,$this->user,$this->password,$this->database,$this->port);
								if(Db::$globals[$this->_key]->connect_errno) {
										if(defined("DEBUG")){
												die("connect database error:\n".Db::$globals[$this->_key]->connect_error);
										}else{
												die("connect database error:");
										}
								}
						}else{
								$tmp = explode("_",$this->engine);
								$driver =$tmp[1];
								try{
										Db::$globals[$this->_key] = new PDO($driver .":dbname=".$this->database.";host=".$this->host.";port=".$this->port,$this->user,$this->password);
								}catch(Exception $e){
										if(defined("DEBUG")){
												die("connect database error:\n".var_export($e,true));
										}else{
												die("connect database error:");
										}
								}
						}
				}
				if(!empty($this->charset)){
						$this->execute("SET NAMES ".$this->charset);
				}
		}
		private function __quote($condition,$split="AND",&$bind){
				$condiStr = "";
				if(!is_array($bind)){$bind=array();}
				if(is_array($condition) || is_object($condition)){
						$v1=array();
						$i=1;
						foreach($condition as $k=>$v){
								if(!is_numeric($k)){
										if(strpos($k,".")>0){
												$v1[]="`$k` = ?";
										}else{
												$v1[]="`$k` = ?";
										}
										$bind[$i++]=$v;
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
		private function __array2string($mixed,$alais=false){
				$r="";
				if(is_array($mixed) || is_object($mixed)){
						$tmp=array();
						foreach($mixed as $k=>$t){
								if($t!="*"){
										if(!is_numeric($k) && $alais){
												$tmp[]="`" . ($t). "` AS `". $k ."`";
										}else{
												$tmp[]="`" . ($t) . "`";
										}
								}else{
										$tmp[]="*";
								}
						}
						$r=implode(" , ",$tmp);
				}else{
						if($mixed!="*")$r="`".($mixed)."`";else $r="*";
				}
				return $r;
		}
}
?>
