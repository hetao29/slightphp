<?php
class index_redis{
	public function pageEntry($inPath){
        SRedis::useConfig("default");
		$r = SRedis::set($key="key", $v="value.".date("Y-m-d H:i:s"), 60);
		if($r){
			echo "set redis ok!<br />\n";
		}else{
			echo "set redis fail!<br />\n";
		}
        $v   = SRedis::get($key);
		echo "get redis values:$v <br />\n";
	}
}
