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
/** phpredis 客户端封装，支持通过配置选择 RedisArray、RedisCluster 或 Sentinel 主节点。 */
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
	 * @var \Redis|RedisArray|RedisCluster|null 当前使用的客户端。
	 */
	private $_redis;
	/** @var string[] Sentinel 节点列表，连接成功的节点优先用于下次发现。 */
	private array $sentinelHosts = [];
	/** @var array Sentinel 服务与连接参数。 */
	private array $sentinelOptions = [];
	/**
	 * 创建 Redis 客户端。sentinel 查询服务名并连接经 ROLE 验证的主节点。
	 * @param array $hosts 数据节点地址列表；sentinel 模式时为 Sentinel 节点地址。
	 * @param array $options 客户端选项；sentinel 必须提供 service，支持独立 sentinel_auth、auth 及 database。
	 * @param string $engine cluster、sentinel，或其他值（RedisArray）。
	 * @throws \InvalidArgumentException Sentinel 配置缺失或节点格式非法。
	 * @throws \RuntimeException Sentinel 模式缺少 phpredis 6.0+。
	 * @throws RedisException 无法发现或连接主节点。
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
			if (!class_exists('RedisSentinel') || version_compare((string) phpversion('redis'), '6.0', '<')) {
				throw new \RuntimeException('Sentinel mode requires phpredis 6.0 or later');
			}
			if ($hosts === [] || !is_string($options['service'] ?? null) || trim($options['service']) === '') {
				throw new \InvalidArgumentException('Sentinel hosts and service are required');
			}
			$this->sentinelHosts = array_values($hosts);
			$this->sentinelOptions = self::normalizeSentinelOptions($options);
			// 校验全部地址，配置错误不应被当作临时节点故障忽略。
			foreach ($this->sentinelHosts as $host) self::sentinelAddress($host);
			$this->_redis = $this->connectSentinel();
		} else {
			//https://github.com/phpredis/phpredis/blob/develop/arrays.md
			$this->_redis = new RedisArray($hosts, $options);
		}
		return $this->_redis;
	}

	/** 将 JSON 配置中的嵌套对象转换为 phpredis 所需的数组。 */
	private static function normalizeSentinelOptions(array $options): array
	{
		foreach ($options as $key => $value) {
			if ($value instanceof \stdClass) $value = get_object_vars($value);
			$options[$key] = is_array($value) ? self::normalizeSentinelOptions($value) : $value;
		}
		return $options;
	}

	/** @return array{string,int} 支持 host[:port]、[IPv6]:port 和 tls://host:port。 */
	private static function sentinelAddress($address): array
	{
		if (!is_string($address) || $address === '') {
			throw new \InvalidArgumentException('Invalid Sentinel address');
		}
		$parts = parse_url(str_contains($address, '://') ? $address : 'tcp://' . $address);
		if ($parts === false || empty($parts['host']) || !in_array($parts['scheme'] ?? '', ['tcp', 'tls'], true)
			|| isset($parts['user']) || isset($parts['pass']) || isset($parts['path']) || isset($parts['query']) || isset($parts['fragment'])
			|| ($parts['port'] ?? 26379) < 1) {
			throw new \InvalidArgumentException('Invalid Sentinel address');
		}
		return [($parts['scheme'] === 'tls' ? 'tls://' : '') . $parts['host'], $parts['port'] ?? 26379];
	}

	/** 发现并验证主节点；仅建立连接，不重放任何业务命令。 */
	private function connectSentinel(): \Redis
	{
		$options = $this->sentinelOptions;
		$service = $options['service'];
		$answered = false;
		$found = false;
		$lastError = null;
		foreach ($this->sentinelHosts as $index => $endpoint) {
			$redis = null;
			try {
				[$host, $port] = self::sentinelAddress($endpoint);
				$sentinel = new \RedisSentinel(array_filter([
					'host' => $host, 'port' => $port,
					'connectTimeout' => $options['sentinel_timeout'] ?? 0.5,
					'readTimeout' => $options['sentinel_read_timeout'] ?? 0.5,
					'auth' => $options['sentinel_auth'] ?? null,
					'ssl' => str_starts_with($host, 'tls://') ? ($options['sentinel_context']['stream'] ?? $options['context']['stream'] ?? []) : null,
				], static fn($value) => $value !== null));
				$master = $sentinel->getMasterAddrByName($service);
				$answered = true;
				if (!is_array($master) || count($master) !== 2 || !is_string($master[0]) || $master[0] === ''
					|| !preg_match('/\A[0-9]+\z/', (string) $master[1]) || (int) $master[1] < 1 || (int) $master[1] > 65535) continue;
				$found = true;
				$redis = new \Redis();
				$connect = !empty($options['persistent']) ? 'pconnect' : 'connect';
				$target = (!empty($options['tls']) ? 'tls://' : '') . $master[0];
				$persistentId = 'slightphp-sentinel-' . hash('sha256', serialize($options));
				if (!$redis->$connect($target, (int) $master[1], $options['timeout'] ?? 1,
					$persistentId, 0, $options['read_timeout'] ?? 1, $options['context'] ?? null)) {
					throw new RedisException('Cannot connect to Sentinel master');
				}
				// 禁止 phpredis 在旧地址透明重连，故障后应重新通过 Sentinel 发现。
				$redis->setOption(\Redis::OPT_MAX_RETRIES, 0);
				if (isset($options['auth']) && !$redis->auth($options['auth'])) {
					throw new RedisException('Cannot authenticate with Sentinel master');
				}
				$role = $redis->role();
				if (!is_array($role) || ($role[0] ?? null) !== 'master') {
					throw new RedisException('Sentinel target is not a master');
				}
				if (!$redis->select($options['database'] ?? 0)) {
					throw new RedisException('Cannot select Sentinel master database');
				}
				unset($this->sentinelHosts[$index]);
				array_unshift($this->sentinelHosts, $endpoint);
				return $redis;
			} catch (RedisException $error) {
				$lastError = $error;
				try { $redis?->close(); } catch (RedisException $ignored) {}
			}
		}
		$message = !$answered ? 'Redis Sentinels are unreachable for service: '
			: ($found ? 'No usable master found for Sentinel service: ' : 'Sentinel service not found: ');
		throw new RedisException($message . $service, 0, $lastError);
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
			if ($this->sentinelHosts !== [] && ($this->_redis === null || !$this->_redis->isConnected())) {
				$this->_redis = $this->connectSentinel();
			}
			$result = call_user_func_array(array($this->_redis, $name), $args);
			if ($this->sentinelHosts !== [] && $result === false && str_starts_with((string) $this->_redis->getLastError(), 'READONLY')) {
				$this->_redis->close();
				$this->_redis = null;
			}
			return $result;
		} catch (RedisException $e) {
			if ($this->sentinelHosts !== []) {
				try { $this->_redis?->close(); } catch (RedisException $ignored) {}
				$this->_redis = null;
			}
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
