<?php        
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."SDbData.php");

class SMysql{
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
	public $user;
	/**
	 *
	 */
	public $pass;
	/**
	 *
	 */
	public $database;
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
	 * 构造方法
	 *
	 * @param string host
	 * @param string user
	 * @param string pass
	 * @param string database
	 */
	function SMysql($host,$user,$pass,$database=null){
		$this->host= $host;
		$this->user= $user;
		$this->pass= $pass;
		$this->database= $database;
		$this->key = "mysql:".$this->host.":".$this->user.":".$this->pass;
		$GLOBALS[$this->key]="";
		$this->__connect();
	}
	/**
	 * 设置是否计算总数
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
	 * 设置页码
	 *
	 * @param int page 页码，默认为1
	 */
	function setPage($page)
	{
		if(!is_numeric($page) || $page<1){$page=1;}
		$this->page=$page;
	}
	/**
	 * 设置检索限制
	 *
	 * @param int limit 默认为0，就是不限制
	 */
	function setLimit($limit)
	{
		if(!is_numeric($limit) || $limit<0){$limit=0;}
		$this->limit=$limit;
	}
	/**
	 * 设置Groupby
	 *
	 * @param string groupby 默认为空，
	 * 例子:setGroupby("groupby MusicID");
	 *      setGroupby("groupby MusicID,MusicName");
	 */
	function setGroupby($groupby)
	{
		$this->groupby=$groupby;
	}
	/**
	 * 设置Orderby
	 *
	 * @param string orderby 默认为空，
	 * 例子:setOrderby("order by MusicID Desc");
	 */
	function setOrderby($orderby)
	{
		$this->orderby=$orderby;
	}

	/**
	 * 检索数据方法
	 *
	 * @param mixed $table，表名，用数组是多个表
	 * @param array $condition 检索条件
	 * @param array $item 返回的字段
	 * @param string $groupby  groupby语句
	 * @param string $orderby  orderby语句
	 * @param string $leftjoin  leftjoin语句
	 * @return SDbData object
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

		$condiStr = $this->__quoteCondition($condition);
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
	 * 检索数据方法
	 *
	 * @param mixed $table，表名，用数组是多个表
	 * @param array $condition 检索条件
	 * @param array $item 返回的字段
	 * @param string $groupby  groupby语句
	 * @param string $orderby  orderby语句
	 * @param string $leftjoin  leftjoin语句
	 * @return array item
	 */
	function selectOne($table,$condition="",$item="*",$groupby="",$orderby="",$leftjoin="")
	{
		$this->setLimit(1);
		$this->setCount(false);
		$data=$this->select($table,$condition,$item,$groupby,$orderby,$leftjoin);
		if(isset($data->items[0]))
		return $data->items[0];
		else return;

	}

	/**
	 * 更新函数，只能对一个表更新
	 *
	 * @param array $table 要更新的表
	 * @param string,array $condition 更新条件
	 * @param array $item 更新对像
	 * @param int $limit 限制条数 0表示没有限制
	 * @package int 返回修改的条数
	 * update("table",array('name'=>'myName','pass'=>'myPass'),array('id'=>1));
	 * update("table",array('name'=>'myName','pass'=>'myPass'),array("pass=$myPass"));
	 */
	function update($table,$condition="",$item=""){
		$value = $this->__quoteCondition($item,",");
		$condiStr = $this->__quoteCondition($condition);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$this->sql="UPDATE $table SET $value $condiStr";
		$this->__execute($this->sql);
		return $this->rowCount();
	}
	/**
	 * 删除函数，只能对一个表删除
	 *
	 * @param array table 要删除的表
	 * @param string,array $condition 更新条件
	 * @param int $limit 限制条数 0表示没有限制
	 * @return int 返回删除条数
	 * delete("table",array('name'=>'myName','pass'=>'myPass'),array('id'=>1));
	 * delete("table",array('name'=>'myName','pass'=>'myPass'),array("pass=$myPass"));
	 */
	function delete($table,$condition=""){
		$condiStr = $this->__quoteCondition($condition);
		if($condiStr!=""){
			$condiStr=" WHERE ".$condiStr;
		}
		$this->sql="DELETE FROM  $table $condiStr";
		$this->__execute($this->sql);
		return $this->rowCount();
	}
	/**
	 * 插入函数，只能对一个表插入
	 * @param $table 表名
	 * @param array $item 插入的数据如array(0,4)或者array("ID"=>3,"PlayID"=>4)
	 * @param array $update ,值是 array("key"=>value,"key2"=>value2")格式或者 array("a=>b","c=>x")，不能是array("b","c") 执行如如下SQL:
		 insert into zone_user_online values(2,'','','','',now(),now()) on duplicate key update onlineactivetime=CURRENT_TIMESTAMP;
	 * @return int InsertID 返回InsertID
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

		$f = $this->__quoteItem($item);

		$this->sql="$command INTO $table ".$f['field']." VALUES(".$f['value'].")";
		$v = $this->__quoteCondition($update);
		if(!empty($v)){
			$this->sql.="ON DUPLICATE KEY UPDATE ".implode(",",$v);
		}
		$r=$this->__execute($this->sql);
		if($this->lastInsertId ()>0){
			return $this->lastInsertId ();
		}else{
			return $r;
		}
	}

	/**
	 * 直接查询语句
	 *
	 * @param string $sql
	 * @return SDbData object
	 */
	
