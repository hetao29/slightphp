# Response 使用手册与示例

运行示例：`/lab/plugin/entry/response`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。点击 201 / 418 / write-end 可预览真实状态、Content-Type、X-Sample 和正文；不用 JavaScript 时链接直接打开响应。303 链接执行真实重定向。

## 状态、响应头和正文

```php
use SlightPHP\{Request, Response};

public function Created(Request $request, Response $response): void
{
    $response->status(201);
    $response->header('Content-Type', 'application/json; charset=utf-8');
    $response->header('X-Sample', 'SlightPHP');
    $response->end(json_encode(['ok' => true], JSON_THROW_ON_ERROR));
    return;
}
```

status 接受 100–599，header 默认规范化头名，二者在发送成功时返回 true，非法内容、已输出正文或响应已结束时返回 false。头名须合法，头值和原因短语拒绝 CR、LF 和 NUL。先设置响应头、状态和 Cookie，再输出正文。

`write($text)` 可多次调用，空字符串及字符串 `"0"` 均有效。`end($text = '')` 输出最后一段并标记本实例结束，后续 write / end 返回 false。`isWritable()` 只表示本实例能否接受正文，不检测客户端连接。

**end 不调用 exit**，业务代码仍会继续执行；结束响应后应立即 return。同样，redirect 不会替应用终止 PHP 进程。Response 不保证实时分块推送，实际缓冲由 PHP SAPI 和服务器决定。

## Cookie

```php
// 入口按 HTTPS 状态配置默认策略；生产 HTTPS 保持 Secure=true。
Response::setCookieDefaults(secure: true, httponly: true, samesite: 'Lax');

$response->cookie('slightphp_sample', 'Hello SlightPHP', time() + 600);
// 删除时使用与创建时相同的 path / domain。
$response->cookie('slightphp_sample', '', time() - 3600);
```

初始策略为 Secure=true、HttpOnly=true、SameSite=Lax。samples 入口按当前 HTTPS 配置 Secure；本地纯 HTTP 开发须明确设为 false。代理部署应在可信边界正确识别 HTTPS，不能无条件信任访客提供的转发头。

cookie 使用 URL 编码值，rawcookie 使用原值。可在单次调用覆盖 secure、httponly、samesite；null 使用默认值。SameSite=None 必须同时 Secure=true。非法参数会抛 ValueError；设置成功仅代表发送响应头，浏览器是否接受仍取决于协议、域、路径等规则。

Response 的 Cookie 默认策略不自动配置 PHP Session Cookie；Session 策略须在启动前另行设置，可使用 `Session::$cookieOptions`；见 [Session 手册](session.md)。`getCookieDefaults()` 可读取当前默认策略。

示例 POST 表单受 CSRF 保护，仅设置或删除名为 slightphp_sample 的专用 Cookie。结果中的 before 是本次请求带来的旧值；点击“重新读取”后才能观察新请求中的值。它不显示 Session Cookie。

## 重定向与验证

```php
$response->redirect('/lab/plugin/entry/response?redirected=1', 303);
return;
```

默认跳转状态为 302，允许 300–399，参数非法或响应已开始时返回 false。POST 成功后用 303 实现“提交后跳转”；跳转目标由应用固定选择，不能直接接受任意用户 URL。

预期：JSON 链接返回 201，文本链接返回 418；write/end 的两行正文按顺序输出，结束后尝试写入的内容不会出现。303 响应含固定 Location；Cookie 的创建和删除均能在下一次请求验证。当前请求输入见 [Request 手册](request.md)，通用规则见 [HTTP 接口说明](http.md)。
