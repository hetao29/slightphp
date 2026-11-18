# Captcha 使用手册与示例

运行示例：`/lab/plugin/entry/captcha`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。图片由 `/lab/plugin/image` 实时生成，提交到带 CSRF 防护的 POST 入口，结果显示 matched。

## 依赖与 Session

在应用安装 `gregwar/captcha:^1.3 || ^2.0`、`ext-session` 及包所需的 GD、mbstring、fileinfo。先配置并启动 Session，再创建图片或校验；Session Cookie 的 Secure、HttpOnly、SameSite 策略见[部署说明](deployment.md)。

## 独立图片入口

```php
use SlightPHP\{Captcha, Request, Response, Route};

#[Route(method: 'GET')]
public function Image(Request $request, Response $response): void
{
    Captcha::$session_prefix = 'registration_captcha';
    Captcha::$ttl = 300;
    $response->header('Cache-Control', 'no-store');
    $captcha = new Captcha();
    $captcha->width = 140;
    $captcha->height = 50;
    $captcha->imageFormat = 'png';
    $captcha->CreateImage();
    return;
}
```

`CreateImage()` 自行设置图片 Content-Type 并输出图片，返回答案字符串供内部用途。不要 echo 其返回值，也不要把答案放入 HTML、JSON、URL 或公开日志。图片入口前不能先输出 HTML。

```html
<img src="/user/register/image" width="140" height="50" alt="验证码">
<form method="post" action="/user/register/check">
    {\SlightPHP\Csrf::tokenInput()|raw}
    <label>验证码<input name="captcha" autocomplete="off" required></label>
    <button type="submit">校验</button>
</form>
```

表单代码按 Tpl 模板使用。校验入口也必须设置相同的 session_prefix：

```php
#[Route(method: 'POST', csrf: true)]
public function Check(Request $request, Response $response): void
{
    Captcha::$session_prefix = 'registration_captcha';
    $matched = Captcha::check($request->post['captcha'] ?? '');
    // 再结合登录、限流及业务权限判断处理结果。
}
```

## 生命周期与验证

默认 ttl 为 300 秒，必须为正整数。图片生成时保存当前答案及到期时间；相同前缀的新图片替换旧答案，同一 Session 的多个标签页也会相互影响。不同业务使用不同前缀。当前实现按原文精确匹配，不自动转小写或去空格。

校验成功立即消费，不能重放；错误输入保留当前有效答案；过期或记录无效时删除并返回 false。`Captcha::del($code)` 仅在匹配当前答案时移除记录。

打开示例可见真实 PNG。错误输入得到 matched=false，输入图片字母得到 true；成功后页面重新加载图片生成新挑战。刷新链接会替换答案。缺少依赖时页面显示安装提示，图片入口返回 503。验证码不能替代 [CSRF 防护](csrf.md)，也不提供限流或用户权限。
