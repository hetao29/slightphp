<?php
class SlightPHPCache{

	var $lifeTime;
	var $id;
    function pageCache($lifeTime=3600)
    { 
		if($lifeTime==-1)$lifeTime=3600*24*265;
		ob_start("ob_gzhandler");
		if (false) {
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Pragma: no-cache");
			return false;
		}
		header("Pragma:");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
		header("Expires: " . date("D, d M Y H:i:s", time()+$lifeTime) . " GMT");
		header("Cache-Control: max-age=" . "$lifeTime");
		return true;
    }
}
?>