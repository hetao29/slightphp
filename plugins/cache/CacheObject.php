<?php

/**
 * @package SlightPHP
 * @subpackage SCache
 */
namespace SlightPHP;
/** 文件和 APC 缓存实现的基础接口。 */
abstract class CacheObject{
	/**
	 * 初始化缓存引擎。
	 * @param array $params 引擎配置。
	 * @return void
	 */
	abstract function init($params=array());
	/**
	 * 保存缓存。
	 * @param string $key 缓存键。
	 * @param mixed $value 缓存值。
	 * @param int $timestamp 有效期秒数，-1 表示不过期。
	 * @return bool 是否保存成功。
	 */
	abstract function set($key,$value,$timestamp=-1);
	/**
	 * 读取缓存值。
	 * @param string $key 缓存键。
	 * @return mixed 缓存值；未命中时为 false。
	 */
	abstract function get($key);
	/**
	 * 删除缓存值。
	 * @param string $key 缓存键。
	 * @return bool 是否删除成功；不存在时的行为取决于引擎。
	 */
	abstract function del($key);

}
