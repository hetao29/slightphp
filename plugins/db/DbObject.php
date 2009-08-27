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
	abstract function execute($sql);

}
?>