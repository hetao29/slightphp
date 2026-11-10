# 插件使用指南

运行 `composer install` 后，在应用入口引入 `vendor/autoload.php`。插件由 Composer 按需加载，使用 `SlightPHP\` 命名空间；6.x 不提供旧版全局类名。模板辅助函数由 Composer 自动注册。各插件的运行条件不同，请按需准备扩展和服务。

插件包括 `Captcha`、`Config`、`Db`、`Error`、`Http`、`Json`、`Language`、`Log`、`Redis`、`Route` 和 `Tpl`。`SlightPHP\Db` 提供表操作、查询和事务接口。

## Config

```php
require 'vendor/autoload.php';
use SlightPHP\Config;
Config::$CACHE = false; // 开发时禁用进程内缓存
$config = Config::getConfig('samples/config/example.json', 'application');
echo $config->name;
```

仅支持 `.json` 和 `.jsonc`（扩展名不区分大小写），不再兼容 `.conf` 等旧配置格式。旧配置中的分组应迁移为 JSON 对象，重复键应迁移为数组。其他扩展名会抛出 `InvalidArgumentException`；文件不存在或无法读取时抛出 `RuntimeException`。

`.json` 直接按严格 JSON 解码，不处理注释；`.jsonc` 额外允许 `//` 行注释和 `/* ... */` 块注释，字符串内的注释符号会保留。两种格式均不允许尾随逗号。JSON 对象解码为 `stdClass`，数组和标量保留对应类型；解码失败（包括格式、编码或深度错误）及 JSONC 块注释未结束时抛出 `JsonException`。原来含注释的 `.json` 文件需改名为 `.jsonc`。

例如 `config.jsonc`：

```jsonc
{
  // 应用配置
  "application": { "name": "demo", "url": "https://example.com" },
  /* 找不到指定区域时使用 */
  "default": { "name": "fallback" }
}
```

```php
$application = Config::getConfig('config.jsonc', 'application');
echo $application->name; // demo
$all = Config::parse('config.jsonc'); // 完整解码结果
```

`getConfig()` 不指定区域时返回完整解析结果；指定区域时按顶层对象属性读取，区域不存在或值为 `null` 时回退到非 `null` 的 `default` 属性，否则返回 `null`。区域名为空字符串或字符串 `"0"` 时也返回完整结果。顶层 JSON 数组或标量可通过不指定区域读取。

默认仅保留进程内缓存，按配置文件的规范路径及格式复用解析结果；不再读写或反序列化系统临时目录中的文件缓存，旧文件缓存不会再被使用。缓存命中时不会检查文件修改时间。`Config::$CACHE = false` 会绕过缓存，但不会清空已有结果，重新启用时可能再次读到旧值；常驻进程修改配置后可保持缓存关闭或重启进程。配置文件应来自可信路径。

## Error

首次加载 `SlightPHP\Error` 时会注册错误处理器、异常处理器和关闭回调。普通错误处理及关闭回调补记的最后一条错误都遵循调用时的 `error_reporting()` 掩码；即使被屏蔽的错误仍留在 `error_get_last()` 中，关闭回调也不会将其输出或写入日志。未捕获异常仍由异常处理器记录。

```php
error_reporting(error_reporting() & ~(E_DEPRECATED | E_USER_DEPRECATED));
\SlightPHP\Error::$CONSOLE = false;
\SlightPHP\Error::$LOG = true;
\SlightPHP\Error::$LOGARGS = false; // 默认不记录调用参数
\SlightPHP\Error::$LOGTYPE = 'text';
```

`Error::$LOGARGS` 默认为 `false`，HTML、text、json 格式的日志和控制台输出均不记录调用栈参数，仍保留文件、行号、类名、方法名及错误消息。普通错误采集调用栈时也会忽略参数。显式设置为 `true` 后恢复参数记录，并沿用数组字段名脱敏及对象转类名的规则；位置参数及未命中字段名的敏感值仍可能被记录。此开关不清除错误或异常消息本身包含的数据。

