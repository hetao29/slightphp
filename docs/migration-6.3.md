# 升级到 SlightPHP 6.3.0

6.3.0 将控制器原有的 `$inPath` 数组传参改为 `SlightPHP\Request`，并向入口方法传入 `SlightPHP\Response`。这是不兼容变更，已有 6.2.x 应用也需要检查控制器和直接调用它们的代码。完整版本记录见 [Changelog](../CHANGELOG.md)，接口说明见 [Request 与 Response](http.md)。

从 6.0 / 6.1 升级时，还需完成 [6.2 升级说明](migration-6.2.md)中的配置、路径、模板、验证码和日志迁移；5.x 应用需先完成 [6.0 迁移指南](migration-6.0.md)。从 6.2.0 直接升级时，也需检查本文的数据库变化。

本文记录 6.3.0 的变化。升级到当前 6.4.0 时，还需按 [6.4 迁移指南](migration-6.4.md)调整模板变量及静态调用；下方 `^6.3` 约束也允许安装 6.4。

## 安装与自动加载

PHP 和依赖要求保持为 PHP 8.0+、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha；插件附加要求见[插件指南](plugins.md)。在应用项目中更新依赖：

```bash
composer require hetao29/slightphp:^6.3
```

仓库自身使用 `composer install` 安装锁定依赖。应用入口继续加载 `vendor/autoload.php`；Request / Response 由 Composer 的 `SlightPHP\` PSR-4 映射按需加载，仅手动引入 `SlightPHP.php` 不足以支持新的调度。部署时同步应用代码、依赖和 Composer 自动加载文件，并重启常驻进程。

注意：旧的 `^6.2` 约束允许解析到 6.3.0，不能据此认为控制器签名仍兼容。更新依赖时应一并完成下面的代码迁移。

## 控制器构造函数与入口

升级前：

```php
<?php
namespace User;

class ProfileController
{
    private array $route;

    public function __construct(array $inPath)
    {
        $this->route = $inPath;
    }

