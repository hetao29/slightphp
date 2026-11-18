# Request 使用手册与示例

运行示例：`/lab/plugin/entry/request`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。页面可发送 GET、multipart 表单、正确或错误的 JSON，观察框架收到的实际数据。相关控制器签名见 [HTTP 接口说明](http.md)。

## 控制器参数

```php
use SlightPHP\{Request, Response, Route};

#[Route(method: 'POST', csrf: true)]
public function Submit(Request $request, Response $response): void
{
    $message = $request->post['message'] ?? '';
    if (!is_string($message) || strlen($message) > 200) {
        $response->status(400);
        $response->end('Invalid message');
        return;
    }
    // 验证后执行具体业务。
}
```

框架创建 Request / Response 并传入入口。Request 是构造时的数据快照，修改属性不会同步修改 PHP 超全局数组。字段值未经业务校验，`message[]=x` 会得到数组，不能假定所有输入都是字符串。

| 属性 / 方法 | 内容 |
| --- | --- |
| get、post | 查询参数、表单参数 |
| request | `$_REQUEST` 快照，来源和优先级由 PHP request_order / variables_order 决定 |
| cookie | 请求中收到的 Cookie |
| files | PHP `$_FILES` 结构，包括上传错误码 |
| server | 服务器信息，键名转为小写 |
| header | 请求头，键名为小写且使用连字符，如 content-type、x-sample |
| route | 完整路由分段，包括 zone、controller、entry 及后续参数 |
| getMethod() | HTTP 方法；CLI 无该信息时为 false |
| getContent()、rawContent() | 原始正文字符串，读取失败为 false；同一实例内缓存 |

Content-Type / Content-Length 等特殊服务器字段同样会被纳入 header。访问头字段时使用 `$request->header['x-sample']`，不能用原始大写名称。

## JSON 正文

JSON 不会自动解析或进入 post。客户端将 CSRF token 放在请求头，而不是假定 JSON 中的 `_csrf` 会被识别：

```js
const response = await fetch('/lab/plugin/decode', {
  method: 'POST', credentials: 'same-origin',
  headers: {'Content-Type': 'application/json', 'X-CSRF-Token': token, 'X-Sample': 'SlightPHP'},
  body: JSON.stringify({message: 'Hello SlightPHP'})
});
console.log(response.status, await response.text());
```

服务端读取 `$request->getContent()`，控制大小后用 `json_decode(..., true, 16, JSON_THROW_ON_ERROR)` 解析，再检查结构和字段。示例正确正文返回原始 raw、解析后的 message、空 post 和请求头；无效 JSON 返回 400；缺失或错误 CSRF token 会在入口执行前被拒绝（403）。

## 文件与验证

上传表单需设置 `enctype="multipart/form-data"`。检查 `files` 中的 error、大小和实际文件类型，不能信任客户端声明的 MIME、文件名或扩展名。multipart 原始正文的可读性受 PHP 配置影响，通常通过 post / files 处理。

本示例只展示 name、size、error，不保存文件、不显示临时路径；上传限制由 PHP 与 Web 服务器设置。示例不输出 Session Cookie、认证头或完整 server 数据，避免将其当作公开调试转储接口。动态数据仍由模板进行输出编码。

GET、表单、文件和 JSON 都应能看到真实来源；使用数组替代 message 时应被拒绝。响应状态和 Cookie 的后续实验见 [Response 手册](response.md)。
