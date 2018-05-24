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

	public static function get( $url, $params=array(), $cookies=array(), $returnHeader=false, $timeout=5){
		$url .= empty($params)?"":"?".http_build_query($params);
		$ch = curl_init();
		if(!empty($cookies)){
			curl_setopt($ch, CURLOPT_COOKIE, self::cookie_build($cookies));
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Mobile Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if($returnHeader){
			curl_setopt($ch, CURLOPT_HEADER, true);
		}
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($httpcode == '200') {
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($data, 0, $headerSize);
			$body = substr($data, $headerSize);
			if($returnHeader){
				return array("result"=>$body,"header"=>$header);
			}else{
				return $body;
			}
		}
		return false;
	}
	/**
	 * get multi urls
	 *
	 * @param array $url_array=array("url"=>"","parameters"=>array(),"timeout"=>1,"method"=>"get");
	 * @param int $timeout=1
	 * @return string | array
	 */
	public static function getArray($url_array,$timeout = 5) {
		if (!is_array($url_array))
			return false;
		$data    = array();
		$handle  = array();
		$running = 0;
		$mh = curl_multi_init();
		$i = 0;
		foreach($url_array as $key=>$value) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, isset($value['timeout'])?$value['timeout']:$timeout);
			curl_setopt($ch, CURLOPT_USERAGENT, 'RESTful Request');
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 302 redirect
			curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
			if(!isset($value['method'])){
				$value['method']="GET";
			}
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $value['method']);
			if(strcasecmp($value['method'],"POST")==0){
				curl_setopt($ch, CURLOPT_POST, true);
				if(!empty($value['parameters'])){
					curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($value['parameters']));
				}
				curl_setopt($ch, CURLOPT_URL, $value['url']);
			}else{
				if(!empty($value['parameters'])){
					$url = $value['url']."?".http_build_query($value['parameters']);
				}else{
					$url = $value['url'];
				}
				curl_setopt($ch, CURLOPT_URL, $url);
			}
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
	/**
	 * post url
	 *
	 * @param string $url
	 * @param array | string $params
	 * @param array $cookies
	 * @param boolean $returnHeader
	 * @return string | array
	 */
	public static function post( $url, $params=array(), $cookies=array(), $returnHeader=false, $timeout=5){
		if(is_array($params)){
			$content = empty($params)?"":http_build_query($params);
		}else{
			$content=$params;
		}
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$content);
		if(!empty($cookies)){
			curl_setopt($ch, CURLOPT_COOKIE, self::cookie_build($cookies));
		}
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 302 redirect
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Mobile Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		if($returnHeader){
			curl_setopt($ch, CURLOPT_HEADER, true);
		}
		$data = curl_exec($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($httpcode == '200') {
			$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($data, 0, $headerSize);
			$body = substr($data, $headerSize);
			if($returnHeader){
				return array("result"=>$body,"header"=>$header);
			}else{
				return $body;
			}
		}
		return false;
	}
	public static function cookie_parse( $header ) {
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

	public static function cookie_build( $data ) {
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
