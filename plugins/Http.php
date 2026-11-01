<?php

namespace SlightPHP;
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */
/**
 * 基于共享 Guzzle 客户端发送简单的 HTTP 请求。
 * @package SlightPHP
 */
class Http{

	/** @var \GuzzleHttp\Client|null */
	private static $_instance = null;
	/** @return \GuzzleHttp\Client 延迟创建的共享客户端。 */
	public static function getInstance(){
		if (is_null(self::$_instance)) {
			self::$_instance = new \GuzzleHttp\Client();
		}
		return self::$_instance;
	}
	/**
	 * 发送 GET 请求。
	 *
	 * @param string $url 请求地址。
	 * @param array $params 查询参数。
	 * @param array $cookies Cookie 名称到值的映射。
	 * @param bool $returnHeader 是否同时返回响应头。
	 * @param int|float $timeout 请求超时时间（秒）。
	 * @param array $headers 请求头。
	 * @return string|array 响应体，或包含 result 和 header 的数组。
	 * @throws \GuzzleHttp\Exception\GuzzleException 请求失败时由 Guzzle 抛出。
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
	 * 发送 POST 请求；数组和对象编码为 JSON，其他值作为请求体。
	 *
	 * @param string $url 请求地址。
	 * @param mixed $params 数组或对象编码为 JSON；其他值作为 Guzzle 请求体。
	 * @param array $cookies Cookie 名称到值的映射。
	 * @param bool $returnHeader 是否同时返回响应头。
	 * @param int|float $timeout 请求超时时间（秒）。
	 * @param array $headers 请求头。
	 * @return string|array 响应体，或包含 result 和 header 的数组。
	 * @throws \GuzzleHttp\Exception\GuzzleException 请求失败时由 Guzzle 抛出。
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
