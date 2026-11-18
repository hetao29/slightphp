# 自定义 URL 路由

`SlightPHP\Route` 的静态 API 将自定义 URL 映射成核心的 `zone/page/entry/参数` 路径。它与方法上的 `#[Route(...)]` 共用类名：静态规则决定调用哪个入口，注解决定该入口允许哪些 HTTP 方法。

## PHP 规则

应用入口完成 Composer、appDir 和默认入口配置后，在 `SlightPHP::run()` 前注册：

```php
use SlightPHP\Route;

Route::set([
    'pattern' => '^\/r\/:slug$',
    ':slug' => '[A-Za-z0-9_]{1,40}',
    'zone' => 'lab',
    'page' => 'route',
    'entry' => 'entry',
]);
```

访问 `/r/hello?source=php` 时，调度 `Lab\RouteController::Entry()`：

```php
$request->route; // ['lab', 'route', 'entry', 'hello']
$request->get;   // ['source' => 'php']
```

固定地址不需要占位符，例如：

```php
Route::set([
    'pattern' => '^\/tasks$',
    'zone' => 'workspace',
    'page' => 'main',
    'entry' => 'entry',
]);
```

## JSON/JSONC 规则

`setConfigFile()` 通过 `Config::parse()` 加载 `.json` 或 `.jsonc`，不再支持 INI。顶层必须为数组，每个元素必须为规则对象；数组顺序就是匹配优先级，不需要分组名称。配置文件位于公开目录之外：

```json
[
  {
    "pattern": "^\\/r-json\\/:slug$",
    ":slug": "[A-Za-z0-9_]{1,40}",
    "zone": "lab",
    "page": "route",
    "entry": "entry"
  },
  {
    "pattern": "^\\/r-pair\\/:year\\/:month$",
    ":year": "\\d{4}",
    ":month": "(?:0[1-9]|1[0-2])",
    "zone": "lab",
    "page": "route",
    "entry": "entry"
  }
]
```

入口加载后立即尝试匹配当前请求：

```php
Route::setConfigFile(__DIR__ . '/../config/routes.json');
$file = Route::getConfigFile(); // 最近一次成功设置的配置文件路径
```

`/r-pair/2026/11` 映射为 `lab/route/entry/2026/11`，参数按 pattern 中的位置排列。月值 `13` 不符合规则。不同文件的数组按加载顺序追加，后加载的规则不会覆盖先前规则。

JSON 字符串里的反斜杠必须双写：正则 `\d{4}` 写成 `"\\d{4}"`，供内部 `/.../sm` 分隔符使用的 `\/` 写成 `"\\/"`。JSONC 额外支持 `//` 和 `/* ... */` 注释，不支持尾随逗号；规则格式与 JSON 相同。

Config 默认缓存当前进程内的解析结果，不检查文件修改时间；普通 PHP-FPM 中这份静态缓存不会自动跨请求保留。同一进程需读取文件修改时可在调用前设置 `Config::$CACHE = false`，这会同时影响其他 Config 调用；已有 Route 规则不会因此清空。常驻进程应在启动时加载一次，配置发布后重启，避免反复追加同一批规则。

## 匹配规则与路径来源

- `pattern` 不带外围分隔符，内部按 `/.../sm` 匹配，因此路径斜杠写成 `\/`。建议用 `^`、`$` 限定整个路径。
- 只匹配 URL 路径，查询串不参与正则匹配；查询参数仍由 PHP 保存在 `$_GET` 和 Request 快照中。无需在 pattern 中添加 `\?.*`。
- 路径开头的重复斜杠会原样保留，不会被当作主机名；例如 `//articles/42` 不会误匹配 `/42`。是否接受重复斜杠由 pattern 决定。
- `:name` 由规则里同名键提供约束。`:id` 与 `:identifier` 可同时使用，不会相互替换。约束中的分组用 `(?:...)`；普通捕获组也会被追加为路径参数，可能产生额外参数。
- 规则按注册顺序取第一条匹配项，不自动按具体程度排序。应把具体规则放在宽泛规则之前；PHP 和 JSON 共享这一顺序。
- `zone/page/entry` 省略或为空时采用核心默认值。捕获组依次追加，默认以 `/` 分隔；自定义 splitFlag 时使用其首字符，空 splitFlag 使用 `/`。
- 捕获值不做 URL 解码；`hello%20world` 仍是该编码字符串。斜杠和核心配置的其他分隔字符会继续拆分路径，不适合用单个参数携带任意路径。
- 未匹配时不修改 `SlightPHP::$pathInfo`，可继续使用默认命名空间调度。只有 `run()` 最终找不到入口才抛出核心异常。

