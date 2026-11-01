<?php
/**
 * @package SlightPHP
 * @subpackage SCache
 */
namespace SlightPHP;
/** 将序列化的缓存值保存到按键哈希分层的文件中。 */
class Cache_File extends CacheObject{
	/**
	 * @var string 缓存文件根目录。
	 */
	var $dir="cache";
	/**
	 * @var int 缓存文件的哈希目录层数。
	 */
	var $depth=3;

	/**
	 * 设置缓存目录和目录分层深度。
	 *
	 * @param array $params 可包含 dir 和 depth 属性的配置。
	 * @return void
	 */
	function init($params=array()){
		foreach($params as $key=>$value){
			$this->$key = $value;
		}
	}
	/**
	 * 将序列化值写入缓存文件。
	 *
	 * @param string $key 缓存键。
	 * @param mixed $value 缓存值。
	 * @param int $timestamp 有效期秒数；-1 表示不过期。
	 * @return bool 目录创建或文件打开失败时为 false；写入后返回 true，但不检查写入结果。
	 */
	function set($key,$value,$timestamp=-1){
		$realFile = $this->_getDir($key,true);
		if(!$realFile){
			return false;
		}
		$timeout = $timestamp==-1?-1:(time()+$timestamp);

		
		$fp=fopen($realFile,"w");
		if(!$fp){return false;}
		flock($fp,LOCK_EX);
		$content  =$timeout."\r\n".serialize($value);
        	fputs($fp,$content);
		flock($fp,LOCK_UN);
        	fclose($fp);
		return true;
	}
	/**
	 * 读取未过期的缓存值。
	 *
	 * @param string $key 缓存键。
	 * @return mixed 缓存值；未命中、过期或读取失败时为 false。
	 */
	function get($key){
		$realFile = $this->_getDir($key);
		if(!$realFile || !is_file($realFile)){
			return false;
		}
		$fp=fopen($realFile,"r");
		if(!$fp){return false;}
		flock($fp,LOCK_SH);
		$timeout =trim(fgets($fp));
		if(!empty($timeout)){
			$timenow =time();
			if($timeout ==-1 || $timenow < $timeout){
				$data = fread($fp,filesize($realFile));
				flock($fp,LOCK_UN);
				fclose($fp);
				return unserialize($data);
			}
		}
		flock($fp,LOCK_UN);
		fclose($fp);
		return false;
	}
	/**
	 * 删除缓存文件。
	 *
	 * @param string $key 缓存键。
	 * @return bool 文件不存在或删除成功时为 true。
	 */
	function del($key){
		$realFile = $this->_getDir($key);
		if(!$realFile || !is_file($realFile)){
			return true;
		}
		return unlink($realFile);
	}
	/**
	 * 根据键生成缓存文件路径，并可按需创建父目录。
	 * @param string $key 缓存键。
	 * @param bool $mk 是否创建目录。
	 * @return string|false 文件路径，或创建目录失败时的 false。
	 */
	private function _getDir($key,$mk=false){
		$str = md5($key);
		$step = ceil(strlen($str)/($this->depth+1));
		$tmp = array();
		for($i=0;$i<=$this->depth;$i++){
			$path = substr($str, $i*$step,$step);
			if($path)$tmp[]= $path;
		}
		$realFile = $this->dir.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$tmp).".data";
		if($mk){
			$dirname = dirname($realFile);
			if(!is_dir($dirname)){
				if(!mkdir($dirname,0777,true)){
					return false;
				}
			}
		}
		return $realFile;
	}
}
