# 核心调度与路由

## 请求生命周期

1. 入口加载核心，设置应用目录、默认路径和需要的插件。
2. `run()` 选择路径：非空显式参数 → 非空 `setPathInfo()` → `$_SERVER['PATH_INFO']` → `$_SERVER['REQUEST_URI']`。全部为空时抛出 `SlightPHP\Exception::PATH_NOT_SET`。
3. 去掉开头 `/`，用 `parse_url(..., PHP_URL_PATH)` 排除查询串，按 `/` 与 splitFlag 的每个字符拆分，忽略空段。`preg_split()` 使用 `maxDepth + 1` 作为结果数量上限（防止整数溢出），结果超过 `maxDepth` 时立即抛出 `PATH_TOO_DEEP`，不进入控制器查找。不会 URL 解码。
4. 缺少前三段时分别补 defaultZone、defaultPage、defaultEntry。
5. 从最深候选区域开始向浅层尝试；区域后相邻两段作为 page、entry，每个深度寻找命名空间控制器文件。默认最多 10 个路径段，因此最多尝试 8 个区域深度。
6. 文件必须经 realpath 验证位于 appDir 内且是普通文件，再 `require_once`。
7. 找到类后创建 Request 并设置完整 `route`，检查目标公开方法的 `Route` 注解。HTTP 方法不匹配时，在构造控制器前抛出 `METHOD_NOT_ALLOWED`，终止查找，由应用生成 405 响应。通过检查后使用 `new $classname($request)` 构造实例；检查目标方法存在且可公开调用后执行。入口共用同一个 Request，直接按 `($request, $response)` 的顺序调用；参数名可以自定义。构造函数仍可能在方法未匹配时执行，不应在其中执行写操作。
8. 返回方法的原始返回值；Response 已结束时返回 `null`，未匹配抛出 `SlightPHP\Exception`。注解配置、构造函数和入口方法的异常继续向外抛，由调用方处理。核心不会自动输出控制器返回值、序列化对象或发送错误响应。新签名及响应规则见 [Request 与 Response](http.md)。

## 命名空间控制器

| 请求 | 文件（相对 appDir） | 类 / 方法 |
| --- | --- | --- |
| `/user/profile/update` | `User/ProfileController.php` | `User\ProfileController::Update(Request $request, Response $response)` |
| `/user/info/edit/thumb` | `User/Info/EditController.php` | `User\Info\EditController::Thumb(Request $request, Response $response)` |

路径的下划线分段首字母大写并附加 Controller 后缀。在大小写敏感文件系统上必须按此规则命名。

`maxDepth` 默认是 10，限制请求路径的非空分段总数，包含区域、page、entry 和路径参数。超过上限直接抛出 `PATH_TOO_DEEP`，不会加载控制器，也不会把合并的尾部作为一个参数继续调度。默认值允许最多 8 段区域路径，再加 page 和 entry；业务参数也会占用分段数。

在应用初始化时按实际路由需要设置：

```php
SlightPHP::setMaxDepth(15);
$maxDepth = SlightPHP::getMaxDepth(); // 15
```

`setMaxDepth(int $maxDepth)` 必须大于等于 1，否则抛出 `InvalidArgumentException` 并保留原配置。分段数在补全默认区域、page、entry 之前检查；缺省段不会额外计入。增大上限会增加候选控制器查找成本。

`setAppNamespace('Demo')` 同时影响类名和文件路径：例如文件需要放在 `appDir/Demo/User/ProfileController.php`，声明 `namespace Demo\User`。

## 路径参数

`/workspace/main/entry/hello` 的 `$request->route` 是：

```php
['workspace', 'main', 'entry', 'hello']
```

多级路由时 entry 的下标随目录深度变化，不应一律认为第三段是方法、第四段是参数。控制器应根据自己的固定路由结构读取参数并校验。通过 `$request->get/post` 读取 PHP 参数快照；框架不绑定业务参数。

`maxDepth` 同时适用于正常请求和显式路径的内部调用。默认配置下，`/user/profile/update/1/2/3/4/5/6/7` 共 10 段，分段完整保留在 `$request->route`；再追加 `/8` 则直接抛出 `PATH_TOO_DEEP`。查询串和空路径段不计入上限；配置的附加分隔字符同样参与分段。

## 配置 API

| 方法 | 默认值 / 说明 |
| --- | --- |
| `setAppDir($dir)` / `getAppDir()` | `.`，建议使用存在目录的绝对路径 |
| `setAppNamespace($namespace)` / `getAppNamespace()` | 空字符串 |
| `setControllerSuffix($suffix)` / `getControllerSuffix()` | `Controller` |
| `setMaxDepth(int $maxDepth)` / `getMaxDepth()` | `10`；请求路径最大分段数，必须大于等于 1 |
| `setDefaultZone($zone)` / `getDefaultZone()` | `zone` |
| `setDefaultPage($page)` / `getDefaultPage()` | `page` |
| `setDefaultEntry($entry)` / `getDefaultEntry()` | `entry` |
| `setSplitFlag($flag)` / `getSplitFlag()` | `/`；例如 `-.` 同时按 `-`、`.`、`/` 拆分 |
| `setPathInfo($path)` | 覆盖服务器路径来源 |
| `setAllowRequestTypes($types)` / `getAllowRequestTypes()` | `page, ajax, part, json, xml, html` |
| `setDebug($bool)` / `getDebug()` | 默认关闭；开启后使用 `SlightPHP\Log` |
| `setZoneAlias($zone, $alias)` / `getZoneAlias($zone)` | 设置 / 读取别名 |
| `setPageAlias($page, $alias)` / `getPageAlias($page)` | 设置 / 读取别名 |

