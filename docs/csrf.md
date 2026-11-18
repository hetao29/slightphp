# CSRF 使用手册

`SlightPHP\Csrf` 提供基于 Session 的同步 token；入口通过 `#[Route(method: 'POST', csrf: true)]` 开启校验。Csrf 本身不是注解。框架在构造控制器前验证，失败只抛异常，应用入口决定 HTTP 状态码与响应正文。

6.7.2 新增会话按需启动和 `Csrf::$sessionHandler` 应用初始化回调；未提供回调时使用原生 `session_start()`。原来提前启动 Session 的接入方式继续可用，会话名称、存储和 Cookie 策略应在首次使用 Csrf 前配置。本次修改见 [6.7.2 说明](../CHANGELOG.md#672--2026-11-19)。

## 运行 samples 实验

部署 samples 后打开 `/lab/csrf/entry`，也可以从工作台侧栏、官网示例页或 Csrf 插件卡片进入。页面支持简体中文、繁体中文与英文。计数只记录当前会话中成功进入示例提交处理的次数，不需要数据库，也不修改任务列表。

| 操作 | 预期结果 |
| --- | --- |
| 提交有效表单 | 校验通过，计数加一，303 跳回实验页 |
| 点击有效 token 的 Ajax 按钮 | 返回 200 和 `{"ok":true,"accepted":…}`，计数加一 |
| 点击缺少或错误 token 的按钮 | 返回 403，不进入控制器，计数不变 |
| 使用自定义名称面板 | 表单和请求头使用 `--csrf--token`，默认名称不能用于该入口 |
| 更新当前会话 token | 使用当前有效 token 授权更新，303 返回页面，新表单使用新 token |
| 在更新前已打开的旧标签页提交 | 旧 token 返回 403；刷新旧标签页后恢复 |
| 用 GET 访问任一提交或更新入口 | 返回 405，`Allow: POST` |

普通表单和更新 token 不依赖 JavaScript；Ajax 对比需要 JavaScript。成功 JSON 响应由示例控制器生成，403 仍使用 samples 的 HTML 错误页，前端根据真实状态码显示提示。

| 路径 | 方法 | 功能 |
| --- | --- | --- |
| `/lab/csrf/entry` | GET | 生成或复用 token，显示实验页与计数 |
| `/lab/csrf/submit` | POST | 校验 `_csrf` 或 `X-CSRF-Token`，记录提交并跳转 |
| `/lab/csrf/check` | POST | 默认名称校验，返回 JSON |
| `/lab/csrf/customsubmit` | POST | 校验 `--csrf--token` 表单字段或同名头，记录提交并跳转 |
| `/lab/csrf/customcheck` | POST | 自定义名称校验，返回 JSON |
| `/lab/csrf/rotate` | POST | 默认名称校验后更新 token |

## 1. 配置 Session 与按需启动

可以在输出正文前配置并启动 Session，再进行调度；也可以由应用提供初始化回调，在实际使用 Csrf 时按需启动。生产 HTTPS 应启用 Secure；HTTP 本地开发及反向代理部署见[部署手册](deployment.md#cookie与会话安全)。

```php
require dirname(__DIR__) . '/vendor/autoload.php';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure' => true, 'httponly' => true, 'samesite' => 'Lax',
]);
session_start();
```

samples 的 `www/index.php` 已启动会话，控制器无须再次调用。Csrf 默认在首次需要会话时调用原生 `session_start()`，不会在普通路由调度时提前启动。应用需要自己的会话配置时在入口提供 handler：

```php
\SlightPHP\Csrf::$sessionHandler = [AppSession::class, 'init'];
```

回调不接收参数，在 Session 尚未启动且实际调用 Csrf API 时执行。`Route(csrf: true)` 只会在请求类型、方法限制通过且该入口需要 CSRF 时进入校验；普通 GET 或未启用 CSRF 的路由不会因此启动会话。回调复用应用自己的会话名称、Cookie、缓存头和存储配置，成功后必须处于 `PHP_SESSION_ACTIVE`，否则仍抛出 `CSRF_SESSION_NOT_STARTED`。已启动会话不重复调用回调；未配置 handler 时使用原生 `session_start()`，启动失败仍报告 `CSRF_SESSION_NOT_STARTED`。`token/tokenInput/rotate/clear` 同样按需启动，`session_write_close()` 后再次调用也会重新执行初始化回调；需要无会话的路径不调用这些 API。该回调是固定应用配置，不保存用户或请求状态。

原生启动使用 `@session_start()` 抑制默认警告输出，并捕获 `Throwable`；启动失败统一通过 `CSRF_SESSION_NOT_STARTED` 报告。原生调用抛出的异常（包括应用将警告转换成的 `ErrorException`）通过 `getPrevious()` 保留，不替换应用错误处理器；返回 false、会话仍未激活的情况由状态检查处理。自定义初始化回调抛出的异常仍原样传播。

## 2. 全局配置

应用入口可以在生成 token 或调度前设置公共静态变量：

```php
\SlightPHP\Csrf::$fieldName = 'app_csrf';
\SlightPHP\Csrf::$sessionKey = 'my_app_csrf';
```

`$fieldName` 默认 `_csrf`，影响无参数的 `tokenInput()`、默认手动校验及 `Route(csrf: true)` 的表单字段。默认请求头仍为 `X-CSRF-Token`。`$sessionKey` 默认 `_slightphp_csrf`，控制生成、校验、更新与清除 token 时使用的 Session 键；不能为空。`tokenInput('自定义名称')` 和 Route 中的字符串可覆盖字段名，不改变 Session 键。静态配置由当前应用统一管理，不用于在交错并发请求之间切换用户状态。

以下示例使用框架默认配置。

## 3. 普通表单

页面控制器将 token 传入模板：

```php
$tpl->render('form.tpl', ['csrf_input' => \SlightPHP\Csrf::tokenInput()]);
```

`tokenInput()` 返回属性值已转义的完整 input 标签，不直接输出。在 Tpl 中用末尾 `|raw` 保留这个由框架生成的 HTML：

```html
<form method="post" action="/lab/csrf/submit">
    {$csrf_input|raw}
    <button type="submit">提交</button>
</form>
```

也支持直接在模板中调用静态方法，无须预先向模板传入 `$csrf_input`：

```html
<form method="post" action="/lab/csrf/submit">
    {\SlightPHP\Csrf::tokenInput()|raw}
    <button type="submit">提交</button>
</form>
```

这与 `{$csrf_input|raw}` 生成相同的 hidden input；应用仍需预先启动 Session。方法在渲染时执行，编译缓存不会保存 token，更新 token 后再次渲染会使用新值。自定义字段也支持直接调用：`{\SlightPHP\Csrf::tokenInput(fieldName: '--csrf--token')|raw}`。末尾 `|raw` 用于输出该方法生成的 HTML；省略它会将整个标签转义为文本。

接收入口同时限制 HTTP 方法并开启校验：

```php
use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

#[Route(method: 'POST', csrf: true)]
public function Submit(Request $request, Response $response): void
{
    // CSRF 已通过；继续检查身份、对象权限和业务字段。
    $response->redirect('/lab/csrf/entry', 303);
}
```

也可以在原生 PHP 中直接 `echo \SlightPHP\Csrf::tokenInput();`，或继续手写 `<input type="hidden" name="_csrf" value="{$csrf}">` 并向模板传入 `Csrf::token()`。

## 4. Ajax / JSON

从当前会话页面取得 token，通过请求头发送：

```js
const token = document.querySelector('[name="_csrf"]').value;
const response = await fetch('/lab/csrf/check', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
    },
    body: JSON.stringify({ example: 'csrf' })
});
if (response.ok) console.log(await response.json());
else console.log(response.status); // samples 使用 HTML 错误响应。
```

请求头存在时优先校验请求头，即使为空或错误也不回退到表单。没有该头才读取 POST 字段；不会从查询串、Cookie、`$_REQUEST` 或 JSON 正文字段取 token。数组、非字符串、空值及不匹配的值均拒绝。

## 5. 开关与自定义名称

| Route 参数 | 表单字段 | 请求头 |
| --- | --- | --- |
| 省略或 `csrf: false` | 不校验 | 不校验 |
| `csrf: true` | `_csrf` | `X-CSRF-Token` |
| `csrf: '--csrf--token'` | `--csrf--token` | `--csrf--token` |

```php
#[Route(method: 'POST', csrf: '--csrf--token')]
public function CustomSubmit(Request $request, Response $response): void
{
    // 已按自定义名称完成校验。
}
```

表单对应调用 `Csrf::tokenInput('--csrf--token')`，或手写 `<input type="hidden" name="--csrf--token" value="{$csrf}">`；Ajax 对应写 `headers: { '--csrf--token': token }`。自定义名称为非空的 ASCII 字母、数字、下划线和横线组合；空字符串、点、空白、数组括号等会抛出 `InvalidArgumentException`。表单字段区分大小写，请求头不区分大小写；下划线按现有 Request 的规则规范化为横线。

配置字符串后，仅按自定义名称查找，不再读取默认名称。名称只控制取值位置，不改变 token 内容或 Session 存储；默认和自定义入口复用同一会话 token。每个方法只能声明一个 Route，可以同时带其他类型的注解；重复 Route 抛出 `InvalidArgumentException`。多个 HTTP 方法使用 `methods: ['POST', 'PUT']`。

## 6. API 与生命周期

| API | 行为 |
| --- | --- |
| `Csrf::token(): string` | 缺失时生成，存在时复用；32 随机字节转为 64 位小写十六进制字符串 |
| `Csrf::tokenInput(?string $fieldName = null): string` | 生成 hidden input；null 使用 `$fieldName`（默认 `_csrf`），字符串指定字段名，返回 HTML 而不直接输出 |
| `Csrf::validate(Request $request, ?string $fieldName = null): void` | 手动校验；null 使用默认名称，字符串同时指定字段和头名；失败抛异常，不补发 token |
| `Csrf::rotate(): string` | 保存并返回新 token，旧 token 立即失效 |
| `Csrf::clear(): void` | 仅清除 token，保留其他 Session 数据；后续 `token()` 才重新生成 |

应用在登录、退出或身份切换时更新或清除 token。普通提交不自动作废，便于多标签页使用。实验与任务工作台共用 token，因此更新后，其他标签页中的旧任务表单同样需要刷新。

直接调用 PHP 方法不会经过调度器，应自行调用 `Csrf::validate($request)`；使用自定义名称时调用 `Csrf::validate($request, fieldName: '--csrf--token')`。`SlightPHP::run('/...')` 或模板 part 调度仍会检查实际入口；继承方法沿用注解，覆盖方法以自身声明为准。启用后对所有允许的 HTTP 方法校验，不自动跳过 GET、HEAD、OPTIONS 或 CLI。

## 7. 异常响应

```php
try {
    SlightPHP::run();
} catch (\SlightPHP\Exception $error) {
    http_response_code($error->getStatusCode());
    if ($error->getCode() === \SlightPHP\Exception::METHOD_NOT_ALLOWED
        && !empty($error->getContext()['allowedMethods'])) {
        header('Allow: ' . implode(', ', $error->getContext()['allowedMethods']));
    }
    echo $error->getCode() === \SlightPHP\Exception::CSRF_INVALID
        ? '表单已失效，请返回并刷新页面后重试。'
        : '请求无法完成。';
}
```

`CSRF_INVALID` 建议映射为 403；`CSRF_SESSION_NOT_STARTED` 表示原生启动失败、自定义回调返回后会话仍未激活或缺少扩展，建议返回 500。原生调用抛出的异常可通过 `getPrevious()` 读取；自定义初始化回调抛出的异常仍由应用处理。框架异常消息和上下文不携带 token，应用也不要把 token 写入 URL 或日志。权限、输出转义、幂等和防重复提交仍由应用分别处理。

## 源码位置

- `samples/app/Lab/CsrfController.php`：表单、JSON 校验、自定义名称和 token 更新入口。
- `samples/app/templates/workspace/csrf.tpl`：隐藏字段、实验按钮及可复制代码。
- `samples/www/assets/demo.js`：发送 Ajax 并展示 HTTP 状态。
- `samples/www/index.php`：Session 初始化与异常响应。
- `plugins/Csrf.php`：token API；`plugins/Route.php` 与 `SlightPHP.php`：策略声明和调度校验。

另见 [Csrf 插件](plugins.md#csrf)、[Route 的 CSRF 校验](framework.md#route-的-csrf-校验)、[异常处理](exceptions.md)和[完整示例](samples.md)。
