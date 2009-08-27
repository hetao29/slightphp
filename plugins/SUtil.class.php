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


class SUtil{

	/**
	 *
	 */
	static function getRequestValue(array $data,$key,$minLength,$maxLength){
		if(empty($data[$key]) || strlen($data[$key])<$minLength || strlen($data[$key])>$maxLength){
			return false;
		}
		return $data[$key];
	}
	/**
	 *
	 */
	static function log($logFile,$data){
		error_log("[".date("Y-m-d H:i:s")."]$data\r\n",3,$logFile);
	}
	/**
	 *
	 */
	static function getIP($long=false) {
		$cip = getenv('HTTP_CLIENT_IP');
		$xip = getenv('HTTP_X_FORWARDED_FOR');
		$rip = getenv('REMOTE_ADDR');
		$srip = @$_SERVER['REMOTE_ADDR'];
		if($cip && strcasecmp($cip, 'unknown')) {
			$ip = $cip;
		} elseif($xip && strcasecmp($xip, 'unknown')) {
			$ip = $xip;
		} elseif($rip && strcasecmp($rip, 'unknown')) {
			$ip = $rip;
		} elseif($srip && strcasecmp($srip, 'unknown')) {
			$ip = $srip;
		}
		preg_match("/[\d\.]{7,15}/", $ip, $match);
		$ip = $match[0] ? $match[0] : 'unknown';
		if($long){
			return sprintf("%u",ip2long($ip));
		}
		return $ip;
	}
	function get( $url,$cookie="",$returnHeader=true)
	{
		$url_info = parse_url($url);
		if(empty($url_info['host']) || empty($url_info['scheme']) || !in_array($url_info['scheme'],array('http','https')) ){
			return;
		}
		$host = $url_info['host'];
		if(empty($url_info['path'])){
			$url_info['path'] = "/";
		}
		$port = empty( $url_info['port'])?80:$url['port'];
		if(!empty($url_info['query'])){
			$urlquery  = ($url_info['path']."?".$url_info['query']);
		}else{
			$urlquery  = $url_info['path'];
		}
		if(!empty($cookie)){
			$cookie_str = "\nCookie: ".$cookie."\n";
		}else{
			$cookie_str ="";
		}
		$data ="GET $urlquery HTTP/1.1\nAccept: */*\nUA-CPU: x86\nAccept-Language: zh-cn\nReferer: $url\nUA-CPU: x86\nUser-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506)\nHost: $host\nConnection: Close$cookie_str\n\n";
		$fp = fsockopen($host,$port);
		if($fp){

			fwrite($fp,$data);

			if($returnHeader){
				return stream_get_contents($fp);
			}else{
				$inheader = 1;
				$length = 0;
				while(!feof($fp))
				{		
						
					if ($inheader){
						if(($line = fgets($fp))===false){
							break;
						}
						if(($line == "\n" || $line == "\r\n")){
							$length = trim(fgets($fp));
							$inheader = 0;
						}
						continue;
					}
					if($length == 0){
						return stream_get_contents($fp);
					}else{
						return fread($fp,hexdec($length));
					}
				}
			}
		}
		return;
	}
	function post($urlstr, $data)
	{
		$url = parse_url( $urlstr );
		if (!$url)
		return false;

		if (!isset($url['port']))
		$url['port'] = "";

		if (!isset($url['query']))
		$url['query'] = "";

		$encoded = "";
		while (list($k,$v) = each($data))
		{
				$encoded .= ($encoded ? "&" : "");
				$encoded .= rawurlencode($k)."=".rawurlencode($v);
		}

		$fp = fsockopen($url['host'], $url['port'] ? $url['port'] : 80);
		if (!$fp)
		return false;

		fputs($fp, sprintf("POST %s%s%s HTTP/1.0\n", $url['path'], $url['query'] ? "?" : "", $url['query']));
		fputs($fp, "Host: $url[host]\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
		fputs($fp, "Content-length: " . strlen($encoded) . "\n");
		fputs($fp, "Connection: close\n\n");

		fputs($fp, "$encoded\n");

		$line = fgets($fp,1024);
		if (!eregi("^HTTP/1\.. 200", $line))
		return false;

		$results = "";
		$inheader = 1;
		while(!feof($fp))
		{
				$line = fgets($fp,1024);
				if ($inheader && ($line == "\n" || $line == "\r\n"))
				$inheader = 0;
				elseif (!$inheader)
				$results .= $line;
		}
		fclose($fp);
		return $results;
	}
	static function validEmail($email){
		return preg_match('/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])*(\.([a-z0-9])([-a-z0-9_-])([a-z0-9])+)*$/i',$email);
	}
}
?>