# 插件使用指南

运行 `composer install` 后，在应用入口引入 `vendor/autoload.php`。插件由 Composer 按需加载，使用 `SlightPHP\` 命名空间；6.x 不提供旧版全局类名。模板辅助函数由 Composer 自动注册。各插件的运行条件不同，请按需准备扩展和服务。

插件包括 `Captcha`、`Config`、`Csrf`、`Db`、`Error`、`Http`、`Language`、`Log`、`Redis`、`Route` 和 `Tpl`。`SlightPHP\Db` 提供表操作、查询和事务接口。

## 示例与独立手册

官网插件页的每张卡片均提供运行示例与使用手册。Tpl 使用已有模板组合实验，其余实验通过真实插件调用展示结果。

| 插件 | 运行地址 | 手册 |
| --- | --- | --- |
| Tpl | `/lab/template/entry` | [模板与模块](templates.md) |
| Language | `/lab/language/entry` | [Language 手册](language.md) |
| Config | `/lab/plugin/entry/config` | [Config 手册](config.md) |
| Log | `/lab/plugin/entry/log` | [Log 手册](log.md) |
| Db | `/lab/plugin/entry/db` | [Db 手册](db.md) |
| Redis | `/lab/plugin/entry/redis` | [Redis 手册](redis.md) |
| Http | `/lab/plugin/entry/http` | [Http 手册](http-client.md) |
| Error | `/lab/plugin/entry/error` | [Error 手册](error.md) |
| Captcha | `/lab/plugin/entry/captcha` | [Captcha 手册](captcha.md) |
| Request | `/lab/plugin/entry/request` | [Request 手册](request.md) |
| Response | `/lab/plugin/entry/response` | [Response 手册](response.md) |

Db / Redis 需要管理员准备独立服务，在 `samples/config/services.local.json` 启用；模板位于 [services.example.json](../samples/config/services.example.json)。私有配置不提交，缺少依赖或配置时页面显示运行条件，不使用模拟结果。Http 默认调用固定 httpbin 服务。

## 按需安装依赖

框架的 `require` 仅声明 PHP >=8.0，插件依赖放在 `suggest` 中。`suggest` 只提供安装提示，不会自动安装包或扩展，也不会强制校验版本。使用对应插件的应用应将所需依赖加入自己的 `composer.json` 的 `require`，让 Composer 检查部署环境。

| 插件 | 应用需要声明或安装的依赖 |
| --- | --- |
| Db | `ext-pdo`、`ext-pdo_mysql`，并准备 MySQL 服务和连接配置 |
| Redis | `ext-redis`，支持 RedisArray、RedisCluster 或 Sentinel（需 phpredis 6.0+），并准备服务和连接配置 |
| Http | `guzzlehttp/guzzle:^7.0 || ^8.0` |
| Captcha | `gregwar/captcha:^1.3 || ^2.0`、`ext-session`；GD、mbstring、fileinfo 由验证码包声明并检查 |
| Csrf | `ext-session`；应用在使用前启动并配置 Session |

例如，在应用项目目录执行所需命令：

```bash
# 使用 Http 插件
composer require 'guzzlehttp/guzzle:^7.0 || ^8.0'

# 使用 Captcha 插件
composer require 'gregwar/captcha:^1.3 || ^2.0' 'ext-session:*'

# 使用当前 MySQL 数据库插件
composer require 'ext-pdo:*' 'ext-pdo_mysql:*'

# 使用 Redis 插件
composer require 'ext-redis:*'
```

`ext-*` 声明用于检查 PHP 扩展，Composer 不负责安装系统扩展。可选包根据应用的 PHP 环境解析版本，最终运行要求以应用的锁文件为准。加载 `vendor/autoload.php` 不会主动加载这些插件；调用插件前应先安装其依赖，尤其是继承第三方类的 Captcha。

从旧版本升级时，应用若使用上述插件，应先显式声明所需依赖，再更新框架，避免依赖被作为无用包移除。

## Csrf

表单、Ajax、自定义字段和 token 更新实验位于 `/lab/csrf/entry`，接入步骤和预期结果见 [CSRF 使用手册](csrf.md)。

`SlightPHP\Csrf` 提供基于 Session 的同步 token，并由 `#[Route(csrf: true)]` 启用入口检查；Csrf 本身不是注解。应用先配置安全的 Session Cookie 并调用 `session_start()`；插件不自动启动会话、不发送响应头，也不修改其他会话数据。Session 配置见[部署说明](deployment.md#cookie与会话安全)。

