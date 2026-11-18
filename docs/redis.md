# Redis 使用手册与示例

运行示例：`/lab/plugin/entry/redis`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。实验使用真实 Redis，按 SETEX → GET → TTL → DEL → GET 执行，并在 finally 再次清理随机示例键。

## 准备服务

应用声明并安装 `ext-redis`。复制 [services.example.json](../samples/config/services.example.json) 为被忽略的 `samples/config/services.local.json`，填写 redis 的 hosts、options、engine，将 enabled 改为 true。也可使用 `SLIGHTPHP_SAMPLE_SERVICES` 指向 Web 根目录之外的私有 JSON 文件。

使用独立的实验 Redis；不接受访客提供服务器地址、命令或键名。扩展已加载不代表服务已连接；未配置时页面提示准备服务。

## 实例与过期键

```php
use SlightPHP\Redis;

$redis = new Redis(['127.0.0.1:6379'], [
    'connect_timeout' => 1, 'read_timeout' => 1,
]);
$key = 'slightphp:sample:' . bin2hex(random_bytes(12));
try {
    if (!$redis->setex($key, 30, 'hello')) throw new RuntimeException('SETEX failed');
    $value = $redis->get($key);
    $ttl = $redis->ttl($key);
    $deleted = $redis->del($key);
    $missing = $redis->get($key); // 默认无序列化配置时，键不存在返回 false。
} finally {
    $redis->del($key);
}
```

默认 engine 使用 phpredis `RedisArray`，hosts 为节点地址数组，options 遵循 RedisArray 选项。engine 为 `cluster` 时创建 RedisCluster，可设置 timeout、read_timeout、persistent、auth、context；不要混用不同客户端的配置字段。

## Sentinel 与文件配置

```php
$redis = new Redis(['127.0.0.1:26379'], [
    'service' => 'mymaster', 'timeout' => 1, 'read_timeout' => 1,
    // 'sentinel_auth' => ['sentinel-user', 'private-password'],
    // 'auth' => ['data-user', 'private-password'],
    'database' => 0,
], 'sentinel');
```

Sentinel 模式需要 phpredis 6.0+，先发现并用 ROLE 验证主节点，数据节点与 Sentinel 的认证分别配置。切主时应用仍需处理当前操作失败；插件不会自动重放已经失败的写命令。

`Redis::setConfigFile($file)` 配合 `Redis::useConfig($zone)` 可读取框架 Redis 配置并设置静态调用所用的配置；配置结构见[插件指南](plugins.md#redis)。在线实验直接创建实例，不修改全局静态连接设置。

代理命令可能返回 false，也可能产生 PHP 警告或连接异常；应用需要同时处理错误，不要仅凭构造完成认定所有操作成功。

## 验证结果

输入 hello 后 value 应为 hello，ttl 在 1–30 秒内，deleted 为 1，after_delete 为 false。各次运行生成不同的 `slightphp:sample:` 键，即使请求中断也会在 30 秒后过期。服务不可达时显示失败提示，不输出凭据或驱动原始消息。
