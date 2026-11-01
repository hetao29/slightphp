# 插件使用指南

运行 `composer install` 后，在应用入口引入 `vendor/autoload.php`。插件由 Composer 按需加载，使用 `SlightPHP\` 命名空间；6.0 不提供旧版全局类名。模板辅助函数由 Composer 自动注册。各插件的运行条件不同，请按需准备扩展和服务。

插件包括 `Captcha`、`Config`、`Db`、`Error`、`Http`、`Json`、`Language`、`Log`、`Redis`、`Route` 和 `Tpl`。`SlightPHP\Db` 提供表操作、查询和事务接口。

## Config

```php
require 'vendor/autoload.php';
use SlightPHP\Config;
Config::$CACHE = false; // 开发时禁用进程内及临时文件缓存
$config = Config::getConfig('samples/config/example.conf', 'application');
echo $config->name;
```

支持 `key value;`、分组 `{}`、行首 `#` 注释、数字、布尔及重复键（转数组）。读取不存在的分组会回退 `default` 分组，否则返回 null。默认缓存包含进程内结果和系统临时目录文件；常驻进程修改配置后需要考虑重启或禁用缓存。配置文件应来自可信路径。

## Json

封装 `json_encode` / `json_decode`，支持对应 flags、depth。默认编码失败返回 false，解码失败可能返回 null，应检查 `Json::error()` / `errorMsg()`，或使用 `JSON_THROW_ON_ERROR`。响应头由应用设置。

```php
header('Content-Type: application/json; charset=utf-8');
echo \SlightPHP\Json::encode(['message' => '你好'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
```

## Route

需在 `run()` 前设置。它会匹配服务器 PATH_INFO 或 REQUEST_URI，再调用核心的 setPathInfo，并非独立 HTTP 服务。

```php
\SlightPHP\Route::set([
    'pattern' => '^\/tasks(?:\?.*)?$',
    'zone' => 'workspace',
    'page' => 'main',
    'entry' => 'entry',
]);
SlightPHP::run();
```

pattern 内容不带外围正则分隔符，插件内部使用 `/.../sm`，斜杠必须转义。配置可由 `setConfigFile()` 读取 INI。`:name` 占位符由同名键提供正则，捕获组会依次附加为路径段；避免无关捕获组。按注册顺序取第一个匹配规则。默认 `/index.php/...` 入口路径参与匹配时应调整正则；新示例使用原生路径，未启用 Route。

## Tpl

```php
\SlightPHP\Tpl::setTemplateDir(__DIR__ . '/templates');
\SlightPHP\Tpl::setCompileDir(__DIR__ . '/var/templates_c');
$tpl = new \SlightPHP\Tpl();
$tpl->render('index.tpl', ['title' => '示例']);
```

模板及编译目录由应用管理，编译目录必须可写且不应公开访问。当前实现中 `render()` 与 `display()` 均直接输出，返回值为 null（内部 fetch 使用 include），不要依赖其返回 HTML 字符串；还有 `setForceCompile()`、`setSafeMode()` 和历史拼写 `setDelimter()`。安全模式不能替代输出转义，不允许上传不可信模板。模板语法和调用方式应以当前源码及 API 为准。新工作台通过 Tpl 渲染 `head.tpl`、`index/*.tpl` 和 `footer.tpl`；使用 `{%`、`%}` 分隔符，保持默认安全模式。

## Captcha

先运行 `composer install`，通过 `vendor/autoload.php` 加载插件和 `gregwar/captcha`。依赖需要 GD、mbstring 和 fileinfo；插件在会话未启动时会启动会话。图片端点应在输出其他内容之前调用：

```php
require 'vendor/autoload.php';
$captcha = new \SlightPHP\Captcha();
$captcha->CreateImage(); // 输出 PNG 并将验证码保存到会话
```

提交表单时，使用同一会话调用 `\SlightPHP\Captcha::check($code)`，验证完成后可用 `\SlightPHP\Captcha::del($code)` 删除该验证码。默认图片为 140×50、文本长度为 4–5；可在生成前修改 `width`、`height`、`minWordLength`、`maxWordLength` 和 `imageFormat`。父类现为 `Gregwar\Captcha\CaptchaBuilder`，旧版 `SimpleCaptcha` 的其他属性和方法不再提供。

## 数据库、Redis 与其他插件

| 插件 | 用途 | 启用前需要 |
| --- | --- | --- |
| Db | 表操作、查询与读写配置 | PDO / 对应驱动、数据库、db.conf；见 [数据库 API](index.html#SlightPHP%5CDb) |
| Redis | Redis 缓存 | Redis PHP 扩展、服务和连接配置 |
| Log | 分级日志 | 可写日志路径；避免将调试输出混入 JSON 响应 |
| Error | 错误处理 | 明确线上错误输出策略 |
| Language | 多语言词条 | locale 文件与语言选择策略 |
| Captcha | 验证码 | Composer 的 gregwar/captcha、GD、mbstring、fileinfo 和会话 |
| Http | HTTP 客户端 | Composer 依赖、网络和超时策略 |

使用数据库时先了解条件构造与参数绑定，不要直接拼接用户输入。历史示例的服务地址与配置仅是样本，部署前替换为自己的配置，并将密钥置于 Web 根目录之外。
