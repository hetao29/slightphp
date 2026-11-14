# Request 与 Response

6.3.0 新增 `SlightPHP\Request` 提供请求数据，`SlightPHP\Response` 负责设置并输出响应，适用于 PHP-FPM、PHP 内置服务器和 CLI。旧应用的修改步骤见 [6.3 迁移指南](migration-6.3.md)。

应用入口加载 `vendor/autoload.php` 后，Composer 按 PSR-4 将 `SlightPHP\` 映射到 `plugins/`，可直接使用 `new \SlightPHP\Request()`、`new \SlightPHP\Response()`。控制器调度也复用此自动加载规则。

## 控制器签名

```php
<?php
namespace Home;

use SlightPHP\Request;
use SlightPHP\Response;

class MainController {
    /**
     * 返回请求参数示例。
     * @param Request $request 当前请求及完整路由。
     * @param Response $response 当前响应。
     * @return bool 响应结束结果；run() 在响应结束后返回 null。
     */
    public function Entry(Request $request, Response $response) {
        $response->header('Content-Type', 'application/json; charset=utf-8');
        return $response->end(json_encode([
            'route' => $request->route,
            'query' => $request->get,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
```

调度直接调用 `$controller->$method($request, $response)`，按位置传参：第一个是 Request，第二个是 Response。参数名可以自定义，声明顺序须与传入顺序一致。

调度通过 `new $classname($request)` 构造控制器。需要读取请求的构造函数声明 `__construct(Request $request)`；此时 `route` 已填入完整路径数组，构造函数与入口方法接收同一个 Request 实例。Response 在调用入口方法前创建，仅传入入口方法。

这是不兼容变更：调度不再向构造函数或入口方法传入 `$inPath` 数组。原 `$inPath` 参数改为 Request 参数，并从 `$request->route` 读取路径。`array $request` 等数组参数也不会自动接收路径。

没有使用 Response 结束响应时，`run()` 保持返回控制器原始返回值，由应用入口处理；成功调用 `end()` 或 `redirect()` 结束响应后则返回 `null`，避免入口重复输出其布尔结果或触发 `false` 路由失败分支。仅调用 `write()` 不会结束响应，入口仍会收到控制器的返回值。

## Request

| 属性 / 方法 | 内容 |
| --- | --- |
| `$request->route` | 原 `$inPath` 完整路由分段数组，包含补全的默认段；不包含查询串，不做 URL 解码 |
| `$request->get` | `$_GET` 快照 |
| `$request->post` | `$_POST` 快照，不自动解析 JSON |
| `$request->cookie` | `$_COOKIE` 快照 |
| `$request->files` | `$_FILES` 快照，保持 PHP 的单文件和多文件结构 |
| `$request->server` | `$_SERVER` 快照，键为小写，例如 `request_method`、`request_uri` |
| `$request->header` | 从 `HTTP_*` 和 `CONTENT_TYPE/LENGTH/MD5` 提取的请求头，键为小写和连字符，例如 `content-type` |
| `$request->getMethod()` | HTTP 方法；CLI 未设置时返回 `false` |
| `$request->getContent()` / `rawContent()` | 按需读取并缓存 `php://input`，空正文为 `''`，失败为 `false` |

推荐使用 **route** 替代 `inPath`，表示框架用于调度的分段数据。例如 `/user/profile/edit/123` 对应 `['user', 'profile', 'edit', '123']`，原 `$inPath[3]` 改为 `$request->route[3]`。多层控制器的 entry 下标仍随目录深度变化，不能统一认定第三段为入口。URI 字符串通过 `$request->server['request_uri']` 读取。

快照不与超全局变量建立引用；修改 `$request->post` 不会更新 `$_POST`。空值、`'0'`、数组和大 ID 数字字符串保持 PHP 解析后的原值。上传和 multipart 正文沿 PHP 的处理规则，`getContent()` 不重建 multipart 数据。上传临时文件仍由 PHP 管理，请求结束后不得依赖其继续存在。

## Response

| 方法 | 行为 |
| --- | --- |
| `header(string $key, string $value, bool $format = true)` | 设置/替换同名响应头，默认规范头名称大小写 |
| `status(int $http_code, string $reason = '')` | 设置 100–599 状态码，可指定原因短语 |
| `cookie($name, $value = '', $expires = 0, $path = '/', $domain = '', $secure = false, $httponly = false, $samesite = '')` | 使用 PHP `setcookie()`，值经过 URL 编码；到期时间为 Unix 时间戳 |
| `rawcookie(...)` | 同上，使用 `setrawcookie()` 保留原始值 |
| `write(string $content)` | 输出一段正文，缓冲由 PHP SAPI 管理 |
| `end(string $content = '')` | 输出最后一段正文，标记本实例结束 |
| `redirect(string $url, int $http_code = 302)` | 设置 3xx 状态码和 Location，然后结束 |
| `isWritable()` | 本实例是否仍接受正文；不检测客户端连接状态 |

操作成功返回 `true`，响应已结束或不能设置响应头时返回 `false`。非法响应头名称、含换行/NUL 的头值与原因短语、非法状态码均返回 `false`；非法 Cookie 参数沿 PHP 抛出 `ValueError`。Cookie 空字符串沿 PHP 的删除语义，多次调用不同 Cookie 不会互相覆盖。

状态码、响应头和 Cookie 应在 `write()` 输出非空正文前设置；本实例一旦开始输出，即使 PHP 还在缓冲，也不再接受这些修改。重复 `end()`、结束后 `write()` 返回 `false`。`end()` 不调用 `exit` 或 `fastcgi_finish_request()`，不阻止直接 `echo`；控制器应使用 `return $response->end(...)` 停止自身执行。

## 调度入口

入口继续使用 `SlightPHP::run($path = '')`。Request 在构造控制器前创建并设置完整 `route`，Response 在调用入口方法前创建。路径深度限制、默认段、Request-Type 和显式路径的 part 语义沿用原规则。
