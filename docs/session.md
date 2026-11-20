# Session 使用手册

`SlightPHP\Session` 按需封装原生 PHP Session，需要 `ext-session`。引入类或设置配置不会启动会话；不调用 `header()`，默认由 `session_cache_limiter('nocache')` 产生原生禁止缓存响应头。

本插件自 6.7.4 起提供。Csrf 未配置 `sessionHandler` 时使用 `Session::start()`，已有回调继续可用，见 [Session 接入迁移](../CHANGELOG.md#session-接入迁移)。可运行示例位于 `/lab/plugin/entry/session`。

## 配置与启动

在首次使用 Session 或 Csrf 前配置，名称、Cookie 和缓存 limiter 仅在会话未激活时应用：

```php
require dirname(__DIR__) . '/vendor/autoload.php';

\SlightPHP\Session::$sessionName = 'ExampleSession';
\SlightPHP\Session::$cookieOptions = [
    'lifetime' => 86400,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
];
```

| 配置 | 默认值 | 语义 |
| --- | --- | --- |
| `$sessionName` | `null` | 保留 PHP 当前名称；字符串作为原生 `session_name()` 参数 |
| `$cookieOptions` | `[]` | 保留 PHP 当前 Cookie 配置；非空数组传给 `session_set_cookie_params()`，支持 `lifetime/path/domain/secure/httponly/samesite`，未指定项由 PHP 保留 |
| `$cacheLimiter` | `'nocache'` | 启动前配置原生 limiter；`''` 关闭自动缓存头，`null` 保留当前运行时值 |

名称、域名和有效期由应用决定，框架不依赖应用常量或域名工具。Cookie 生命周期不等于服务端存储期限。HTTP 本地开发按实际环境配置 Secure，HTTPS 生产应启用。

上述配置不自动启用严格 ID 或设置存储后端。应用应在启动前配置 `session.use_strict_mode`、`session.use_only_cookies` 和存储参数，见[部署手册](deployment.md#cookie与会话安全)。

`start(): bool` 在会话已激活时直接返回 true；配置或启动失败返回 false，原生参数错误和应用错误处理器抛出的异常继续传播。默认启动使用 `@session_start()`，不把失败警告输出到响应。

## 读写与 ID

```php
$session = &\SlightPHP\Session::get();
$session['counter'] = ($session['counter'] ?? 0) + 1;
\SlightPHP\Session::get()['choice'] = 'example';
$id = \SlightPHP\Session::id();
$name = \SlightPHP\Session::name();
```

`&get(): array` 按引用返回当前原生会话数组；启动失败抛出 `RuntimeException`，不继续读写未激活的会话。`id()`、`name()` 启动失败返回 false。

`id($id)` 在会话未激活时设置要打开的 ID，成功时返回设置前的 ID；不传值、null、空字符串等空值沿读取语义。它不是登录时轮换 ID 的方法，也不在会话已激活时隐式关闭或切换身份。

## 关闭、重新打开与边界

通过 `session_write_close()` 释放锁，后续调用 Session 会按配置重新初始化；不把“已启动”或用户数据缓存到静态属性。配置只是固定应用策略，不应跨请求保存用户信息。

流式响应需要在首包前关闭自动 Cookie 和缓存头，输出后使用原生 `session_start()` 重开同一会话，再使用 `Session::get()` 访问已激活的会话；此时插件直接复用，不重新设置名称、Cookie 或 limiter。

`nocache` 会发送 `Cache-Control: no-store, no-cache, must-revalidate` 等原生头。应用后续重新设置 Cache-Control 仍能覆盖它；公开 GET 不应为配置或检查会话而提前启动 Session。Csrf 默认调用 `Session::start()`，复用相同配置；设置 `Csrf::$sessionHandler` 后由该回调负责初始化。回调也可自行调用 `Session::start()`。会话已激活时 Csrf 直接复用，不执行回调。

本插件沿用 PHP 原生 Session，未提供 Swoole 协程隔离；不能把会话引用带到其他并发请求或保存到共享对象。

## 运行示例

访问 `/lab/plugin/entry/session`，提交文本后观察结果中的 `counter`、`message`、`name` 和 `has_id`。每次有效 POST 增加计数；刷新页面只读取结果，数据继续保留。另开独立浏览器会话时，计数从 0 开始。缺失或无效 CSRF token 的 POST 返回 403，不修改数据。

samples 的入口先配置严格 ID、files 存储和 Cookie，再调用 `Session::start()`；因此实验中的 `get()`、`id()`、`name()` 复用已有会话，不重设名称或缓存策略。实验不展示会话 ID，不修改任务工作区或 CSRF token。控制器见 [PluginController.php](../samples/app/Lab/PluginController.php)，可复制代码见 [plugin-code.php](../samples/app/data/plugin-code.php)。