`set()` 和 `setConfigFile()` 都会立即匹配，默认优先读取非空 `PATH_INFO`，否则读取 `REQUEST_URI`。它们不会读取已经改写的 `SlightPHP::$pathInfo`，因此连续注册规则不会自动串联改写。

入口已去除 `/index.php`、子目录前缀，或 Web 转发提供了不合适的 PATH_INFO 时，保存规范化后的原始请求路径，完成注册后显式重新匹配：

```php
// $requestPath 是入口规范化后的路径，如 /r/hello，不是映射后的 zone/page/entry。
SlightPHP::setPathInfo($requestPath); // 未命中时用于默认调度
require __DIR__ . '/../config/routes.php'; // 注册 PHP 规则并加载 JSON
Route::parse($requestPath);
$result = SlightPHP::run();
if (is_string($result)) echo $result;
```

省略 `Route::parse()` 的参数时重新匹配服务器路径；传入参数时使用指定路径，查询串同样会被排除。CLI 可用这一方式测试规则，无需修改服务器变量。规则是进程内静态状态；常驻进程应只注册一次，每次请求先恢复当前原始 pathInfo，再调用 `parse($requestPath)`，避免重复追加规则或使用旧的映射结果。

## 方法限制与异常

自定义路由不会绕过入口上的 Route 注解。例如 `/tasks/save` 映射到只允许 POST 的 `Save`，GET 仍抛出 `SlightPHP\Exception::METHOD_NOT_ALLOWED`。CSRF、权限和业务输入校验仍由控制器执行。

方法元数据先按实际控制器类名和入口方法名查缓存，未命中才反射；无注解的空结果和非公开方法的可见性也缓存。继承方法仍读取实际声明上的注解，子类覆盖按子类入口独立解析。缓存不保存 HTTP 方法的匹配结果，405 检查仍在构造控制器前逐次执行；无效注解抛出后不会被缓存为无限制。普通 PHP-FPM 中缓存只在当前请求内复用，不会自动跨请求保留。

配置文件不存在或不可读时抛出 `RuntimeException`；JSON/JSONC 语法错误时抛出 `JsonException`；扩展名不受支持、顶层不是数组、元素不是对象、规则缺少 pattern、占位符约束类型不正确、正则无效或无法完成匹配时抛出 `InvalidArgumentException`。无效配置不会被加入规则列表。调度异常由入口捕获并生成 404、405 和 Allow，见 [核心异常与处理](exceptions.md)。

## samples 与验证

完整配置在 [routes.php](../samples/config/routes.php) 和 [routes.json](../samples/config/routes.json)，加载与规范化流程见 [samples 入口](../samples/www/index.php)。

| 地址 | 配置 / 行为 |
| --- | --- |
| `/r/hello?source=php` | PHP 单参数，查询参数独立保留 |
| `/r-json/hello?source=json` | JSON 单参数 |
| `/r-pair/2026/11` | JSON 多参数，月份限制 01–12 |
| `/tasks` | 任务工作台 |
| `/tasks.json` | GET 任务 JSON；POST 返回 405 和 Allow |
| `/tasks/save` | POST 写操作，保留 CSRF 校验；GET 返回 405 |
| `/lab/route/entry/hello` | 未命中自定义规则，使用默认命名空间路径 |

路由实验室显示实际配置，可直接点击链接或输入参数。

官网 `/site/main/plugins#route-custom` 展示 PHP / JSON 配置和可运行链接，`/site/main/plugins#route-methods` 展示单方法、多方法注解，并提供 GET 200、POST 405 与 POST 200 的实际验证入口。

部署 samples 后，通过上述地址和插件页的验证入口检查短地址、查询参数、参数约束与方法限制；在独立会话中验证任务增删。完整步骤见[部署文档](deployment.md#验证)。
