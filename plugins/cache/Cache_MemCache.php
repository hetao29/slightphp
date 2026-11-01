<?php

/**
 * @package SlightPHP
 * @subpackage SCache
 */
namespace SlightPHP;
/** 保存缓存值及写入时间，供依赖键失效判断使用。 */
final class Cache_MemcacheObject{
	/** @var mixed 缓存值。 */
	var $v;
	/** @var int 写入时的 Unix 时间戳。 */
	var $t;
	/** @param mixed $value 缓存值。 */
	function __construct($value){
		$this->v = $value;
		$this->t = time();
	}
}
if(class_exists("Memcached",false)){
	/** 基于 Memcached 扩展的服务器及基础缓存操作。 */
	class Cache_MemcacheEngine{
		private $_memcache;
		/** 创建 Memcached 客户端并设置一致性哈希和连接超时。 */
		public function __construct(){
			$this->_memcache = new \Memcached();
			$this->_memcache->setOption(\Memcached::OPT_DISTRIBUTION,\Memcached::DISTRIBUTION_CONSISTENT);
			$this->_memcache->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE,true); 
			$this->_memcache->setOption(\Memcached::OPT_CONNECT_TIMEOUT,1000);
		}
		/** 析构客户端。 */
		public function __destruct(){
		}

		/**
		 * 添加服务器。
		 * @param string $host 主机名。
		 * @param int $port 端口。
		 * @param int $weight 一致性哈希权重。
		 * @param int|float $timeout 连接超时秒数。
		 * @return void
		 */
		public function addServer($host,$port=11211,$weight=10,$timeout=1){
			$this->_memcache->setOption(\Memcached::OPT_CONNECT_TIMEOUT,$timeout*1000);
			$this->_memcache->addServer($host,$port,$weight);
		}
		/**
		 * @param array|object $hosts 服务器配置对象或对象数组，字段为 host、port、weight。
		 * @return void
		 */
		public function addServers($hosts=array()){
			$realhost=array();
			if(is_array($hosts)){
				foreach($hosts as $host){
					if(isset($host->host))
						$realhost[]=array($host->host,
								isset($host->port)?$host->port:11211,
								isset($host->weight)?$host->weight:10
								);
				}
			}elseif(is_object($hosts)){
				$host = $hosts;
				if(isset($host->host))
					$realhost[]=array($host->host,
							isset($host->port)?$host->port:11211,
							isset($host->weight)?$host->weight:10
							);
			}
			$this->_memcache->addServers($realhost);
		}
		/**
		 * @param string|array $keys 缓存键；数组时当前实现只删除第一个键。
		 * @return bool|null 删除结果；空数组返回 null。
		 */
		public function del($keys){
			if(is_array($keys)){
				foreach($keys as $key){
					return $this->del($key);
				}
			}else{
				return $this->_memcache->delete($keys);
			}
		}
		/**
		 * @param string|array $keys 缓存键或键数组。
		 * @return mixed 单键值或多键结果；失败时由扩展返回 false。
		 */
		public function get($keys){
			if(is_array($keys)){
				return $this->_memcache->getMulti($keys);
			}else{
				return $this->_memcache->get($keys);
			}
		}
		/**
		 * @param string $key 缓存键。
		 * @param mixed $value 缓存值。
		 * @param int $expire Memcached 过期参数，0 表示不过期。
		 * @return bool 是否保存成功。
		 */
		public function set($key,$value,$expire=0){
			return $this->_memcache->set($key,$value,$expire);
		}
	}
}else{
	/** 基于旧版 Memcache 扩展的服务器及基础缓存操作。 */
	class Cache_MemcacheEngine extends \Memcache{
		private $_memcache;
		/** 创建 Memcache 客户端并设置哈希策略。 */
		public function __construct(){
			$this->_memcache = new \Memcache();
			ini_set("memcache.hash_strategy","consistent");
			ini_set("memcache.hash_function","crc32");
		}
		/** 关闭 Memcache 连接。 */
		public function __destruct(){
			$this->_memcache->close();
		}
		/**
		 * 添加服务器。
		 * @param string $host 主机名。
		 * @param int $port 端口。
		 * @param int $weight 哈希权重；非正数时使用 10。
		 * @param int|float $timeout 连接超时秒数；非正数时使用 1。
		 * @return void
		 */
		public function addServer($host,$port=11211,$weight=10,$timeout=1){
			$this->_memcache->addServer($host,$port,false,$weight>0?$weight:10,$timeout>0?$timeout:1);
		}
		/**
		 * @param array|object $hosts 服务器配置对象或对象数组，字段为 host、port、weight、timeout。
		 * @return void
		 */
		public function addServers($hosts=array()){
			if(is_array($hosts)){
				foreach($hosts as $host){
					if(isset($host->host))
						$this->addServer($host->host,
								isset($host->port)?$host->port:11211,
								isset($host->weight)?$host->weight:10,
								isset($host->timeout)?$host->timeout:1
							       );
				}
			}elseif(is_object($hosts)){
				$host = $hosts;
				if(isset($host->host))
					$this->addServer($host->host,
							isset($host->port)?$host->port:11211,
							isset($host->weight)?$host->weight:10,
							isset($host->timeout)?$host->timeout:1
						       );
			}

		}
		/**
		 * @param string|array $keys 缓存键；数组时当前实现只删除第一个键。
		 * @return bool|null 删除结果；空数组返回 null。
		 */
		public function del($keys){
			if(is_array($keys)){
				foreach($keys as $key){
					return $this->del($key);
				}
			}else{
				return $this->_memcache->delete($keys);
			}
		}
		/**
		 * @param string|array $keys 缓存键或键数组。
		 * @return mixed 扩展返回的缓存值或多键结果。
		 */
		public function get($keys){
			return $this->_memcache->get($keys);
		}
		/**
		 * @param string $key 缓存键。
		 * @param mixed $value 缓存值。
		 * @param int $expire Memcache 过期参数，0 表示不过期。
		 * @return bool 是否保存成功。
		 */
		public function set($key,$value,$expire=0){
			return $this->_memcache->set($key,$value,0,$expire);
		}
	}
}
/** Memcache 缓存封装，支持以其他缓存键作为失效依赖。 */
class Cache_Memcache extends Cache_MemcacheEngine{