    public function Edit(array $inPath): string
    {
        return '用户 ID：' . htmlspecialchars($inPath[3] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
```

升级后：

```php
<?php
namespace User;

use SlightPHP\Request;
use SlightPHP\Response;

class ProfileController
{
    private array $route;

    public function __construct(Request $request)
    {
        $this->route = $request->route;
    }

    public function Edit(Request $request, Response $response): string
    {
        return '用户 ID：' . htmlspecialchars($request->route[3] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
```

`/user/profile/edit/123` 的路由仍为 `['user', 'profile', 'edit', '123']`。路径分段规则、默认段补全、深度限制和 URL 无需因本次签名变化而调整；多层控制器的入口及参数下标仍随目录深度变化。

框架通过 `new $classname($request)` 构造控制器，再按 `($request, $response)` 的顺序调用入口。构造前 `route` 已填入，构造函数和入口接收同一个 Request；Response 在调用入口前创建，仅传给入口。参数名不限，框架不根据参数名或类型进行依赖注入。

修改时同时检查父类构造函数、继承的入口、带 Request-Type 前缀的入口（如 `AjaxEdit`）、CLI 任务、内部调用和测试桩。`array $inPath` 会因收到 Request 而报类型错误；仅重命名变量或删除 `array` 类型仍不足以迁移按数组访问的代码。异常会向外抛出，不会自动转换为 `run() === false`。

手动创建控制器或直接调用入口时，也应创建并传入对象，而不是路径数组：

```php
$request = new \SlightPHP\Request();
$request->route = ['user', 'profile', 'edit', '123'];
$controller = new \User\ProfileController($request);
$result = $controller->Edit($request, new \SlightPHP\Response());
```

直接调用方法不会经过 `run()` 的响应结束处理，返回值由调用方自行处理。

## 请求数据读取

| 原用法 | 6.3.0 用法 |
| --- | --- |
| `$inPath` / `$inPath[3]` | `$request->route` / `$request->route[3]` |
| `$_GET` / `$_POST` | `$request->get` / `$request->post` |
| `$_COOKIE` / `$_FILES` | `$request->cookie` / `$request->files` |
| `$_SERVER['REQUEST_METHOD']` | `$request->getMethod()` 或 `$request->server['request_method']` |
| `$_SERVER['HTTP_CONTENT_TYPE']` / `$_SERVER['CONTENT_TYPE']` | `$request->header['content-type'] ?? null` |
| `file_get_contents('php://input')` | `$request->getContent()` 或 `$request->rawContent()` |

超全局变量仍可使用；Request 保存的是构造时的快照，修改对象中的数组不会更新超全局变量，构造后再修改超全局变量也不会更新已有快照。`server` 的键统一为小写，`header` 的键统一为小写并使用连字符。

JSON 正文不会自动进入 `post`，需先检查 `getContent()` 是否为 `false`，再自行解码和校验。路径数组不含查询串且不做 URL 解码。`run('/user/profile/edit/123')` 创建的 Request 仍读取当前 PHP 请求数据，只将 `route` 设置为显式路径，不会为内部调用合成新的 GET、POST 或请求正文。

## 响应输出与返回值

返回字符串并由应用入口输出的旧流程可以继续使用，迁移签名后无需同时改写全部响应。使用 Response 输出时，应先设置状态码、响应头与 Cookie，再输出正文。例如入口方法可改为：

```php
public function Edit(\SlightPHP\Request $request, \SlightPHP\Response $response): bool
{
    $response->header('Content-Type', 'application/json; charset=utf-8');
    return $response->end(json_encode([
        'id' => $request->route[3] ?? null,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}
```

同步检查入口原有的返回类型：例如从返回字符串改为 `return $response->end(...)` 时，返回类型应相应调整为 `bool` 或移除旧声明。

| 控制器行为 | `SlightPHP::run()` 的返回值 |
| --- | --- |
| 未结束 Response，包括仅调用 `write()` | 控制器原始返回值 |
| 成功通过 `end()` 或 `redirect()` 结束 Response | `null` |
| 找不到可调用入口，或路径超出深度限制 | `false` |

应用入口应使用 `$result === false` 判断调度失败，使用 `is_string($result)` 判断是否输出返回正文；不要用 `if (!$result)` 将已经结束响应的 `null` 误判为 404。完整入口示例见[快速开始](getting-started.md)。

`end()` 和成功的 `redirect()` 只标记本实例结束，不调用 `exit`，也不停止后续 PHP 语句；使用 `return $response->end(...)` 或 `return $response->redirect('/login')` 结束控制器执行。`write()` 不标记结束，混用 `write()` 和返回同一正文会导致重复输出。响应开始输出非空正文后，不能再通过该实例修改响应头、状态码或 Cookie；响应操作失败会返回 `false`，应按业务需要处理。

## 数据库调用

以下变化分别在 6.2.1 和 6.2.2 引入，从这些版本升级也应确认应用已适配。

### 6.2.1：移除自动重试

`Db::query()` 现在只接受 `$sql, $params = []`。移除调用中的第三个 `$retry` 参数，包括位置参数和命名参数 `retry:`；自定义子类也应检查覆盖的方法签名。

```php
// 升级前
$rows = $db->query('SELECT * FROM t_user WHERE pk_user = ?', [$id], 1);
// 升级后
$rows = $db->query('SELECT * FROM t_user WHERE pk_user = ?', [$id]);
```

查询失败会保留错误信息、触发 `E_USER_WARNING` 并返回 `false`，不会自动重连或重试；`select()`、`selectOne()` 及增删改操作同样不再依靠自动恢复。应用需明确检查失败结果，并按任务语义决定是否重新建立连接、恢复会话设置或重试完整事务；连接重建不能恢复已有事务和锁，写入结果不确定时应先核实实际结果。

直接使用 DbPDO 的代码需移除对 `$connectionError` 的依赖；该公开标记已删除，改为检查操作返回值，并通过 `errno()` / `error()` 读取错误。Db 的错误协议保持不变，成功查询不保证清空以前的错误，应优先依据本次返回值判断是否成功。

### 6.2.2：查询锁参数

`select()` 和 `selectOne()` 当前参数顺序均为 `table, condition, item, groupby, orderby, leftjoin, hits, for`。`select()` 新增末尾 `$for`；`selectOne()` 新增末尾 `$hits` 和 `$for`，已有位置参数调用保持兼容。覆盖这些方法的自定义子类应同步可选参数签名。

```php
// $db 已初始化，并在同一连接上成功开启事务。
$row = $db->selectOne('t_invoice', ['pk_invoice' => $id], for: 'UPDATE');
// 检查查询结果，执行所需更新后提交；失败路径回滚。
```

仅精确的大写 `'UPDATE'` / `'SHARE'` 追加 `FOR UPDATE` / `FOR SHARE`，传入 `'FOR UPDATE'`、小写或其他值均不会追加。锁子句位于主查询 LIMIT 之后，COUNT 查询不加锁；`selectOne()` 继续限制一条并关闭计数。调用方负责使用同一连接管理事务，并确认数据库支持所选语法。详情见[数据库说明](plugins.md#数据库失败处理)。

## 升级验证

- 搜索并修改控制器及父类中的 `$inPath`、数组类型的请求参数、直接调用入口的代码，检查返回类型。
- 覆盖默认路由、多层控制器、路径参数、Request-Type 前缀、CLI 和内部调用；确认构造函数与入口读取到相同的 Request 和完整路由。
- 验证查询参数、表单、JSON、上传、请求头与 Cookie；确认大小写键名及快照行为符合应用预期。
- 验证字符串返回、JSON 输出、重定向、响应结束、404 和异常处理，确认没有重复正文或响应结束后的业务操作。
- 验证数据库失败分支；使用锁查询时检查事务提交、回滚和数据库语法支持。

仓库示例可运行 `php samples/tests/routing.php` 检查真实入口、自动加载、模板和 URI 解析；实际 HTTP 验证方式见[部署文档](deployment.md#验证)。
