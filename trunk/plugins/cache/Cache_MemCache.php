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
if(class_exists("Memcached")){
	class Cache_MemcacheEngine{
		static private $_memcache;
		public function __construct(){
			if(!self::$_memcache){
				self::$_memcache = new Memcached();
				self::$_memcache->setOption(Memcached::OPT_DISTRIBUTION,Memcached::DISTRIBUTION_CONSISTENT);
				self::$_memcache->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE,true); 
				self::$_memcache->setOption(Memcached::OPT_CONNECT_TIMEOUT,1000);
			}
		}
		public function addServer($host,$port=11211,$weight=10,$timeout=1){
			self::$_memcache->setOption(Memcached::OPT_CONNECT_TIMEOUT,$timeout*1000);
			return self::$_memcache->addServer($host,$port,$weight);
		}
		public function addServers($hosts=array()){
			$realhost=array();
			if(is_array($hosts)){
				foreach($hosts as $host){
					$realhost[]=array($host->host,
							isset($host->port)?$host->port:11211,
							isset($host->weight)?$host->weight:10
							);
				}
			}elseif(is_object($hosts)){
				$host = $hosts;
				$realhost[]=array($host->host,
						isset($host->port)?$host->port:11211,
						isset($host->weight)?$host->weight:10
						);
			}
			return self::$_memcache->addServers($realhost);
		}
		public function del($keys){
			if(is_array($keys)){
				foreach($keys as $key){
					return self::del($key);
				}
			}else{
				return self::$_memcache->delete($keys);
			}
		}
		public function get($keys){
			if(is_array($keys)){
				return self::$_memcache->getMulti($keys);
			}else{
				return self::$_memcache->get($keys);
			}
		}
		public function set($key,$value,$expire=0){
			return self::$_memcache->set($key,$value,$expire);
		}
	}
}else{
	class Cache_MemcacheEngine extends Memcache{
		static private $_memcache;
		public function __construct(){
			if(!self::$_memcache){
				self::$_memcache = new Memcache();
				ini_set("memcache.hash_strategy","consistent");
				ini_set("memcache.hash_function","crc32");
			}
		}
		public function addServer($host,$port=11211,$weight=10,$timeout=1){
			return self::$_memcache->addServer($host,$port,true,$weight>0?$weight:10,$timeout>0?$timeout:1);
		}
		public function addServers($hosts=array()){
			if(is_array($hosts)){
				foreach($hosts as $host){
					return self::addServer($host->host,
							isset($host->port)?$host->port:11211,
							isset($host->weight)?$host->weight:10,
							isset($host->timeout)?$host->timeout:1
							);
				}
			}elseif(is_object($hosts)){
				$host = $hosts;
				return self::addServer($host->host,
						isset($host->port)?$host->port:11211,
						isset($host->weight)?$host->weight:10,
						isset($host->timeout)?$host->timeout:1
						);
			}

		}
		public function del($keys){
			if(is_array($keys)){
				foreach($keys as $key){
					return self::del($key);
				}
			}else{
				return self::$_memcache->delete($keys);
			}
		}
		public function get($keys){
			return self::$_memcache->get($keys);
		}
		public function set($key,$value,$expire=0){
			return self::$_memcache->set($key,$value,0,$expire);
		}
	}
}
class Cache_Memcache extends Cache_MemcacheEngine{

	/**
	 * int $mode
	 */
	static $mode = 1;
	public function __construct(){
		parent::__construct();
	}
	
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
	 * @param int $mode
	 */
	function setMode($mode){
		self::$mode=$mode;
	}

	/**
	 * @param string|array $key
	 * @param string|array $depKeys
	 * @return mixed
	 */
	function get($key,$depKeys=null){
		$return_type=1;
		if(is_array($key)){
			$return_keys = $key;
			$return_type=2;
		}else{
			$return_keys = array($key);
		}
		$keys=array();
		if(!empty($depKeys)){
			if(is_string($depKeys))$depKeys=array($depKeys);
			if(is_array($depKeys)) $keys =array_merge($return_keys,$depKeys);
		}else{
			$keys = $return_keys;
		}
		$values = parent::get($keys);
		$result=array();
		foreach($return_keys as $key){
			if(!isset($values[$key]) || !($values[$key] instanceof Cache_MemcacheObject)){
				$result[$key]=false;continue;
			}else{
				$value = $values[$key];
				if(!empty($depKeys)){
					if(self::$mode==1){
						$flag=true;
						foreach($depKeys as $depKey){
							if(	!isset($values[$depKey]) || 
								!($values[$depKey] instanceof Cache_MemcacheObject) || 
								$values[$depKey]->t>$value->t
							){
								$flag=false;break;
							}
						}
						if($flag===false){
							$result[$key]=false;continue;
						}
					}else{
						$flag=false;
						foreach($depKeys as $depKey){
							if(	isset($values[$depKey]) && 
								($values[$depKey] instanceof Cache_MemcacheObject) &&
								$values[$depKey]->t<=$value->t
							){
								$flag=true;break;
							}
						}
						if($flag===false){
							$result[$key]=false;continue;
						}
					};
				}
			}
			$result[$key]=$value->v;
		}
		if(empty($result))return false;
		if($return_type==1)return array_shift($result);
		return $result;
	}
	function set($key,$value,$exp=0){
		$v = new Cache_MemcacheObject($value);
		parent::set($key,$v,$exp);
	}
}
