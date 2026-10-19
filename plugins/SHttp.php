<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * @package SlightPHP
 */
class SHttp{

	private static $_instance = null;
	public static function getInstance(){
		if (is_null(self::$_instance)) {
			self::$_instance = new \GuzzleHttp\Client();
		}
		return self::$_instance;
	}
	/**
	 * get url
	 *
	 * @param string $url
	 * @param array $params
	 * @param array $cookies
	 * @param boolean $returnHeader
	 * @return string | array
	 */

	public static function get( $url, $params=array(), $cookies=array(), $returnHeader=false, $timeout=5, $headers=array()){
		$client = self::getInstance();
		$request =[
			'timeout' => $timeout
		];
		if(!empty($params)){
			$request['query']=$params;
		}
		if(!empty($cookies)){
			$domain = parse_url($url, PHP_URL_HOST);
			$jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookies, $domain);
			$request['cookies'] = $jar;
		}
		if(!empty($headers)){
			$request['headers'] = $headers;
		}
		$res = $client->request('GET', $url, $request);
		$body = (string)$res->getBody();
		if($returnHeader){
			return array("result"=>$body,"header"=>$res->getHeaders());
		}else{
			return $body;
		}
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
	public static function post( $url, $params=array(), $cookies=array(), $returnHeader=false, $timeout=5, $headers=array()){
		$client = self::getInstance();
		$request =[
			'timeout' => $timeout
		];
		if(is_array($params) || is_object($params)){
			$request['json']=$params;
		}else{
			$request['body']=$params;
		}
		if(!empty($cookies)){
			$domain = parse_url($url, PHP_URL_HOST);
			$jar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookies, $domain);
			$request['cookies'] = $jar;
		}
		if(!empty($headers)){
			$request['headers'] = $headers;
		}
		$res = $client->request('POST', $url, $request);
		$body = (string)$res->getBody();
		if($returnHeader){
			return array("result"=>$body,"header"=>$res->getHeaders());
		}else{
			return $body;
		}
	}
}
