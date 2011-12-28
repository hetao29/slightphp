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
 * @subpackage SCache
 */
final class Cache_MemcacheObject{
	var $v;
	var $t;
	function __construct($value){
		$this->v = $value;
		$this->t = time();
	}
}
class Cache_Memcache{
	static private $_connects	=	array();
	static private $_data		=	array();
	static private $_servers	=	array();
	static private $_serverCt	=	0;

	/**
	 * var $localCache
	 */
	static $localCache=true;

	/**
	 * int $mode
	 */
	static $mode = 1;
	
	/**
	 * @param array['host']
	 * @param array['port']
	 * @param array['timeout']
	 * @param array['weight']
	 */
	function init($params=array()){
		if(!empty($params['host'])){
			self::addServer(
				$params['host'],
				isset($params['port'])?$params['port']:0,
				isset($params['weight'])?$params['weight']:1,
				isset($params['timeout'])?$params['timeout']:1
			);
		}
	}
	/**
	 * @param array $servers
	 */
	static function addServers($servers){
		self::$_servers=array();
		foreach($servers as $server) self::init($server);
	}
	/**
	 * @param string $host
	 * @param int $port
	 * @param int $weight=1
	 * @param int $timeout=1
	 */
	/**
	 * @param int $mode
	 */
	static function setMode($mode){
		self::$mode=$mode;
	}
	/**
	 * @param bool $cache
	 */
	static function setLocalCache($cache){
		self::$localCache=$cache;
	}

	/**
	 * @param string $key
	 * @param string|array  $depKeys
	 * @return mixed
	 */
	static function get($key,$depKeys=null){
		$keys = array($key);
		if(!empty($depKeys)){
			if(is_string($depKeys))$depKeys=array($depKeys);
			if(is_array($depKeys)) $keys =array_merge($keys,$depKeys);
		}
		$values = self::_get($keys);
		if(!isset($values[$key]) || !($values[$key] instanceof Cache_MemcacheObject))return false;
		$value = $values[$key];unset($values[$key]);
		if(!empty($depKeys)){
			if(self::$mode==1){
				foreach($depKeys as $depKey){
					if(	!isset($values[$depKey]) || 
						!($values[$depKey] instanceof Cache_MemcacheObject) || 
						$values[$depKey]->t>$value->t
					) return false;
				}
			}else{
				foreach($values as $k=>$v){
					if(($v instanceof Cache_MemcacheObject) && $v->t > $value->t)return false;
				}
			};
		}
		return $value->v;

	}
	/**
	 * @param string|array $key
	 * @return bool
	 */
	static function del($key){
		if(is_array($key))foreach($key as $k)return self::delete($k);
		if(self::$localCache && isset(self::$_data[$key]))unset(self::$_data[$key]);
		$memcache_obj = self::_connect(self::getServer($key));
		return memcache_delete($memcache_obj,$key);
	}
	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $exp
	 */
	static function set($key,$value,$exp=0){
		$v = new Cache_MemcacheObject($value);
		if(self::$localCache)self::$_data[$key]=$v;
		$memcache_obj = self::_connect(self::getServer($key));
		return memcache_set($memcache_obj,$key,$v,0,$exp);
	}

	static private function _connect($server){
		$index = implode(":",$server);
		if(!isset(self::$_connects[$index])){
			$memcache_obj = memcache_connect($server['host'], $server['port'],$server['timeout']);
			if($memcache_obj)self::$_connects[$index]=$memcache_obj;
		}
		return self::$_connects[$index];

	}
	static private function _get($keys){
		$servs  = array();
		$values = array();
		foreach($keys as $key){
			if(self::$localCache && isset(self::$_data[$key]) && (self::$_data[$key] instanceof Cache_MemcacheObject)){
				$values[$key]=self::$_data[$key];
			}else{
				$server = self::getServer($key);
				$serverIndex = $server['index'];
				$servs[$serverIndex][]=$key;
			}
		}
		foreach($servs as $serverIndex=>$key){
			$memcache_obj = self::_connect(self::$_servers[$serverIndex]);
			$vars = memcache_get($memcache_obj, $key);
			$values = array_merge($values,$vars);
			if(self::$localCache)self::$_data = array_merge(self::$_data,$vars);
		}
		return $values;
	}
	/**
	 * consistent hashing
	 */
	public static function addServer($host,$port=11211,$weight=1,$timeout=1){
		$weight = $weight*10;
		for($i=1;$i<=$weight;$i++){
			$serverid = self::hash($host.":".$port.":".$i);
			self::$_servers[]=array(
				"host"=>$host,
				"port"=>$port,
				"weight"=>$weight,
				"id"=>$serverid,
				"timeout"=>$timeout,
			);
		}
		usort(self::$_servers,self::_sort);
		self::$_serverCt=count(self::$_servers);
	}
	private static function _sort($a,$b){
		return $a['id']>$b['id'];
	}
	public static function getServer($key){
		$key = self::hash($key);
		$left = 0;
		$right=self::$_serverCt-1;
		$index = 0;
		while($left<$right-1){
			$middle = (int)(($left+$right)/2);
			if($key <= self::$_servers[$left]['id']){
				$index = $left;
				break;
			}
			if($key>= self::$_servers[$right]['id']){
				$index = $right;
				break;
			}
			$t = self::$_servers[$middle]['id'];
			if($key==$t){
				$index = $middle;
			}
			if($key>$t){
				$left = $middle;
				$index = $right;
			}else {
				$right=$middle;
				$index = $middle;
			}
		}
		$server = self::$_servers[$index];
		$server['index'] = $index;
		return $server;
	}
	private static function hash($str){
		return crc32($str);
	}
}
