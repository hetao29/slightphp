# 插件使用指南

核心不会自动加载插件。Composer 安装后可按自动加载配置使用插件；直接加载仓库核心时需要自行 require 插件及其依赖。旧插件的运行条件各不相同，不应假设所有插件都与当前 PHP 版本完全兼容。

## SConfig

```php
require 'plugins/SConfig.php';
SConfig::$CACHE = false; // 开发时禁用进程内及临时文件缓存
$config = SConfig::getConfig('samples/config/example.conf', 'application');
echo $config->name;
```

支持 `key value;`、分组 `{}`、行首 `#` 注释、数字、布尔及重复键（转数组）。读取不存在的分组会回退 `default` 分组，否则返回 null。默认缓存包含进程内结果和系统临时目录文件；常驻进程修改配置后需要考虑重启或禁用缓存。配置文件应来自可信路径。

## SJson

封装 `json_encode` / `json_decode`，支持对应 flags、depth。默认编码失败返回 false，解码失败可能返回 null，应检查 `SJson::error()` / `errorMsg()`，或使用 `JSON_THROW_ON_ERROR`。响应头由应用设置。

```php
header('Content-Type: application/json; charset=utf-8');
echo SJson::encode(['message' => '你好'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
```

## SRoute

需在 `run()` 前设置。它会匹配服务器 PATH_INFO 或 REQUEST_URI，再调用核心的 setPathInfo，并非独立 HTTP 服务。

```php
SRoute::set([
    'pattern' => '^\/tasks(?:\?.*)?$',
    'zone' => 'workspace',
    'page' => 'main',
    'entry' => 'entry',
]);
SlightPHP::run();
```

pattern 内容不带外围正则分隔符，插件内部使用 `/.../sm`，斜杠必须转义。配置可由 `setConfigFile()` 读取 INI。`:name` 占位符由同名键提供正则，捕获组会依次附加为路径段；避免无关捕获组。按注册顺序取第一个匹配规则。默认 `/index.php/...` 入口路径参与匹配时应调整正则；新示例使用原生路径，未启用 SRoute。

## STpl

```php
STpl::setTemplateDir(__DIR__ . '/templates');
STpl::setCompileDir(__DIR__ . '/var/templates_c');
$tpl = new STpl();
echo $tpl->render('index.tpl', ['title' => '示例']);
```

模板及编译目录由应用管理，编译目录必须可写且不应公开访问。当前实现中 `render()` 与 `display()` 均直接输出，返回值为 null（内部 fetch 使用 include），不要依赖其返回 HTML 字符串；还有 `setForceCompile()`、`setSafeMode()` 和历史拼写 `setDelimter()`。安全模式不能替代输出转义，不允许上传不可信模板。更详细语法见 [原 STpl 文档](../wiki/STpl.md)。新工作台通过 STpl 渲染 `head.tpl`、`index/*.tpl` 和 `footer.tpl`；使用 `{%`、`%}` 分隔符，保持默认安全模式。

## 数据库、缓存与其他插件

| 插件 | 用途 | 启用前需要 |
| --- | --- | --- |
| SDb | 表操作、查询与读写配置 | PDO / 对应驱动、数据库、db.conf；见 [SDb](../wiki/SDb.md) |
| SRedis | Redis 缓存 | Redis PHP 扩展、服务和连接配置 |
| SCache | Memcache / 文件 / APC 缓存 | 对应扩展或可写缓存目录；文件后端也需检查旧代码兼容性 |
| SLog | 分级日志 | 可写日志路径；避免将调试输出混入 JSON 响应 |
| SError | 错误处理 | 明确线上错误输出策略 |
| SLanguage | 多语言词条 | locale 文件与语言选择策略 |
| SCaptcha / SThumbnail | 验证码、缩略图 | GD、字体 / 图片资源 |
| SHttp | HTTP 客户端 | Composer 依赖、网络和超时策略 |
| SMail / SSphinx / SLunar / SUtil | 邮件、搜索、农历、工具 | 按具体实现检查运行依赖 |

使用数据库时先了解条件构造与参数绑定，不要直接拼接用户输入。历史示例的服务地址与配置仅是样本，部署前替换为自己的配置，并将密钥置于 Web 根目录之外。