HTML 格式会转义错误消息、文件名、调用方法和参数中的 HTML 字符，保留错误表格；引号使用 `ENT_QUOTES`，非法 UTF-8 使用替代字符。text、json 格式写入日志文件和输出到 CLI 时保持原内容。开启 `CONSOLE` 向网页输出时，仅在响应已声明 `text/plain`、`application/json` 或 `application/*+json` 时保留原格式；其他类型（含默认 `text/html` 和未指定类型）会将错误内容作为 HTML 文本转义，避免消息中的标签被执行。插件不修改响应头；独立 JSON 或纯文本接口应由应用设置正确的 `Content-Type`。

## Log

`Log::$CONSOLE = true` 时，CLI 输出纯文本，网页响应输出 HTML 注释。网页中的日志内容会进行 HTML 转义，避免标签注入或 `-->` 提前闭合注释；写入日志文件的内容保持原样。

`LOGTYPE` 默认是 `text`，保留多行文本。`json` 将每次调用编码为一个完整 JSON 对象，每行一条记录；消息中的换行、回车及控制字符会被 JSON 编码，不会变成额外记录。这是 JSON Lines 格式，整个多条记录文件不是单个 JSON 数组：

```php
\SlightPHP\Log::$LOGTYPE = 'json';
\SlightPHP\Log::$LOGFILE = __DIR__ . '/app.json';
\SlightPHP\Log::warning("第一行\n第二行", ['user_id' => 123]);
```

```json
{"time":"2026-11-09T18:00:00+08:00","level":"WARNING","message":["第一行\n第二行",{"user_id":123}]}
```

`time` 为带时区的 ISO 8601 时间，`level` 沿用原级别名称，`write()` 的级别为空字符串。`message` 始终为调用参数数组，保留可编码值的类型。非法 UTF-8 使用替代字符；循环引用、资源等无法完整编码的数据按 `JSON_PARTIAL_OUTPUT_ON_ERROR` 规则部分输出。指定 `LOGFILE` 的文件与 CLI 控制台每条记录以一个换行结束；网页控制台继续使用转义后的 HTML 注释。`LOGFILE` 为空时交给 PHP 默认日志系统，其附加前缀由运行环境决定。

兼容性：`json` 模式已从旧的“文本前缀 + 部分参数 JSON 编码”升级为整条记录 JSON 编码。依赖旧日志格式的解析程序需要调整；应为新格式使用单独日志文件或先轮转旧文件，避免混合格式。

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

源文件经 `realpath()` 解析后必须位于模板目录内；编译文件和其各级子目录也必须位于编译目录内，指向目录外的符号链接会被拒绝。保留原有 CRC32 子目录和修改时间缓存逻辑。新建编译目录及子目录默认权限为 `0700`（受 umask 影响），已有目录权限不自动修改。`compile_dir` 及其 CRC32 子目录均不检查归属或权限，允许将 `compile_dir` 设为 `/tmp`。

`{%$value|escape%}` 使用 `ENT_QUOTES | ENT_SUBSTITUTE` 和 UTF-8 进行 HTML 转义，覆盖单双引号、尖括号和 `&`，非法 UTF-8 使用替代字符，不依赖 PHP 默认选项或 `default_charset`。此修饰器用于 HTML 文本和带引号的普通属性值；URL 编码分支仍使用 `rawurlencode()`。

不检查编译文件的所属用户或权限。新建或重新写入编译文件时，将文件权限设置为 `0600`；命中已有缓存时不修改其权限。目录需要执行位以访问内部文件，因此新建目录默认使用 `0700`，不使用 `0600`。实际读写仍受操作系统权限限制，编译目录及其父级应由可信用户管理。

## Captcha

先运行 `composer install`，通过 `vendor/autoload.php` 加载插件和 `gregwar/captcha`。依赖需要 GD、mbstring 和 fileinfo；插件在会话未启动时会启动会话。图片端点应在输出其他内容之前调用：

```php
require 'vendor/autoload.php';
$captcha = new \SlightPHP\Captcha();
$captcha->CreateImage(); // 输出 PNG 并将验证码保存到会话
```

