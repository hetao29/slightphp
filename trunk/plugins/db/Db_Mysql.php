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


class Db_Mysql extends DbObject{
	/**
	 * 
	 */
	private $mysql;

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
	public $orderby;
	/**
	 *
	 */
	public $groupby;
	/**
	 *
	 */
	public $sql;
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
	private $countsql;
	/**
	 *
	 */
	public $error=array('code'=>0,'msg'=>"");
	/**
	 * @var array $globals
	 */
	static $globals;
	function __construct(){
	}
	/**
	 * construct
	 *
	 * @param string host
	 * @param string user
	 * @param string password
	 * @param string database
	 * @param int port=3306
	 */
	function init($params=array()){
		foreach($params as $key=>$value){
			$this->$key = $value;
		}
		$this->key = "mysql:".$this->host.":".$this->user.":".$this->password;
		if(!isset(Db_Mysql::$globals[$this->key])) Db_Mysql::$globals[$this->key] = "";
	}
	/**
	 * is count 
	 *
	 * @param boolean count
	 */
	function setCount($count)
	{
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
	function setPage($page)
	{
		if(!is_numeric($page) || $page<1){$page=1;}
		$this->page=$page;
	}
	/**
	 * page size
	 *
	 * @param int limit ,0 is all
	 */
	function setLimit($limit)
	{
		if(!is_numeric($limit) || $limit<0){$limit=0;}
		$this->limit=$limit;
	}
	/**
	 * group by sql
	 *
	 * @param string groupby 
	 * eg:	setGroupby("groupby MusicID");
	 *      setGroupby("groupby MusicID,MusicName");
	 */
	function setGroupby($groupby)
	{
		$this->groupby=$groupby;
	}
	/**
	 * order by sql
	 *
	 * @param string orderby
	 * eg:	setOrderby("order by MusicID Desc");
	 */
	function setOrderby($orderby)
	{
		$this->orderby=$orderby;
	}

	/**
	 * select data from db
	 *
	 * @param mixed $table 
	 * @param array $condition
	 * @param array $item 
	 * @param string $groupby 
	 * @param string $orderby
	 * @param string $leftjoin
	 * @return DbData object
	 */
	function select($table,$condition="",$item="*",$groupby="",$orderby="",$leftjoin=""){
		if($item==""){$item="*";}
		if(is_array($table)){
			for($i=0;$i<count($table);$i++)
			{
				$tmp[]=trim($table[$i]);
			}
			$table=@implode(" , ",$tmp);
		}else{
			$table=trim($table);
		}

		if(is_array($item))
		$item =@implode(" , ",$item);

		$condiStr = $this->__quote($condition);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$join="";
		if(is_array($leftjoin)){
			foreach ($leftjoin as $key=>$value){
				$join.=" LEFT JOIN $key ON $value ";
			}
		}

		$this->groupby  =$groupby!=""?$groupby:$this->groupby;
		$this->orderby  =$orderby!=""?$orderby:$this->orderby;
		$orderby_sql="";
		$orderby_sql_tmp = array();
		if(is_array($orderby)){
			foreach($orderby as $key=>$value){
				if(!is_numeric($key)){
					$orderby_sql_tmp[]=$key." ".$value;
				}
			}
		}else{
			$orderby_sql=$this->orderby;
		}
		if(count($orderby_sql_tmp)>0){
			$orderby_sql=" ORDER BY ".implode(",",$orderby_sql_tmp);
		}
		$limit="";
		if($this->limit!=0){
			$limit    =($this->page-1)*$this->limit;
			$limit ="LIMIT $limit,$this->limit";
		}
		$this->sql="SELECT $item FROM $table $join $condiStr $groupby $orderby_sql $limit";
		$this->countsql="SELECT count(1) totalSize FROM $table $condiStr $groupby";
		return $this->query($this->sql,$this->countsql);
	}
	/**
	 * 
	 *
	 * @param mixed $table
	 * @param array $condition
	 * @param array $item 
	 * @param string $groupby
	 * @param string $orderby
	 * @param string $leftjoin
	 * @return array item
	 */
	function selectOne($table,$condition="",$item="*",$groupby="",$orderby="",$leftjoin="")
	{
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
	 * @param mixed $table
	 * @param string,array $condition
	 * @param array $item
	 * @param int $limit
	 * @package int
	 * update("table",array('name'=>'myName','password'=>'myPass'),array('id'=>1));
	 * update("table",array('name'=>'myName','password'=>'myPass'),array("password=$myPass"));
	 */
	function update($table,$condition="",$item=""){
		$value = $this->__quote($item,",");
		$condiStr = $this->__quote($condition);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$this->sql="UPDATE $table SET $value $condiStr";
		$this->execute($this->sql);
		return $this->rowCount();
	}
	/**
	 * delete
	 *
	 * @param mixed table
	 * @param string,array $condition
	 * @param int $limit
	 * @return int
	 * delete("table",array('name'=>'myName','password'=>'myPass'),array('id'=>1));
	 * delete("table",array('name'=>'myName','password'=>'myPass'),array("password=$myPass"));
	 */
	function delete($table,$condition=""){
		$condiStr = $this->__quote($condition);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$this->sql="DELETE FROM  $table $condiStr";
		$this->execute($this->sql);
		return $this->rowCount();
	}
	/**
	 * insert
	 * 
	 * @param $table
	 * @param array $item 
	 * @param array $update ,egarray("key"=>value,"key2"=>value2")
		 insert into zone_user_online values(2,'','','','',now(),now()) on duplicate key update onlineactivetime=CURRENT_TIMESTAMP;
	 * @return int InsertID
	 */
	function insert($table,$item="",$isreplace=false,$isdelayed=false,$update=array())
	{
		if($isreplace==true){
			$command="REPLACE";
		}else{
			$command="INSERT";
		}
		if($isdelayed==true){
			$command.=" DELAYED ";
		}

		$f = $this->__quote($item,",");

		$this->sql="$command INTO $table SET $f ";
		$v = $this->__quote($update);
		if(!empty($v)){
			$this->sql.="ON DUPLICATE KEY UPDATE ".implode(",",$v);
		}
		$r=$this->execute($this->sql);
		if($this->lastInsertId ()>0){
			return $this->lastInsertId ();
		}else{
			return $r;
		}
	}

	/**
	 * query
	 *
	 * @param string $sql
	 * @return DbData object
	 */
	
	function query($sql,$countsql="")
	{
		$data = new DbData;
		$data->limit = $this->limit;
		$start = microtime(true);
		$result = $this->execute($sql);
		$end = microtime(true);
		$data->totalSecond = $end-$start;
		if($result){
			while($row=mysql_fetch_array($result,MYSQL_ASSOC)){
				$tmp = array();
				foreach($row as $key=>$value){
					$tmp[$key]=stripslashes($value);
				}
				$data->items[]=$tmp;
				$data->pageSize++;
			}
		}
		if($this->limit !=0 and $this->count==true and $countsql!=""){
			$result = $this->execute($countsql);
			if($result){
				$row = mysql_fetch_array($result,MYSQL_NUM );
				$data->totalSize = $row[0];
			}
			$data->totalPage = ceil($data->totalSize/$data->limit);
		}
		return $data;

	}
	function lastInsertId(){
		return mysql_insert_id(Db_Mysql::$globals[$this->key]);
	}
	function rowCount(){
		return mysql_affected_rows(Db_Mysql::$globals[$this->key]);
	}


	function __connect($forceReconnect=false){
		if(empty(Db_Mysql::$globals[$this->key]) || $forceReconnect){
			if(!empty(Db_Mysql::$globals[$this->key])){
				mysql_close(Db_Mysql::$globals[$this->key]);
				unset(Db_Mysql::$globals[$this->key]);
			}
			Db_Mysql::$globals[$this->key] = mysql_connect($this->host.":".$this->port,$this->user,$this->password);
		}
		if(!Db_Mysql::$globals[$this->key]){
			die("connect database error:\n".var_export($this,true));
		}
		if($this->database!=""){
			mysql_select_db($this->database,Db_Mysql::$globals[$this->key]);
			if(!empty($this->charset)){
				mysql_query("SET NAMES ".$this->charset);
			}
		}
	}
	function execute($sql){
		if(empty(Db_Mysql::$globals[$this->key])){
			$this->__connect($forceReconnect=true);
		}
		if(defined("DEBUG")){
			echo "SQL:$sql\n";
		}
		$result = mysql_query($sql,Db_Mysql::$globals[$this->key]);
		if(!$result){
			$this->error['code']=mysql_errno();
			$this->error['msg']=mysql_error();
			
			return false;
		}else{
			return $result;
		}
	}

	function __quote($condition,$split="AND"){
		$condiStr = "";
		if(is_array($condition)){
			$v1=array();
			foreach($condition as $k=>$v)
			{
				if(!is_numeric($k))
				{
					$v1[]="`".$k."`"." = '".mysql_escape_string($v)."'";
				}else{
					$v1[]=($v);
				}
			}
			if(count($v1)>0)
			{
				$condiStr=implode(" ".$split." ",$v1);

			}
		}else{
			$condiStr=$condition;
		}
		return $condiStr;
	}
}
?>