	/**
	 * @var int 1 表示所有依赖必须有效，其他值表示至少一个依赖有效。
	 */
	private $mode = 1;
	/** 初始化底层 Memcache 客户端。 */
	public function __construct(){
		parent::__construct();
	}
	
	/**
	 * 根据配置添加单台服务器；只有 host 非空时才添加。
	 * @param array $params 包含 host、port、timeout、weight 的配置。
	 *                      未指定 port 时传给扩展的值为 0。
	 * @return void
	 */
	function init($params=array()){
		if(!empty($params['host'])){
			parent::addServer(
				$params['host'],
				isset($params['port'])?$params['port']:0,
				isset($params['weight'])?$params['weight']:1,
				isset($params['timeout'])?$params['timeout']:1
			);
		}
	}
	/**
	 * @param int $mode 1 要求所有依赖有效；其他值要求至少一个依赖有效。
	 * @return void
	 */
	function setMode($mode){
		$this->mode=$mode;
	}

	/**
	 * 读取缓存；单个依赖键缺失或其更新时间晚于缓存时，该依赖无效。
	 * mode 为 1 时要求所有依赖有效，其他模式要求至少一个有效。
	 * 写入时间按秒记录，同一秒内更新的依赖不会使缓存失效。
	 * @param string|array $key 缓存键或键数组。
	 * @param string|array|null $depKeys 依赖键或键数组。
	 * @return mixed 单个值或键到值的数组；未命中以 false 表示。
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
					if($this->mode==1){
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
	/**
	 * 保存带写入时间的缓存值；底层写入结果不会返回给调用方。
	 * @param string $key 缓存键。
	 * @param mixed $value 缓存值。
	 * @param int $exp 扩展的过期参数，0 表示不过期。
	 * @return void
	 */
	function set($key,$value,$exp=0){
		$v = new Cache_MemcacheObject($value);
		parent::set($key,$v,$exp);
	}
}
