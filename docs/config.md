# Config 使用手册与示例

运行示例：`/lab/plugin/entry/config`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)、[JSONC 配置](../samples/config/example.jsonc)。入口已引入 Composer 自动加载器。

## 读取配置

```php
use SlightPHP\Config;

$path = __DIR__ . '/config/settings.jsonc';
$all = Config::getConfig($path);
$application = Config::getConfig($path, 'application');
$fallback = Config::getConfig($path, 'missing');
```

JSON / JSONC 文件必须来自可信的服务端路径，不能让访客任意选择文件。返回值保留 JSON 类型：对象为 `stdClass`，数组为 PHP 数组，数字、布尔和 null 保持其类型。对象属性用 `$application->name` 访问。

```jsonc
{
    // 行注释、块注释都支持；字符串内的 // 会保留。
    "application": {"name": "SlightPHP", "debug": false},
    "default": {"name": "fallback"}
}
```

JSONC 不支持尾随逗号。普通 `.json` 按严格 JSON 解析。

## zone 与缓存

不传 zone 时返回完整值。传入非空 zone 时，先取对应的非 null 属性；未找到或为 null 则回退到 `default`，没有 default 返回 null。zone 使用 PHP 真值判断，`''`、`'0'` 与 null 一样会返回完整配置。

`Config::$CACHE` 默认 true，在当前进程内按真实文件路径与扩展名缓存解析结果。开发时可在读取前设为 false；关闭缓存不负责清除之前已存的条目。常驻进程中修改配置后，需要应用安排重新加载或重启进程。

## 异常与验证

不支持的扩展名抛 `InvalidArgumentException`，文件不可读抛 `RuntimeException`，JSON 语法错误抛 `JsonException`。应用应在入口捕获并记录内部错误，不能将配置全文或凭据发给访客。

示例选择 JSONC 的 application 可看到对象；选择 missing 返回 default。切换到 JSON 并选择 missing，结果为 null；选择“完整配置”返回整个文件。示例只读取两个固定文件，不接受任意路径。

更多插件见[插件指南](plugins.md)，所有交互入口见[示例说明](samples.md)。
