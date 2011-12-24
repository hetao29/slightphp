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