别名存在实现限制：当前源码用别名计算 `zone/page`，但文件类名仍由原始 zoneParts 构造，zone 别名不能可靠替代目录映射。完整示例不依赖别名；短 URL 建议用 `SlightPHP\Route` 映射。

`Route::set()` 注册 PHP 规则，`Route::setConfigFile()` 通过 Config 加载 JSON/JSONC 路由数组，均在 `run()` 前执行。入口已规范化 URI 时可调用 `Route::parse($requestPath)` 显式匹配。完整配置与 samples 短地址见 [自定义 URL 路由](routes.md)。

## 方法前缀与 HTTP 方法

`HTTP_REQUEST_TYPE` 对应客户端请求头 `Request-Type`。如果值在允许列表中，会作为方法前缀；不在列表中的值会使用无前缀方法。未提供该头时使用无前缀方法。

这与 GET、POST、PUT 等 HTTP 方法无关，不是权限或路由白名单。HTTP 方法可以通过下面的 `Route` 注解限制。前缀生效时读取实际被调用方法（如 `AjaxSave`）上的注解。

## Route 方法注解

`SlightPHP\Route` 同时支持方法注解和原有的静态 URL 映射 API，需导入正确的命名空间：

```php
namespace User;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

class ProfileController {
    #[Route(method: 'POST')]
    public function Save(Request $request, Response $response) {
        return $response->end('saved');
    }

    #[Route(methods: ['POST', 'GET'])]
    public function Preview(Request $request, Response $response) {
        return $response->end('preview');
    }

    #[Route(method: 'POST', methods: ['POST', 'GET'])]
    public function Check(Request $request, Response $response) {
        return $response->end('checked');
    }
}
```

- `method` 是单个方法，`methods` 是方法数组；同时设置时取并集。上例 `Check` 允许 POST 和 GET，响应中的 `Allow` 为 `POST, GET`。
- 声明会去掉首尾空白、转为大写并去重；空字符串、逗号拼接的方法或非字符串数组项会抛出 `InvalidArgumentException`。HTTP 扩展方法同样支持。
- 未加注解、`#[Route]` 或 `#[Route(methods: [])]` 不限制方法，保持已有行为。注解只用于方法，不能重复声明。
- 不匹配时抛出 `SlightPHP\Exception::METHOD_NOT_ALLOWED`；不执行控制器构造函数或入口，也不继续尝试浅层路由。核心不发送响应；应用入口可根据 `getStatusCode()` 和 `getContext()['allowedMethods']` 生成 405 和 Allow，见 [核心异常与迁移](exceptions.md)。
- GET 不隐含 HEAD；OPTIONS 也不会自动放行。需要时明确加入 `methods`。
- 继承的入口沿用该方法上的注解；子类重写入口时需重新声明。非 public 方法仍不可调度。
- 正常请求与 `run('/...')` 内部调用都校验当前 Request 的 HTTP 方法；CLI 没有 `REQUEST_METHOD` 时，受限制入口会抛出 `METHOD_NOT_ALLOWED`。测试或 CLI 调用时需明确提供方法。

建议单方法入口只写 `method`，多方法入口只写 `methods`，减少重复配置。修改数据的入口应明确限制 POST、PUT、PATCH 或 DELETE，并继续执行权限与 CSRF 校验；HTTP 方法限制不替代这些校验。框架只缓存方法元数据，每次调用都会重新检查当前请求。

## CLI 和内部调用

```php
require 'vendor/autoload.php';
SlightPHP::setAppDir(__DIR__ . '/samples/app');
// 实际 CLI 控制器应避免依赖 HTTP 和会话。
$result = SlightPHP::run('/your/controller/entry');
```

传入非空参数会进入内部调用（part）语义：不更新静态 `zone/page/entry`；若目标与当前顶层路由相同，则抛出 `RECURSIVE_DISPATCH`，防止直接自调用。它不是通用递归保护，循环调用仍需应用控制。常驻进程中的静态配置会保留，应隔离每次请求的环境。

## 调度失败与异常

核心失败统一抛出 `SlightPHP\Exception`，通过 `getCode()` 区分原因，通过 `getContext()` 获取诊断上下文；`getStatusCode()` 仅给出建议 HTTP 状态，不发送响应。未定义别名的查询也改为抛出异常。控制器主动返回的 `false` 仍是业务返回值，不能用来判断调度失败。原因列表、入口 catch 示例和内部调用迁移见 [核心异常与迁移](exceptions.md)。
