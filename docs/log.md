# Log 使用手册与示例

运行示例：`/lab/plugin/entry/log`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。选择级别与格式后提交，页面展示真实写入的日志文本；临时文件随后删除，不写入应用正式日志。

## 配置与写入

```php
use SlightPHP\Log;

Log::$LOGFILE = '/var/log/myapp/application.log';
Log::$LOGTYPE = 'json';
Log::$CONSOLE = false;
Log::notice('order.created', ['order_id' => 123]);
Log::warning('delivery.retry', ['attempt' => 2]);
Log::error('delivery.failed');
```

日志目录须预先创建，并允许 PHP 进程追加文件。LOGFILE 为空时使用 PHP `error_log()` 的默认目标。文件写入不自动创建目录、不做轮转；轮转与保留时间由部署环境处理。

`LOGTYPE = 'json'` 输出每行一个 JSON 对象，字段为 `time`、`level`、`message`；message 是调用参数组成的数组。其他格式值使用文本格式。用户输入、数组和对象不应预先拼成 HTML。

| 方法 | 日志级别 |
| --- | --- |
| debug、trace | DEBUG |
| notice | NOTICE |
| warning | WARNING |
| error | ERR |
| fatal | CRIT |
| write | 不带级别前缀 |

这些方法不抛业务异常，也不因 error / fatal 名称而终止程序。`Log::$LOG` 是保留属性，当前写入流程不读取它，不能用它关闭写日志。需要开关或级别过滤时，在应用调用层控制。

## 控制台与敏感内容

`CONSOLE = true` 会额外输出到当前响应：CLI 输出文本，Web 输出经过处理的 HTML 注释。生产 Web 入口通常设为 false，避免日志混入正文。Log 不自动隐藏密码、token 或完整业务对象；调用者只传入必要且已筛选的信息。

示例可尝试包含 `<script>`、引号或换行的消息，JSON 仍是一条记录，页面按文本显示。默认 NOTICE 的级别字段为 NOTICE，选择 error 为 ERR；日志输出成功后页面仍正常返回。

PHP 错误与异常的自动记录见 [Error 手册](error.md)。
