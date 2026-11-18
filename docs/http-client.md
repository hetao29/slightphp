# Http 使用手册与示例

运行示例：`/lab/plugin/entry/http`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。Http 是向上游发送请求的客户端；处理当前浏览器请求的类见 [Request](request.md)，输出当前响应见 [Response](response.md)。

## 安装与 GET

在应用安装 `guzzlehttp/guzzle:^7.0 || ^8.0` 并引入 Composer 自动加载器。

```php
use SlightPHP\Http;
use GuzzleHttp\Exception\GuzzleException;

try {
    $result = Http::get(
        'https://httpbin.org/anything',
        ['message' => 'hello'], // 查询参数
        ['sample' => 'demo'],   // 仅发送这些 Cookie
        true,                  // 同时返回响应头
        5,                     // 超时秒数
        ['X-Sample' => 'SlightPHP']
    );
    $body = $result['result'];
    $headers = $result['header'];
} catch (GuzzleException $e) {
    // 处理连接失败、超时和 HTTP 错误；不要向浏览器转发原始异常文本。
}
```

不要求响应头时返回正文字符串；要求响应头时返回 `['result' => string, 'header' => array]`。响应头各项的值为数组，不保证只有一个值。插件不自动解析 JSON，不提供 status 字段；需要完整 PSR-7 响应时可直接使用 `Http::getInstance()->request()`。

## POST 与正文

```php
$jsonBody = Http::post('https://httpbin.org/anything', ['message' => 'hello']);
$rawBody = Http::post('https://httpbin.org/anything', 'plain text', [], false, 5,
    ['Content-Type' => 'text/plain; charset=utf-8']);
```

数组和对象通过 Guzzle 的 json 选项发送，不会变成普通表单；其他值作为 body。需要 form_params、multipart 或更多选项时，直接使用共享 Guzzle 客户端。`getInstance()` 延迟创建并复用客户端。

## 运行与错误

在线实验默认访问固定的 `https://httpbin.org/anything`，GET 传递查询参数，POST 发送 JSON，均携带示例请求头和示例 Cookie，不转发浏览器 Session。可在私有服务配置的 http.base_url 指定管理员控制的同协议回显服务；该配置应提供 `/anything` 和 `/status/404`。配置方式见 [Db 手册](db.md#准备服务)。

访客只能选择 GET、POST、404，不能任意指定上游 URL。不要将本示例改为公开的任意 URL 代理；应用应按业务约束允许的目标及重定向目的地。

选择 404 时捕获 Guzzle RequestException，展示 upstream_status=404；网络不可达或依赖缺失会显示提示，不伪造回显结果。正常请求展示实际响应头和正文，页面最多保留前 12000 字节的正文用于观察。
