<?php
abstract class DbObject{
	abstract function init($params=array());
	abstract function setCount($count);
	abstract function setPage($page);
	abstract function setLimit($limit);
	abstract function setGroupby($groupby);
	abstract function setOrderby($orderby);
	abstract function select($table,$condition="",$item="*",$groupby="",$orderby="",$leftjoin="");
	abstract function selectOne($table,$condition="",$item="*",$groupby="",$orderby="",$leftjoin="");
	abstract function update($table,$condition="",$item="");
	abstract function delete($table,$condition="");
	abstract function insert($table,$item="",$isreplace=false,$isdelayed=false,$update=array());
	abstract function query($sql,$countsql="");

}
?>