<?php
abstract class CacheObject{
	abstract function set($key,$value,$timestamp=-1);
	abstract function get($key);
	abstract function del($key);

}
?>