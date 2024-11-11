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
require_once(SLIGHTPHP_PLUGINS_DIR."/db/DbPDO.php");
require_once(SLIGHTPHP_PLUGINS_DIR."/db/DbMysqli.php");
class Db{
	/**
	 * 
	 */
	private $engine;
	private $params;
	private $_engine_name="pdo_mysql";
	private $_allow_engines=array(
		"mysqli",
		"pdo_mysql","pdo_sqlite","pdo_cubrid",
		"pdo_dblib","pdo_firebird","pdo_ibm",
		"pdo_informix","pdo_sqlsrv","pdo_oci",
		"pdo_odbc","pdo_pgsql","pdo_4d"
	);
	private $_key;

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
	 *
	 */
	function __construct($engineName="mysql"){
		$this->__setEngine($engineName);
	}
	public function error(){
		return $this->error;
	}
	private function __setEngine($engineName){
		if(in_array($engineName,$this->_allow_engines)){
			$this->_engine_name=$engineName;
		}else{
			die("Db engine: $engineName does not support!");
		}
	}
	/**
	 * construct
	 *
	 * @param array|object $params
	 * @param string $params.host
	 * @param string $params.user
	 * @param string $params.password
	 * @param string $params.database
	 * @param string $params.charset
	 * @param string $params.engine
	 * @param bool $params.persistent 
	 * @param int $param.port=3306
	 */
	private function _reInit(){
		if(empty($this->params)){
			return false;
		}
		return $this->init($this->params);
	}
	public function init($params){
		if(is_object($params))$params=(array)$params;

		if(!isset($params['engine']) || !in_array($params['engine'],$this->_allow_engines)){
			$params['engine']=$this->_engine_name;
		}
		$this->params=$params;
		$this->__setEngine($params['engine']);
		$this->_key = implode("|",$params);

		if($this->_engine_name=="mysqli" && extension_loaded('mysqli')){
			$this->engine = new \SlightPHP\DbMysqli($this->params);
		}elseif(extension_loaded('pdo')){
			$this->engine = new \SlightPHP\DbPDO($this->params);
		}else{
			trigger_error("pdo and mysqli extension not exists",E_USER_ERROR);
			return false;
		}
		$this->engine->init($this->params);
		if($this->engine->connect()===false){
			$this->error['code']=$this->engine->errno();
			$this->error['msg']=$this->engine->error();
			if(defined("DEBUG")){
				trigger_error("{$this->_engine_name} ( ".var_export($this->error,true).")");
			}
			return false;
		}
		return true;
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
		$condiStr = $this->__quote($condition,"AND",$params);

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
		$sql="SELECT $item FROM ($table) $join $condiStr $groupby $orderby_sql $limit_sql";
		$start = microtime(true);

		$result = $this->__query($sql,false,$params);
		if($result!==false){
			$data = new DbData;
			$data->page = (int)$this->page;
			$data->limit = (int)$this->limit;
			$data->items= $result;
			$data->pageSize = (int)count($data->items);
			//{{{
			if($this->count==true){
				if($this->limit>0){
					$countsql="SELECT count(1) totalSize FROM ($table)$join $condiStr $groupby";
					$result_count = $this->__query($countsql,false,$params);
					if(!empty($result_count[0])){
						$data->totalSize = (int)$result_count[0]['totalSize'];
						$data->totalPage = (int)ceil($data->totalSize/$data->limit);
					}
				}else{
					$data->totalSize = $data->pageSize;
					$data->totalPage = 1;
				}
			}
			//}}}
			$end = microtime(true);
			$data->totalSecond = (int)$end-$start;
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
		$value = $this->__quote($item,",",$params);
		$condiStr = $this->__quote($condition,"AND",$params2);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$sql="UPDATE $table SET $value $condiStr";
		return $this->__query($sql,false,$this->merge_params($params,$params2));
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
		$condiStr = $this->__quote($condition,"AND",$params);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$sql="DELETE FROM  $table $condiStr";
		return $this->__query($sql,false,$params);
	}
	public function escape($str){
		return $this->engine->escape($str);
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
	public function insert($table,$item="",$isreplace=false,$isdelayed=false,$update=array(),$ignore=false){
		$table = $this->__array2string($table);
		if($isreplace==true){
			$command="REPLACE";
		}else{
			if($ignore){
				$command="INSERT IGNORE";
			}else{
				$command="INSERT";
			}
		}
		if($isdelayed==true){
			$command.=" DELAYED ";
		}

		$f = $this->__quote($item,",",$params);

		$sql="$command INTO $table SET $f ";
		$v = $this->__quote($update,",",$params2);
		if(!empty($v)){
			$sql.="ON DUPLICATE KEY UPDATE $v";
		}
		return $this->__query($sql,false,$this->merge_params($params,$params2));
	}

	/**
	 * merge array
	 */
	private function merge_params(...$arr){
		$arr = array_filter($arr,function($var){
			return ($var && is_array($var)) ? true : false;
		});
		return array_merge(...$arr);
	}

	/**
	 * query
	 *
	 * @param string $sql
	 * @return Array $result  || Boolean false
	 */

	private function __query($sql, $retry=false, $params=[]){
		//{{{
		//SQL MODE 默认为DELETE，INSERT，REPLACE 或 UPDATE,不需要返回值
		$sql_mode = 1;//1.更新模式 2.查询模式 3.插入模式

		if(stripos($sql,"INSERT")===0){
			$sql_mode = 3;
		}else{
			$sql_result_query=array("SELECT","SHOW","DESCRIBE","EXPLAIN");
			foreach($sql_result_query as $query_type){
				if(stripos($sql,$query_type)===0){
					$sql_mode = 2;
					break;
				}
			}
		}
		//}}}
		if(defined("DEBUG")){
			trigger_error("{$this->_engine_name} ( $sql )");
		}

		$result = $this->engine->query($sql, $params);

		if($result){
			if($sql_mode==2){//查询模式
				if(($data=$this->engine->getAll())!==false){
					return $data;
				}
			}elseif($sql_mode==3){//插入模式
				return $this->engine->lastId();
			}else{
				return $this->engine->count();
			}
		}
		$this->error['code']=$this->engine->errno();
		$this->error['msg']=$this->engine->error();

		if($retry===false && $this->engine->connectionError){
			$this->_reInit();
			return $this->__query($sql,true,$params);
		}
		trigger_error("DB QUERY ERROR (".var_export($this->error['msg'],true)."), CODE({$this->error['code']}), SQL({$sql})",E_USER_WARNING);
		return false;
	}
	/**
	 * query sql and return data
	 * @param string $sql
	 * @param array $parameters
	 * @return boolean|int|array
	 */
	public function query($sql,$params=[]){
		return $this->__query($sql, false, $params);
	}
	/**
	 * execute sql(multi sql) 
	 * @param string $sql
	 * @return boolean
	 */
	public function execute($sql){
		return $this->engine->exec($sql);
	}
	public function begin(){
		return $this->engine->begin();
	}
	public function commit(){
		return $this->engine->commit();
	}
	public function rollback(){
		return $this->engine->rollback();
	}

	private function __quote($condition,$split="AND",&$params=[]){
		$condiStr = "";
		if(is_array($condition) || is_object($condition)){
			$v1=array();
			$i=1;
			foreach($condition as $k=>$v){
				if(!is_numeric($k)){
					if(strpos($k,".")===false){
						$k = $this->__addsqlslashes($k);
					}
					if(!is_null($v)){
						$params[]=$v;
						$v1[]="$k = ?";
					}else{
						$v1[]="$k = NULL";
					}
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
