<?php

namespace SlightPHP;

use RedisArray;
use RedisCluster;
use RedisException;

/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 */
/** phpredis 客户端封装，支持通过配置选择 RedisArray 或 RedisCluster。 */
class Redis
{
	private static $hosts = array();
	private static $options = array();
	private static $engine = "";
	/**
	 * @var string $_configFile global redis config file
	 */
	private static $_configFile;
	/**
	 * @var RedisArray|RedisCluster 当前使用的客户端。
	 */
	private $_redis;
	/**
	 * 创建 Redis 客户端。cluster 使用传入的主机和选项；其他模式同样使用传入的主机和选项。
	 * sentinel 分支同样创建 RedisArray，不会创建单独的 Sentinel 客户端。
	 * @param array $hosts 集群节点地址列表。
	 * @param array $options 客户端选项，如 timeout、read_timeout、persistent、auth 和 context。
	 * @param string $engine 为 cluster 时创建 RedisCluster，否则创建 RedisArray。
	 */
	public function __construct(array $hosts, array $options, $engine = "")
	{
		if ($engine == "cluster") {
			//https://github.com/phpredis/phpredis/blob/develop/cluster.md
			$this->_redis = new RedisCluster(
				NULL,
				$hosts,
				$options['timeout'] ?? 0,
				$options['read_timeout'] ?? 0,
				$options['persistent'] ?? false,
				$options['auth'] ?? NULL,
				$options['context'] ?? NULL
			);
		} elseif ($engine == "sentinel") {
			//https://github.com/phpredis/phpredis/blob/develop/sentinel.md
			$this->_redis = new RedisArray($hosts, $options);
		} else {
			//https://github.com/phpredis/phpredis/blob/develop/arrays.md
			$this->_redis = new RedisArray($hosts, $options);
		}
		return $this->_redis;
	}

	/**
	 * @param string $file Redis 配置文件路径。
	 * @return void
	 */
	static function setConfigFile($file)
	{
		self::$_configFile = $file;
	}
	/**
	 * 获取指定类型的 Redis 配置，缺失时回退到 host，包括请求 engine 和 options 时。
	 * @param string|null $zone 配置区域。
	 * @param string $type 配置项名，如 host、engine 或 options。
	 * @return mixed 配置值，缺失时为 null。
	 */
	static function getConfig($zone = null, $type = "host")
	{
		$config = Config::getConfig(self::$_configFile, $zone);
		if (isset($config->$type)) {
			return $config->$type;
		} elseif (isset($config->host)) {
			return $config->host;
		}
		return NULL;
	}
	/**
	 * 读取配置并创建客户端，同时设置静态调用使用的配置。
	 * options 仅在配置值为对象时转为数组；其他类型会被忽略。
	 * @param string $zone 配置区域。
	 * @param string $type 主机配置项名，默认 host。
	 * @return Redis|false 客户端，或缺少主机配置时为 false。
	 */
	static function useConfig($zone, $type = "host")
	{
		$config = self::getConfig($zone, $type);
		if (empty($config)) {
			trigger_error("the redis hosts is not set in config file(" . self::$_configFile . ")");
			return false;
		}
		self::$hosts = [];
		self::$options = [];
		if (is_array($config)) {
			self::$hosts = $config;
		} else {
			self::$hosts[] = $config;
		}
		self::$engine = self::getConfig($zone, "engine");
		$config = self::getConfig($zone, "options");
		if (!empty($config)) {
			if (is_object($config)) {
				foreach ($config as $k => $v) {
					self::$options[$k] = $v;
				}
			}
		}
		return new self(self::$hosts, self::$options, self::$engine);
	}
	/**
	 * 将调用转发给当前 Redis 客户端。
	 * @param string $name 方法名。
	 * @param array $args 方法参数。
	 * @return mixed 客户端返回值；捕获 RedisException 时为 false。
	 */
	public function __call($name, $args)
	{
		try {
			return call_user_func_array(array($this->_redis, $name), $args);
		} catch (RedisException $e) {
			trigger_error($e);
		}
		return false;
	}
	/**
	 * 使用静态配置创建客户端并转发调用。
	 * @param string $name 方法名。
	 * @param array $args 方法参数。
	 * @return mixed 客户端返回值；捕获 RedisException 时为 false。
	 */
	public static function __callStatic($name, $args)
	{
		try {
			$redis = new self(self::$hosts, self::$options, self::$engine);
			return call_user_func_array(array($redis, $name), $args);
		} catch (RedisException $e) {
			trigger_error($e);
		}
		return false;
	}
}