提交表单时，在同一活动会话中调用 `\SlightPHP\Captcha::check($code)`。同一会话、同一前缀仅保存最新验证码，刷新图片立即替换旧记录。验证码默认有效期为 300 秒，可在生成前通过 `Captcha::$ttl` 设置正整数秒数；到期及异常记录在检查时移除并返回 `false`。成功校验后立即移除，重复提交返回 `false`；错误输入保留当前未过期验证码，空值或非字符串输入返回 `false`。不要在校验前调用 `session_write_close()`，也不要在同一业务流程中重复调用 `check()`。原有 `del($code)` 可以继续调用，也可用于主动作废尚未使用的验证码。默认图片为 140×50、文本长度为 4–5；可在生成前修改 `width`、`height`、`minWordLength`、`maxWordLength` 和 `imageFormat`。父类现为 `Gregwar\Captcha\CaptchaBuilder`，旧版 `SimpleCaptcha` 的其他属性和方法不再提供。

Session 中的记录现在包含 `code` 和 `expires_at`，存放于 `session_prefix` 对应的固定键中；旧的“前缀 + 验证码”记录不再接受，升级后需重新获取验证码。不同用途可使用不同前缀隔离；生成图片时必须有活动会话。

并发请求下的一次性校验依赖会话后端提供互斥锁。PHP files 会话后端会锁定同一会话；使用 Redis 等后端时需确认其会话锁支持与配置，不能将仅修改当前请求的 `$_SESSION` 视为分布式原子操作。PhpRedis 对 RedisCluster 的会话锁存在限制，见[官方说明](https://github.com/phpredis/phpredis#session-locking)。

## Language

`Language::tr($source, $zone = 'main')` 从 `languageDir/locale/zone.ini` 查找词条，未找到时返回原文。`Accept-Language` 仅接受由 1–8 个字母开头、后接连字符及 1–8 个字母或数字子标签组成的语言标签，例如 `en`、`zh-CN`、`zh-Hans-CN`；标签不区分大小写。非法项、通配符、非法 q 值及 `q=0` 项会被忽略，合法项按原有出现顺序查找，并保留主语言回退和 `defaultLocale` 优先规则，不按 q 值排序。

请求头最多处理 8192 字节、前 32 个条目，包含 CR、LF、NUL 或不是字符串时整个请求头被忽略。不会通过 URL 解码或删除危险字符把非法标签转换成路径。`setLocale()` 的强制语言也进行格式检查，并兼容 `en_US` 这样的下划线写法；`zone` 仅允许字母、数字、下划线开头，后续可含点或连字符。语言文件经 `realpath()` 解析后必须位于语言根目录内，指向目录外的符号链接不会读取。

语言列表及翻译缓存仍保留原有进程内行为，常驻进程需隔离请求状态；修改语言目录或强制语言不会自动清空已经初始化的结果。

## 数据库、Redis 与其他插件

| 插件 | 用途 | 启用前需要 |
| --- | --- | --- |
| Db | 表操作、查询与读写配置 | PDO / 对应驱动、数据库、JSON/JSONC 连接配置；见 [数据库 API](index.html#SlightPHP%5CDb) |
| Redis | Redis 缓存 | Redis PHP 扩展、服务和连接配置 |
| Log | 分级日志 | 可写日志路径；避免将调试输出混入 JSON 响应 |
| Error | 错误处理 | 明确线上错误输出策略 |
| Language | 多语言词条 | locale 文件与语言选择策略 |
| Captcha | 验证码 | Composer 的 gregwar/captcha、GD、mbstring、fileinfo 和会话 |
| Http | HTTP 客户端 | Composer 依赖、网络和超时策略 |

使用数据库时先了解条件构造与参数绑定，不要直接拼接用户输入。历史示例的服务地址与配置仅是样本，部署前替换为自己的配置，并将密钥置于 Web 根目录之外。

### 数据库失败处理

`Db::query($sql, $params = [])` 只执行一次 SQL，失败保留原错误并返回 `false`，框架不自动重连或重试。第三个重试次数参数及重连逻辑已移除；旧调用中的 `query($sql, $params, 1)`、`query($sql, $params, false)` 都应迁移为两个参数。

`select`、`selectOne`、增删改等沿用同样的失败行为。连接断开后由调用方决定是否重新获取连接、重新初始化会话或重试整个任务；事务、锁及会话状态不能通过重连自动恢复。写入结果不确定时先核对实际结果，不能只重放失败的 SQL。`error()` 保留原有错误协议，成功时不保证清空旧错误。