	function query($sql,$countsql="")
	{
		$data = new SDbData;
		$data->limit = $this->limit;
		$start = microtime(true);
		$result = $this->__execute($sql);
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
			$result = $this->__execute($countsql);
			if($result){
				$row = mysql_fetch_array($result,MYSQL_NUM );
				$data->totalSize = $row[0];
			}
			$data->totalPage = ceil($data->totalSize/$data->limit);
		}
		return $data;

	}
	function lastInsertId(){
		return mysql_insert_id($GLOBALS[$this->key]);
	}
	function rowCount(){
		return mysql_affected_rows($GLOBALS[$this->key]);
	}
	/**
	 * 析构函数
	 */

	function __connect($forceReconnect=false){
		if(empty($GLOBALS[$this->key]) || $forceReconnect){
			if(!empty($GLOBALS[$this->key])){
				mysql_close($GLOBALS[$this->key]);
				unset($GLOBALS[$this->key]);
			}
			$GLOBALS[$this->key] = mysql_connect($this->host,$this->user,$this->pass,false,MYSQL_CLIENT_COMPRESS);
		}
		if(!$GLOBALS[$this->key]){
			die("网络繁忙，请稍后在试");
		}
		if($this->database!=""){
			mysql_select_db($this->database,$GLOBALS[$this->key]);
			if(defined("mysql_charset")){
				$charset = "SET NAMES '".mysql_charset."'";
				mysql_query($charset);
			}
		}
	}
	function __execute($sql){
		if(empty($GLOBALS[$this->key]) || !mysql_ping($GLOBALS[$this->key])){
			$this->__connect($forceReconnect=true);
		}
		if(defined("DEBUG")){
			echo "SQL:$sql\n";
		}
		$result = mysql_query($sql,$GLOBALS[$this->key]);
		if(!$result){
			$this->error['code']=mysql_errno();
			$this->error['msg']=mysql_error();
			
			return false;
		}else{
			return $result;
		}
	}
	function __quoteItem($item){
		$result = array("field"=>"","value"=>"");
		if(is_array($item)){
			$it=array();
			$value=array();
			foreach($item as $k=>$v){
				if(!is_numeric($k))
				{
					$it[]="`".$k."`";
					$value[]="'".mysql_real_escape_string($v,$GLOBALS[$this->key])."'";
				}else{
					$tmp=explode("=",$v);
					if(isset($tmp[0]) and isset($tmp[1])){
						$it[]="`".$tmp[0]."`";
						$value[]=$tmp[1];
					}
					unset($tmp);
				}
				
			}
			if(count($value)>0){
				$result['value'] = implode(",",$value);
			}
			if(count($it)){
				$result['field'] = "(".implode(",",$it).")";
			}
			return $result;
		}
	}
	function __quoteCondition($condition,$split="AND"){
		$condiStr = "";
		if(is_array($condition)){
			$v1=array();
			foreach($condition as $k=>$v)
			{
				if(!is_numeric($k))
				{
					$v1[]="`".$k."`"." = '".mysql_real_escape_string($v,$GLOBALS[$this->key])."'";
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