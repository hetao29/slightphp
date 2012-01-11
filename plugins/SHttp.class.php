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
 */
class SHttp{

	/**
	 * get url
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $cookies
	 * @param boolean $returnHeader
	 * @return string | array
	 */

	function get( $url, $params=array(), $cookies=array(), $returnHeader=false, $timeout=1){
		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'timeout'=>$timeout,
				'header'=>
						"Accept-Language: zh-cn\r\n" .
						"User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)\r\n" .
						"Referer: $url\r\n" .
						(!empty($cookies)?"Cookie: ".self::cookie_build($cookies)."\r\n":"").
						"Connection: Close\r\n" ,
			)
		);
		$url .= empty($params)?"":"?".http_build_query($params);
		$context = stream_context_create($opts);
		$result =  file_get_contents($url,false,$context);
		if($returnHeader){
			return array("result"=>$result,"header"=>$http_response_header);
		}else{
			return $result;
		}
	}
	/**
	 * get multi urls
	 *
	 * @param array $url_array
	 * @param int $timeout=1
	 * @return string | array
	 */
	function getArray($url_array, $timeout = 1) {
		if (!is_array($url_array))
			return false;
		$data    = array();
		$handle  = array();
		if(!function_exists("curl_multi_init")){
			foreach($url_array as $key=>$url){
				$_tmp = parse_url($url);
				$_tmp['port'] = empty($_tmp['port'])?80:$_tmp['port'];
				$requests[$key]=$_tmp;
			}
			$timeout = 1;
			$status = array();
			$retdata = array();
			$sockets = array();
			$e = array();
			$data    = array();
			foreach($url_array as $key=>$url){
				$_tmp = parse_url($url);
				$_tmp['port'] = empty($_tmp['port'])?80:$_tmp['port'];
				$host = $_tmp['host'];
				$port = $_tmp['port'];
				$errno = 0;
				$errstr = "";
				$s = @stream_socket_client("$host:$port", $errno, $errstr, $timeout,STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_CONNECT);
				$data[$key]=array("error"=>"","errno"=>"","result"=>"");
				if ($s) {
					$_p = $_tmp['path']."?".$_tmp['query'];
					fwrite($s, "GET $_p HTTP/1.0\r\nHost: ".$host."\r\n\r\n");
					$sockets[$s] = $s;
				} else {
					$data[$key]=array("error"=>$errstr,"errno"=>$errno,"result"=>"");
				}
				$handle[$s] = array("key"=>$key,"ch"=>$s,"req"=>$_tmp);
			}
			while (count($sockets)) {
				$read = $write = $sockets;
				$n = stream_select($read, $write=null, $e=null, $timeout);
				if ($n > 0) {
					foreach ($read as $r) {
						$_r = ($handle[$r]);
						$_data = stream_get_contents($r);
						$_key = $_r['key'];
						if (strlen($_data) == 0) {
							fclose($r);
							unset($sockets[$r]);
						} else {
							preg_match("/Content-Length: (\d+)/i",$_data,$_m);
							if(!empty($_m[1])){
								$_data=substr($_data,0-$_m[1]);
							}
							$data[$_key]['result']=$_data;
						}
					}
				} else {
					foreach ($sockets as $id => $s) {
						$status[$id] = "timed out " . $status[$id];
					}
					break;
				}
			}
			return $data;
		}else{
			$running = 0;
			$mh = curl_multi_init();
			$i = 0;
			foreach($url_array as $key=>$url) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
				curl_setopt($ch, CURLOPT_MAXREDIRS, 7);
				curl_multi_add_handle($mh, $ch); 
				$data[$key]=array("error"=>"","errno"=>"","result"=>"");
				$handle[$ch] = array("key"=>$key,"ch"=>$ch);
			}
			do {
				$mrc = curl_multi_exec($mh, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);

			while ($active and $mrc == CURLM_OK) {
				if (curl_multi_select($mh) != -1) {
					do {
						$mrc = curl_multi_exec($mh, $active);
						while($info=curl_multi_info_read($mh)){
							$ch = $info['handle'];
							$key = $handle[$ch]['key'];
							if($info['result']==0){
								$data[$key]['result'] = curl_multi_getcontent($info['handle']);
							}else{
								$data[$key]['errno']=$info['result'];
								$data[$key]['error']=curl_error($info['handle']);
							}
						}
					} while ($mrc == CURLM_CALL_MULTI_PERFORM);
				}
			}
			foreach($handle as $c) {
				curl_multi_remove_handle($mh, $c['ch']);
			}

			curl_multi_close($mh);
			return $data;
		}
	}
	/**
	 * post url
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $cookies
	 * @param boolean $returnHeader
	 * @return string | array
	 */
	function post( $url, $params=array(), $cookies=array(), $returnHeader=false, $timeout=1){
		$content = empty($params)?"":http_build_query($params);
		$opts = array(
			'http'=>array(
				'method' => 'POST',
				'timeout'=>$timeout,
				'header' =>
						"Accept-Language: zh-cn\r\n" .
						"User-Agent: Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)\r\n" .
						"Referer: $url\r\n" .
						"Connection: Close\r\n" .
						(!empty($cookies)?"Cookie: ".self::cookie_build($cookies)."\r\n":"").
						"Content-type: application/x-www-form-urlencoded\r\n" . 
						"Content-length: ".strlen($content)."\r\n",
						'content' => $content
			)

		);
		$context = stream_context_create($opts);
		$result =  file_get_contents($url,false,$context);
		if($returnHeader){
			return array("result"=>$result,"header"=>$http_response_header);
		}else{
			return $result;
		}

	}
	function cookie_parse( $header ) {
			$cookies = array();
			foreach( $header as $line ) {
					if(	preg_match_all ("/Set-Cookie: (.+?)=(.+?);/i", $line, $_match,PREG_SET_ORDER)){
						$cdata=array();
						$key = $_match[0][1];
						$value =$_match[0][2];
						$csplit = explode( ';', substr($line,strpos($line,$value)+strlen($value)) );
						foreach( $csplit as $data ) {
								$cinfo = explode( '=', $data ,2);
								if(count($cinfo)<2)continue;

								$key2 =  trim($cinfo[0]);
								$value2 = $cinfo[1];
								if($key==$key2)continue;
								
								if( in_array( strtolower($key2), array( 'domain', 'expires', 'path', 'secure', 'comment' ) ) ) {
									$key2 = strtolower($key2);
										
									if( $key2 == 'expires' ) $value2 = strtotime( $value2 );
									if( $key2 == 'secure' ) $value2 = "true";
								}
								$cdata[$key2]=$value2;
								
						}
						$cdata['value']['key'] = $key;
						$cdata['value']['value'] = $value;

						$cookies[$key] = $cdata;
					}
			}
			return $cookies;
	}

	function cookie_build( $data ) {
			if( is_array( $data ) ) {
					$cookie = '';
					foreach( $data as $d ) {
						if(!empty($d['expires']) && $d['expires']<time()){continue;}
						$cookie[] = $d['value']['key'].'='.$d['value']['value'];
					}
					if( count( $cookie ) > 0 ) {
							return trim( implode( '; ', $cookie ) );
					}
			}
			return false;
	}

}
?>