| API | 行为 |
| --- | --- |
| `Csrf::token(): string` | 获取当前会话 token；缺失或存储格式无效时生成。32 随机字节编码为 64 位小写十六进制字符串，同一会话复用 |
| `Csrf::validate(Request $request, ?string $fieldName = null): void` | 校验提交值，成功正常返回，失败抛异常；不生成、更新或消费 token |
| `Csrf::tokenInput(?string $fieldName = null): string` | 返回含当前 token 的 hidden input；null 使用 `$fieldName`（默认 `_csrf`），字符串指定字段名，HTML 属性已转义；Tpl 用末尾 `|raw` 输出 |
| `Csrf::rotate(): string` | 生成并保存新 token，旧 token 立即失效 |
| `Csrf::clear(): void` | 仅清除 token，后续 `token()` 才重新生成 |

公共静态变量 `Csrf::$fieldName`（默认 `_csrf`）与 `Csrf::$sessionKey`（默认 `_slightphp_csrf`）可由应用入口统一配置；Session 键不能为空。默认请求头仍为 `X-CSRF-Token`，Route 字符串可覆盖当前入口的字段与同名头。所有 API 都要求 Session 处于活动状态；未启动、已关闭或缺少 session 扩展时抛出 `SlightPHP\Exception::CSRF_SESSION_NOT_STARTED`，建议状态为 500。读取或写入 token 应在 `session_write_close()` 之前完成。

控制器示例（Session 已由应用入口启动）：

```php
namespace User;

use SlightPHP\Csrf;
use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;
use SlightPHP\Tpl;

class ProfileController {
    #[Route(method: 'GET')]
    public function Edit(Request $request, Response $response) {
        (new Tpl())->render('profile.tpl', ['csrf' => Csrf::token()]);
    }

    #[Route(method: 'POST', csrf: true)]
    public function Save(Request $request, Response $response) {
        // CSRF 已通过；继续执行身份、权限和业务字段校验。
        return $response->redirect('/user/profile/edit', 303);
    }
}
```

普通表单显式提交 `_csrf`，模板使用带引号的属性：

```html
<form method="post" action="/user/profile/save">
    <input type="hidden" name="_csrf" value="{$csrf}">
    <button type="submit">保存</button>
</form>
```

