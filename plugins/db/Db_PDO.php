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
class Db_PDO extends DbObject{
	/**
	 * 
	 */
	//private $mysql;

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
	private $prefix;
	private $countsql;
	private $affectedRows=0;
	/**
	 *
	 */
	public $error=array('code'=>0,'msg'=>"");
	/**
	 * @var array $globals
	 */
	static $globals;
	function __construct($prefix="mysql"){
		$this->prefix=$prefix;
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
		$this->key = $this->prefix.":".$this->host.":".$this->user.":".$this->password;
		if(!isset(Db_PDO::$globals[$this->key])) Db_PDO::$globals[$this->key] = "";
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
	 * @return DbData object || Boolean false
	 */
	function select($table,$condition="",$item="*",$groupby="",$orderby="",$leftjoin=""){
		//{{{$item
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
		//}}}
		//{{{$condition
		$condiStr = $this->__quote($condition,"AND",$bind);

		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		//}}}
		//{{{
		$join="";
		if(is_array($leftjoin)){
			foreach ($leftjoin as $key=>$value){
				$join.=" LEFT JOIN $key ON $value ";
			}
		}
		//}}}
		//{{{
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
		//}}}

		$limit="";
		if($this->limit!=0){
			$limit    =($this->page-1)*$this->limit;
			$limit ="LIMIT $limit,$this->limit";
		}
		$this->sql="SELECT $item FROM $table $join $condiStr $groupby $orderby_sql $limit";
		$this->countsql="SELECT count(1) totalSize FROM $table $condiStr $groupby";
		$data = new DbData;
		
		$data->page = $this->page;
		$data->limit = $this->limit;
		$start = microtime(true);


		$data->limit = $this->limit;
		$data->items = $this->query($this->sql,$bind);
		if($data->items === false){
			//²éÑ¯Ê§°Ü
			return false;
		}
		$data->pageSize = count($data->items);
		$end = microtime(true);
		$data->totalSecond = $end-$start;

		//}}}
		

		//{{{
		if($this->limit !=0 and $this->count==true and $this->countsql!=""){
			$result_count = $this->query($this->countsql,$bind);
			$data->totalSize = $result_count[0]['totalSize'];
			$data->totalPage = ceil($data->totalSize/$data->limit);
		}
		//}}}
		return $data;
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
	 * @return int
	 * update("table",array('name'=>'myName','password'=>'myPass'),array('id'=>1));
	 * update("table",array('name'=>'myName','password'=>'myPass'),array("password=$myPass"));
	 */
	function update($table,$condition="",$item=""){
		$value = $this->__quote($item,",",$bind_v);
		$condiStr = $this->__quote($condition,"AND",$bind_c);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$this->sql="UPDATE $table SET $value $condiStr";
		if($this->query($this->sql,$bind_v,$bind_c)!==false){
			return $this->rowCount();
		}else{
			return false;
		}
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
		$condiStr = $this->__quote($condition,"AND",$bind);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$this->sql="DELETE FROM  $table $condiStr";
		if($this->query($this->sql,$bind)!==false){
			return $this->rowCount();
		}else{
			return false;
		}
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

		$f = $this->__quote($item,",",$bind_f);

		$this->sql="$command INTO $table SET $f ";
		$v = $this->__quote($update,"AND",$bind_v);
		if(!empty($v)){
			$this->sql.="ON DUPLICATE KEY UPDATE ".implode(",",$v);
		}
		$r=$this->query($this->sql,$bind_f,$bind_v);
		if($this->lastInsertId ()>0){
			return $this->lastInsertId ();
		}elseif($this->affectedRows >0){
			return $this->affectedRows;
		}else{
			return $r;
		}
	}

	/**
	 * query
	 *
	 * @param string $sql
	 * @return Array $result  || Boolean false
	 */
	
	function query($sql,$bind1=array(),$bind2=array())
	{
		//{{{
		if(empty(Db_PDO::$globals[$this->key])){
			$this->__connect($forceReconnect=true);
		}
		if(defined("DEBUG")){
			echo "SQL:$sql\n";
			print_r($bind1);
			print_r($bind2);
		}
		$stmt = Db_PDO::$globals[$this->key]->prepare($sql);
		
		if(!$stmt){
			
			$this->error['code']=Db_PDO::$globals[$this->key]->errorCode ();
			$this->error['msg']=Db_PDO::$globals[$this->key]->errorInfo ();
			return false;
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
			$this->affectedRows = $stmt->rowCount();
			return $stmt->fetchAll (PDO::FETCH_ASSOC );
		}else{
			$this->error['code']=$stmt->errorCode ();
			$this->error['msg']=$stmt->errorInfo ();
		}
		return false;

	}
	function lastInsertId(){
		return Db_PDO::$globals[$this->key]->lastInsertId ();
	}
	function rowCount(){
		return $this->affectedRows;
	}

	/**
	 *
	 * @param string $sql
	 * @return array $data || Boolean false
	 */
	function execute($sql){
		return $this->query($sql);
	}

	function __connect($forceReconnect=false){
		if(empty(Db_PDO::$globals[$this->key]) || $forceReconnect){
			if(!empty(Db_PDO::$globals[$this->key])){
				unset(Db_PDO::$globals[$this->key]);
			}
			try{
			Db_PDO::$globals[$this->key] = new PDO($this->prefix.":dbname=".$this->database.";host=".$this->host.";port=".$this->port,$this->user,$this->password);
			}catch(Exception $e){
				die("connect database error:\n".var_export($this,true));
			}
		}
		if(!empty($this->charset)){
			$this->execute("SET NAMES ".$this->charset);
		}
	}
	function __quote($condition,$split="AND",&$bind){
		$condiStr = "";
		if(!is_array($bind)){$bind=array();}
		if(is_array($condition)){
			$v1=array();
			$i=1;
			foreach($condition as $k=>$v)
			{
				if(!is_numeric($k))
				{
					if(strpos($k,".")>0){
						$v1[]="$k = ?";
					}else{
						$v1[]="`$k` = ?";
					}
					$bind[$i++]=$v;
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
