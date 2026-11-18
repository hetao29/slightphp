# Error 使用手册与示例

运行示例：`/lab/plugin/entry/error`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。实验显式调用错误或异常处理器，将真实输出写入临时日志后读取；不制造未捕获异常、不终止页面、不将格式化的 HTML 日志作为页面 HTML 执行。

## 启动时配置

```php
use SlightPHP\Error;

Error::$CONSOLE = false;
Error::$LOG = true;
Error::$LOGARGS = false;
Error::$LOGTYPE = 'json';
Error::$LOGFILE = '/var/log/myapp/error.log';
```

首次自动加载此类会注册 PHP error handler、exception handler 和 shutdown 回调。在应用启动阶段决定是否启用，不应仅为查询类的存在而在任意业务位置触发加载。与已有错误处理框架集成时，明确各处理器的注册顺序和职责。

## 输出策略

LOG 控制是否记录，LOGFILE 为空则使用 PHP 的默认 error_log 目标，指定文件时目录必须可写。LOGTYPE 支持 text、html、json；空值按 CLI / Web 环境选择默认格式。CONSOLE 控制是否同时输出到当前响应，生产 Web 通常为 false。

`LOGARGS` 默认 false，减少堆栈参数中敏感数据泄漏。开启后，插件会对数组中部分密码、secret、token 等键脱敏，但位置参数、未知字段或任意字符串不能依赖自动识别；应优先保持关闭。访问日志的权限与保留时间由应用安排。

## 显式调用与异常响应

```php
Error::error_handler(E_USER_NOTICE, 'sample notice', 'example.php', 1);
try {
    throw new RuntimeException('example exception');
} catch (Throwable $e) {
    Error::exception_handler($e); // 记录已捕获异常；应用继续决定 HTTP 响应。
}
```

错误处理遵循 error_reporting 掩码。Error 不替代控制器的响应设计，不能仅凭异常处理器记录日志就认为已经发送了正确的 HTTP 500。业务入口仍应捕获 `Throwable` 并通过 Response 设置状态和安全的错误正文，详见[异常指南](exceptions.md)。

实验首次加载后会恢复原错误/异常处理器，临时覆盖日志配置，并在 finally 恢复设置、删除临时文件；本实验新注册的 shutdown 回调关闭日志输出。示例显示的异常路径会替换仓库根路径。

## 验证结果

依次选择 NOTICE / RuntimeException 与 JSON / text / HTML，查看相应格式的实际日志；HTML 格式显示为文本。输入含标签的消息不会执行脚本，记录过程不会使页面失去正常响应。没有任何输入需要真实生产错误或敏感参数。