表单也可以传入 `Csrf::tokenInput()` 的结果并写 `{$csrf_input|raw}`，或直接写 `{\SlightPHP\Csrf::tokenInput()|raw}`，后者无需预先传入变量。具体示例见 [CSRF 手册](csrf.md#3-普通表单)。

`Route` 的 `csrf` 参数也可为字符串，例如 `#[Route(method: 'POST', csrf: '--csrf--token')]`，此时表单字段和请求头都使用 `--csrf--token`，不再读取默认名称。`getCsrfField()` 返回该名称，`isCsrfRequired()` 为 true。名称允许非空 ASCII 字母、数字、下划线及横线；请求头不区分大小写，下划线按 Request 规则归一化为横线。手动校验可调用 `Csrf::validate($request, fieldName: '--csrf--token')`，生成字段可调用 `Csrf::tokenInput('--csrf--token')`。

使用默认配置时，Ajax/JSON 请求通过 `X-CSRF-Token` 请求头提交从当前会话页面取得的 token。该头存在时优先使用，即使为空或无效也不回退到表单；不存在时才读取 `$request->post['_csrf']`。不读取 GET、Cookie、`$request->request` 或 JSON 正文字段，不修剪或转换提交值。缺失、空值、数组、非字符串或不匹配的 token 均抛出 `CSRF_INVALID`（建议 403），校验采用 `hash_equals()`。Session 中没有有效 token 时同样拒绝，不在校验过程中补发。

只有 Route 中将 csrf 设置为 true 或自定义名称字符串的入口会由调度器自动校验；直接调用普通 PHP 方法需要自行调用 `Csrf::validate($request)`。注解不按 HTTP 方法跳过：即使请求为 GET、HEAD、OPTIONS 或 CLI 也必须通过 token 校验；写入口应同时通过 Route 的 `method` 或 `methods` 限制 HTTP 方法。实际入口、继承/覆盖、Request-Type 和内部调度规则见[核心调度](framework.md#route-的-csrf-校验)。

校验失败只抛异常，不输出正文、不修改状态码、不继续候选回退；异常消息与上下文不包含 token。应用入口按 `getStatusCode()` 生成响应，见[异常处理](exceptions.md)。应用在登录、退出或身份切换时更新或清除 token，并自行维护 Session 生命周期；普通提交不自动更新，方便多标签页使用。CSRF 防护不替代权限、输出转义或防重复提交，SameSite Cookie 也不替代 token 校验。常驻进程不缓存用户 token，应用必须保证各请求 Session 的隔离；PHP 全局 Session 不适合交错并发请求。

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

## JSON 使用原生函数

从 6.5.1 起移除 `SlightPHP\Json`，直接使用 PHP 原生函数；原调用的参数与 flags、depth 可保持不变。迁移对应关系见 [6.5 迁移指南](../CHANGELOG.md#65-json-插件移除)。默认编码失败返回 false，解码失败可能返回 null，应检查 `json_last_error()` / `json_last_error_msg()`，或使用 `JSON_THROW_ON_ERROR`。响应头由应用设置。

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => '你好'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
```

## Route

`SlightPHP\Route` 可通过 `#[Route(method: 'POST')]` 或 `#[Route(methods: ['GET', 'POST'])]` 限制控制器入口的 HTTP 方法，不匹配时抛出 `SlightPHP\Exception`，由入口生成 405 和 Allow。完整规则见 [Route 方法注解](framework.md#route-方法注解)。

用于 URL 映射时需在 `run()` 前设置。它匹配 URL 路径、忽略查询串，再调用核心的 setPathInfo，并非独立 HTTP 服务。

```php
\SlightPHP\Route::set([
    'pattern' => '^\/tasks$',
    'zone' => 'workspace',
    'page' => 'main',
    'entry' => 'entry',
]);
SlightPHP::run();
```

pattern 不带外围正则分隔符，斜杠必须转义。`setConfigFile()` 通过 Config 加载 JSON/JSONC 路由数组，`:name` 占位符由同名键提供正则；规则按注册顺序取第一条匹配项。默认使用 PATH_INFO 或 REQUEST_URI，也可用 `Route::parse($requestPath)` 显式匹配入口已规范化的路径。samples 已启用 PHP 和 JSON 自定义路由，完整用法、注意事项和可运行地址见 [自定义 URL 路由](routes.md)。

## Tpl

```php
\SlightPHP\Tpl::setTemplateDir(__DIR__ . '/templates');
\SlightPHP\Tpl::setCompileDir(__DIR__ . '/var/templates_c');
$tpl = new \SlightPHP\Tpl();
$tpl->assign('site', 'SlightPHP');
$tpl->render('index.tpl', ['title' => '示例']);
```

模板变量保存在各个 Tpl 实例的受保护属性 `$_tpl_vars` 中，不同实例互不共享。`assign($name, $value)` 或 `assign($array)` 分配变量，`render()` 的参数覆盖同名值；同一个实例在多次渲染之间保留变量及模板循环产生的赋值。常驻服务应为每个请求创建新实例。模板目录、编译目录、分隔符等配置仍为静态配置，由所有实例共享。

6.7.0 默认对 HTML/属性、URL、JS、CSS 执行上下文自动转义，已移除 `setContextMode()`、`getContextMode()` 及兼容编译分支。直接创建 Tpl 实例，传入原始数据；内置 raw、escape、default、tr、tostring、version 及修饰链继续可用。末尾 raw 标记可信原文。默认分隔符仍为 `{` / `}`，普通 JS/CSS 花括号会保留，歧义片段可用 literal 消歧。支持边界见 [上下文自动转义](templates.md#上下文自动转义)，升级步骤见 [6.7 迁移指南](../CHANGELOG.md#67-迁移指南)。

自动模式的编译缓存保存静态块和编码计划。JS 词法结果由 Compiler 编译到计划表，动态新入口首次生成上下文变体，完整命中后不再执行 JS 词法扫描；CSS 仍按渲染流保存增量断点。首次编译会多做分析；HTML 实际状态、URL/CSS 检查和函数输出检测仍在运行时执行。见 [编译缓存与执行计划](templates.md#编译缓存与执行计划)。

从 6.4.0 起，`assign()`、`fetch()` 为实例方法，不能再使用 `Tpl::assign()`、`Tpl::fetch()` 或从外部访问 `Tpl::$_tpl_vars`；迁移示例见 [6.4 升级说明](../CHANGELOG.md#64-模板变量与实例)。`{include "child.tpl"}` 和 `{include $child}` 自动使用父模板的实例，支持多层嵌套，子模板可以读取并修改同一张变量表。PHP 代码直接调用模板辅助函数时，需显式传入上下文：`\SlightPHP\Tpl\Func::include('child.tpl', $tpl)`。

`{part "/widgets/template/card/demo"}` 调用其它模块的控制器入口，模块内容直接输出到当前位置，再输出字符串返回值，函数自身返回 null。控制器新建 Tpl 实例时使用独立变量；part 不会自动将父模板变量传给控制器。返回布尔值、数字、数组或对象时仅保留直接输出。模板只允许在 HTML 文本中插入 part，将片段收集后继续跟踪上下文。异常原样抛出，失败片段尚未输出的临时缓冲被丢弃，之前已输出的页面内容保留。完整规则与可运行示例见 [Tpl 模板与模块组合手册](templates.md)，samples 入口为 `/lab/template/entry`。

模板及编译目录由应用管理，编译目录必须可写且不应公开访问。当前实现中 `render()` 与 `display()` 均直接输出，返回值为 null（内部 fetch 使用 include），不要依赖其返回 HTML 字符串；还有 `setForceCompile()` 和历史拼写 `setDelimter()`。6.7 已移除 `$safe_mode`、`setSafeMode()` 和 Tpl 内的 `_compile()`，源码编译改由 `Tpl\Compiler::compileSource()` 负责；模板必须来自可信来源。模板语法和调用方式应以当前源码及 API 为准。新工作台通过 Tpl 渲染 `head.tpl`、`index/*.tpl` 和 `footer.tpl`；模板及展示代码统一使用默认 `{`、`}`，使用默认上下文自动转义。

源文件经 `realpath()` 解析后必须位于模板目录内；编译文件和其各级子目录也必须位于编译目录内，指向目录外的符号链接会被拒绝。保留原有 CRC32 子目录和修改时间缓存逻辑。新建编译目录及子目录默认权限为 `0700`（受 umask 影响），已有目录权限不自动修改。`compile_dir` 及其 CRC32 子目录均不检查归属或权限，允许将 `compile_dir` 设为 `/tmp`。

编译文件带有格式标记，每次加载时检查文件头；缺少当前标记的旧缓存会自动重新编译，即使模板源文件没有变化。统一使用 `.context.php` 编译文件；文件头还校验分隔符和 JS 计划版本。部署时需确保运行用户能够重写旧编译文件，常驻进程需重启以加载新类定义；OPcache 刷新由部署流程管理。

`{$value|escape}` 使用 `ENT_QUOTES | ENT_SUBSTITUTE` 和 UTF-8 进行 HTML 转义，覆盖单双引号、尖括号和 `&`，非法 UTF-8 使用替代字符，不依赖 PHP 默认选项或 `default_charset`。此修饰器用于 HTML 文本和带引号的普通属性值；URL 编码分支仍使用 `rawurlencode()`。

不检查编译文件的所属用户或权限。新建或重新写入编译文件时，将文件权限设置为 `0600`；命中已有缓存时不修改其权限。目录需要执行位以访问内部文件，因此新建目录默认使用 `0700`，不使用 `0600`。实际读写仍受操作系统权限限制，编译目录及其父级应由可信用户管理。

## Captcha

先按[插件依赖说明](#按需安装依赖)安装 `gregwar/captcha` 并启用 session，再通过 `vendor/autoload.php` 加载。依赖需要 GD、mbstring 和 fileinfo；插件在会话未启动时会启动会话。图片端点应在输出其他内容之前调用：

```php
require 'vendor/autoload.php';
$captcha = new \SlightPHP\Captcha();
$captcha->CreateImage(); // 输出 PNG 并将验证码保存到会话
```

生产环境应在首次加载 Captcha 类之前按[Cookie与会话安全](deployment.md#cookie与会话安全)配置并启动会话；类加载时的自动 `session_start()` 不会替应用补齐安全参数。

提交表单时，在同一活动会话中调用 `\SlightPHP\Captcha::check($code)`。同一会话、同一前缀仅保存最新验证码，刷新图片立即替换旧记录。验证码默认有效期为 300 秒，可在生成前通过 `Captcha::$ttl` 设置正整数秒数；到期及异常记录在检查时移除并返回 `false`。成功校验后立即移除，重复提交返回 `false`；错误输入保留当前未过期验证码，空值或非字符串输入返回 `false`。不要在校验前调用 `session_write_close()`，也不要在同一业务流程中重复调用 `check()`。原有 `del($code)` 可以继续调用，也可用于主动作废尚未使用的验证码。默认图片为 140×50、文本长度为 4–5；可在生成前修改 `width`、`height`、`minWordLength`、`maxWordLength` 和 `imageFormat`。父类现为 `Gregwar\Captcha\CaptchaBuilder`，旧版 `SimpleCaptcha` 的其他属性和方法不再提供。

Session 中的记录现在包含 `code` 和 `expires_at`，存放于 `session_prefix` 对应的固定键中；旧的“前缀 + 验证码”记录不再接受，升级后需重新获取验证码。不同用途可使用不同前缀隔离；生成图片时必须有活动会话。

并发请求下的一次性校验依赖会话后端提供互斥锁。PHP files 会话后端会锁定同一会话；使用 Redis 等后端时需确认其会话锁支持与配置，不能将仅修改当前请求的 `$_SESSION` 视为分布式原子操作。PhpRedis 对 RedisCluster 的会话锁存在限制，见[官方说明](https://github.com/phpredis/phpredis#session-locking)。

## Language

独立实验位于 `/lab/language/entry`，可切换简体、繁体、英文，输入称呼并观察词条替换与缺失键回退；目录组织、完整 API、缓存和验证方法见 [Language 使用手册](language.md)。

从 6.5.3 起，Language 使用 JSON/JSONC 语言文件；旧 INI 文件的迁移见 [6.5.3 升级说明](../CHANGELOG.md#65-language-语言文件改为-jsonjsonc653)。6.6.0 修复运行中切换 locale/语言目录的缓存失效行为，并新增请求状态重置，见 [6.6 迁移指南](../CHANGELOG.md#66-language-语言切换与请求重置)。

`Language::tr($source, $zone = 'main')` 从 `languageDir/locale/zone.json` 或 `zone.jsonc` 查找词条，与 Route 一样通过 `Config::parse()` 加载。相同 locale 和 zone 下优先使用 `.json`，没有位于语言目录内的同名 `.json` 文件时再查找 `.jsonc`；两者不合并。语言文件缺失或词条不存在时继续查找候选语言，最终未找到则返回原文。

语言文件的顶层必须是 JSON 对象，每个值必须是字符串；支持空字符串、换行、Unicode 和包含特殊符号的键，不进行 INI 常量或环境变量展开。`.json` 使用严格 JSON；`.jsonc` 允许行注释和块注释，不支持尾随逗号。解析错误抛出 `JsonException`，顶层不是对象或词条不是字符串时抛出 `InvalidArgumentException`，读文件失败抛出 `RuntimeException`；无效文件不会回退为另一种格式来掩盖错误。

例如 `languages/en/main.json`：

```json
{
  "welcome": "Welcome to SlightPHP",
  "任务已添加": "Task added"
}
```

```php
\SlightPHP\Language::setLanguageDir(__DIR__ . '/languages');
\SlightPHP\Language::setLocale('en');
echo \SlightPHP\Language::tr('welcome'); // Welcome to SlightPHP
```

旧的 `locale/zone.ini` 不再读取，升级时需将词条转换为字符串键值对象并保存为 `.json` 或 `.jsonc`；不能只修改扩展名。语言目录及 locale 的设置接口保持不变。

`Accept-Language` 仅接受由 1–8 个字母开头、后接连字符及 1–8 个字母或数字子标签组成的语言标签，例如 `en`、`zh-CN`、`zh-Hans-CN`；标签不区分大小写。非法项、通配符、非法 q 值及 `q=0` 项会被忽略，合法项按原有出现顺序查找，并保留主语言回退和 `defaultLocale` 优先规则，不按 q 值排序。

请求头最多处理 8192 字节、前 32 个条目，包含 CR、LF、NUL 或不是字符串时整个请求头被忽略。不会通过 URL 解码或删除危险字符把非法标签转换成路径。`setLocale()` 的强制语言也进行格式检查，并兼容 `en_US` 这样的下划线写法；`zone` 仅允许字母、数字、下划线开头，后续可含点或连字符。语言文件经 `realpath()` 解析后必须位于语言根目录内，指向目录外的符号链接不会读取。

`setLocale($locale)` 会清空已初始化的候选语言列表，下次 `tr()` 按新语言重新选择；传入 `null` 或空字符串时重新使用当前 `Accept-Language`。已加载的词条按语言复用，不因切换 locale 而丢弃。运行中切换语言应调用 setter，直接赋值公开属性 `$locale` 不会使候选列表失效。

`setLanguageDir($dir)` 每次调用都会清空 Language 的词条缓存，避免切换目录后仍返回旧目录的翻译；不会重置强制语言或 `defaultLocale`。词条首次加载仍受 `Config::$CACHE` 控制，清理 Language 缓存不会清理 Config 缓存，关闭 Config 缓存也不会清空 Language 已加载的词条。修改同一路径的语言文件后，生产部署应重启工作进程以更新两层缓存；开发时可关闭 Config 缓存，再调用 `setLanguageDir()` 清理 Language 缓存。

`resetRequest()` 清除强制语言（恢复为 `null`）及候选语言列表，保留语言目录、`defaultLocale`、已加载词条和 Config 缓存。串行复用同一个 PHP 工作进程时，在每次请求开始、设置本次语言前调用；可在 `finally` 中再清理，确保异常请求也不会留下强制语言：

```php
\SlightPHP\Language::resetRequest();
try {
    // $locale 为本次请求的语言；null 表示使用当前请求头。
    \SlightPHP\Language::setLocale($locale);
    $text = \SlightPHP\Language::tr('welcome');
} finally {
    \SlightPHP\Language::resetRequest();
}
```

`defaultLocale` 作为应用配置保留；若应用按请求修改该属性，须在本次请求开始时明确设置。宿主适配器负责提供本次请求的 `$_SERVER` 等数据；`resetRequest()` 只重置 Language，不重置框架路由、会话或其他插件。Language 仍使用进程内静态状态，不支持同一进程内协程或其他交错执行请求之间的自动隔离；重置接口不能据此视为整个框架已兼容常驻并发运行。

## Redis

`SlightPHP\Redis` 的 `engine: "cluster"` 使用 RedisCluster，`engine: "sentinel"` 通过 RedisSentinel 发现并连接主节点，其他模式使用 RedisArray。Sentinel 模式要求 phpredis 6.0+；`host` 是 Sentinel 地址列表，`options.service` 是 Sentinel 监控的服务名，不是 Redis 主节点地址。

例如应用配置 `redis.json`（地址、服务名及凭据按实际部署替换）：

```json
{
  "cache": {
    "engine": "sentinel",
    "host": ["127.0.0.1:26379", "127.0.0.1:26380"],
    "options": {
      "service": "mymaster",
      "sentinel_auth": ["sentinel-user", "replace-sentinel-password"],
      "auth": ["cache-user", "replace-redis-password"],
      "database": 0,
      "timeout": 1,
      "read_timeout": 1,
      "sentinel_timeout": 0.5,
      "sentinel_read_timeout": 0.5,
      "persistent": false
    }
  }
}
```

```php
\SlightPHP\Redis::setConfigFile(__DIR__ . '/redis.json');
$cache = \SlightPHP\Redis::useConfig('cache');
$cache->set('example', 'value');
$value = $cache->get('example');
```

`sentinel_auth` 与 `auth` 分别用于 Sentinel 和数据主节点；无认证时省略对应项，仅密码认证时传字符串，ACL 认证时传 `[用户名, 密码]`。Sentinel 服务端的 ACL 认证需 Redis 6.2+；较早版本的 Sentinel 使用仅密码认证。数据节点 ACL 需允许 `ROLE`、`SELECT` 和业务使用的命令。`database` 默认为 0，连接及重连后都会明确选择该数据库；默认连接和读取超时为 1 秒，Sentinel 两项超时默认为 0.5 秒。

节点地址支持 `host[:port]`、`[IPv6]:port` 和 `tls://host:port`，Sentinel 默认端口为 26379。Sentinel 使用 TLS 时在其地址中加 `tls://`；数据节点使用 TLS 时设置 `options.tls: true`。`context` 使用 phpredis 的 `['stream' => [...]]` 格式，`sentinel_context` 可为 Sentinel 单独指定这一上下文，未指定时复用 `context.stream`；JSON 中的嵌套对象会转换为数组。TLS 连接需部署对应证书及信任配置。

实现按列表依次尝试 Sentinel，取得主节点地址后连接并认证，验证目标 `ROLE` 为 master，再选库并执行命令。不可达、未知服务或返回非主节点时继续尝试其他 Sentinel，成功节点会优先用于同实例的下次发现；全部失败时构造函数抛出 `RedisException`，不会回退到 RedisArray。

运行中的连接断开或返回 READONLY 后，该连接会失效；后续调用重新查询 Sentinel 并连接当前主节点。失败的当前命令不会自动重放：实例调用沿用捕获 `RedisException` 后记录错误并返回 `false` 的约定，应用应自行判断后续操作，不能盲目重试结果不确定的写入。底层透明重试已关闭，以免重新连接到旧主节点。此流程遵循 Redis 的[Sentinel 客户端发现规范](https://redis.io/docs/latest/develop/reference/sentinel-clients/)；扩展接口见 [phpredis Sentinel](https://github.com/phpredis/phpredis/blob/6.3.0/sentinel.md)。

静态业务调用每次创建新客户端并重新发现主节点。事务、pipeline 或需共享连接的操作应复用实例；链式方法返回底层原生 Redis 对象，直接使用该对象的调用不经过本封装的异常与重连处理。重连会根据配置重新认证和选库，不恢复未完成事务，也不会重放运行中通过 `setOption()`、`select()` 等设置的连接状态。

## 数据库、Redis 与其他插件

| 插件 | 用途 | 启用前需要 |
| --- | --- | --- |
| Db | 表操作、查询与读写配置 | PDO / 对应驱动、数据库、JSON/JSONC 连接配置；见 [数据库 API](index.html#SlightPHP%5CDb) |
| Redis | Redis 缓存 | Redis PHP 扩展、服务和连接配置 |
| Log | 分级日志 | 可写日志路径；避免将调试输出混入 JSON 响应 |
| Error | 错误处理 | 明确线上错误输出策略 |
| Language | 多语言词条 | locale 文件与语言选择策略 |
| Captcha | 验证码 | Composer 的 gregwar/captcha、GD、mbstring、fileinfo 和会话 |
| Http | HTTP 客户端 | Composer 安装 guzzlehttp/guzzle、网络和超时策略 |

使用数据库时先了解条件构造与参数绑定，不要直接拼接用户输入。历史示例的服务地址与配置仅是样本，部署前替换为自己的配置，并将密钥置于 Web 根目录之外。

### 数据库失败处理

以下接口变化分别从 6.2.1（移除自动重试）和 6.2.2（查询锁参数）开始生效。升级旧应用时参阅 [6.3 迁移指南](../CHANGELOG.md#63-数据库调用)。

`Db::query($sql, $params = [])` 只执行一次 SQL，失败保留原错误并返回 `false`，框架不自动重连或重试。第三个重试次数参数及重连逻辑已移除；旧调用中的 `query($sql, $params, 1)`、`query($sql, $params, false)` 都应迁移为两个参数。

`select`、`selectOne`、增删改等沿用同样的失败行为。连接断开后由调用方决定是否重新获取连接、重新初始化会话或重试整个任务；事务、锁及会话状态不能通过重连自动恢复。写入结果不确定时先核对实际结果，不能只重放失败的 SQL。`error()` 保留原有错误协议，成功时不保证清空旧错误。

`select` 和 `selectOne` 的参数顺序均为 `table, condition, item, groupby, orderby, leftjoin, hits, for`。`selectOne` 将末尾默认空字符串的 `$hits`、`$for` 原样传给 `select`；优化器提示仍由可信调用方提供。仅 `$for === 'UPDATE'` 或 `$for === 'SHARE'` 时，在主查询排序及 LIMIT 后分别追加 `FOR UPDATE` 或 `FOR SHARE`，COUNT 查询不追加锁子句，其他值不作为 SQL 拼接。调用方须在同一连接开启并结束事务；多条锁查询可关闭计数，单条查询继续限定1条并关闭计数。

```php
$db->begin();
$row = $db->selectOne('t_invoice', ['pk_invoice' => $id], hits: '', for: 'UPDATE');
// 校验并更新后提交；失败路径须 rollback。
$db->commit();
```
