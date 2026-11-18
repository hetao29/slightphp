# Changelog

版本更新与升级迁移统一收录在此。升级当前版本请按[6.x 升级与迁移指南](#6x-升级与迁移指南)执行；6.x 的修改汇总见[版本更新记录](#版本更新记录)。

<a id="60-迁移指南"></a>
<a id="62-迁移指南"></a>
<a id="63-迁移指南"></a>
<a id="64-迁移指南"></a>
<a id="65-迁移指南"></a>
<a id="66-迁移指南"></a>
<a id="67-迁移指南"></a>

## 6.x 升级与迁移指南

本文统一说明从旧版升级到当前 **6.7.1** 的最终改法，按模块执行，无需先逐个安装中间版本。每节标明相关变更首次引入的版本；已完成的步骤可以跳过。6.6 的可选自动转义已被 6.7 的默认行为取代，当前代码不要再调用模式接口。本系列的修改汇总见[版本更新记录](#版本更新记录)。

| 当前应用版本 | 需要重点检查的迁移 |
| --- | --- |
| 6.7.0 | 按需接入 CSRF；已完成的模板迁移无需重复 |
| 6.6.x | 删除模式和安全模式接口，迁移所有模板的默认自动转义，重建编译缓存 |
| 6.5.3 | 内部类与模板辅助函数、Cookie、语言重置、Sentinel、入口构造时机、默认自动转义 |
| 6.5.0–6.5.2 | 按补丁版本补齐 JSON 插件/路由配置（6.5.1）、请求类型前缀（6.5.2）、语言资源（6.5.3），再检查后续各项 |
| 6.4.x | 调度异常与 Route、JSON/语言资源、类名与辅助函数、Cookie、默认自动转义 |
| 6.3.x | 模板实例、调度异常与 Route、JSON/语言资源、类名与辅助函数、Cookie、默认自动转义 |
| 6.2.1–6.2.2 | 6.2.1 检查新增锁参数；两者均需迁移 Request/Response、模板实例及后续各项 |
| 6.2.0 | 数据库重试和锁参数、Request/Response、模板实例，以及后续各项 |
| 6.0–6.1.x | 配置和文件边界、验证码和日志、数据库，以及后续各项 |
| 5.x 及更早版本 | 全部章节，先迁移命名空间控制器、插件类名和 Composer 加载 |

阅读顺序：[安装与加载](#安装与加载) → [控制器与调度](#控制器与调度) → [模板](#模板迁移) → [配置与语言](#配置json与语言资源) → [数据库与 Redis](#数据库与-redis) → [会话与安全](#cookie会话与安全) → [部署验证](#部署与验证)。

<a id="60-安装与加载"></a>
<a id="62-安装"></a>
<a id="63-安装与自动加载"></a>
<a id="64-安装"></a>
<a id="65-安装"></a>
<a id="66-安装与部署"></a>

### 安装与加载

当前核心要求 PHP 8.0+。插件依赖通过 Composer 的 `suggest` 声明，应用应按实际使用将扩展或包加入自己的依赖：Db 使用 PDO 及相应驱动，Redis 使用 phpredis，Http 使用 Guzzle，Captcha 使用 Gregwar Captcha、GD、mbstring、fileinfo 和活动会话，Csrf 使用 Session。Sentinel 模式需要 phpredis 6.0+，详见[按需安装依赖](docs/plugins.md#按需安装依赖)。

```bash
composer require hetao29/slightphp:^6.7.1
```

入口统一加载 `vendor/autoload.php`，Request/Response 和插件通过 Composer PSR-4 按需加载；仅手动引入 `SlightPHP.php` 不足以支持当前调度。删除旧的 `plugins/S*.php` 手动引用以及 `SLIGHTPHP_PLUGINS_DIR`、旧辅助函数文件的依赖，更新部署包中的自动加载文件。

旧的 `^6.2`、`^6.3`、`^6.4` 等约束均可能安装到后续 6.x，不能据此保留旧接口。应用项目仍可管理自己的锁文件；6.7 起框架仓库忽略且不再跟踪 `composer.lock`，本地文件保留。部署、缓存重建和重启步骤统一见[部署与验证](#部署与验证)。

<a id="60-插件类名"></a>

### 插件类名与移除项

涉及版本：6.0、6.5.1、6.6。

旧的全局插件类名及兼容别名均已删除，必须使用命名空间类，或在调用处使用 `use` 导入：

| 旧类名 | 当前替代 |
| --- | --- |
| `SCaptcha` | `SlightPHP\Captcha` |
| `SConfig` | `SlightPHP\Config` |
| `SDb` | `SlightPHP\Db` |
| `SError` | `SlightPHP\Error` |
| `SHttp` | `SlightPHP\Http` |
| `SJson` | PHP 原生 JSON 函数（6.5.1 起移除 `SlightPHP\Json`），见 [JSON 迁移](#json-插件移除) |
| `SLanguage` | `SlightPHP\Language` |
| `SLog` | `SlightPHP\Log` |
| `SRedis` | `SlightPHP\Redis` |
| `SRoute` | `SlightPHP\Route` |
| `STpl` | `SlightPHP\Tpl` |

同时替换 `new`、静态调用、继承、类型声明、`instanceof` 和配置中存储的类名。数据库的表记录、查询及事务操作现在都在 `SlightPHP\Db`；`SlightPHP\Internal\Db` 不再存在。

`SCache`、`SLunar`、`SMail`、`SSphinx`、`SUtil` 和 `SThumbnail` 已移除，没有内置替代别名。请根据应用功能另选维护中的依赖并迁移接口和配置。

### 控制器与调度

依次检查控制器文件与签名、请求/响应处理、入口失败分支和 Route 策略。

<a id="60-控制器和路由"></a>

#### 控制器文件与入口命名

涉及版本：6.0。

框架只支持命名空间控制器。将 `app/user/profile.page.php` 中的 `user_profile::pageUpdate()` 改为 `app/User/ProfileController.php` 中的 `User\ProfileController::Update()`；`/user/profile/update` 的 URL 可保持不变。多级目录同样要按命名空间首字母大写规则迁移。可用 `setControllerSuffix()` 和 `setAppNamespace()` 配置后缀及根命名空间；后者也影响磁盘路径。

删除 `setModes()`、`getModes()`、`setDefaultType()`、`getDefaultType()` 和 `$defaultType` 的调用。无 `Request-Type` 请求头时，入口方法不再默认添加 `page` 前缀；有该头且值在允许列表中时，仍会添加对应前缀。注意检查 AJAX、内部调用、CLI 任务及自定义错误页面。框架仍不根据 GET/POST 自动限制方法。

<a id="62-请求路径"></a>

#### 请求路径与文件边界

涉及版本：6.2、6.5。

路径默认最多 10 段，包含区域、page、entry 和路径参数；从 6.5.0 起，超过上限时 `run()` 抛出 `SlightPHP\Exception::PATH_TOO_DEEP`，不加载控制器。正常请求、CLI 和内部调用均受限制。已有路由需要更多路径段时，在初始化阶段按实际需要调整：

```php
SlightPHP::setMaxDepth(15); // 必须大于等于 1
```

未超限的路径参数保持原样，默认区域、页面和入口的补全不额外计数。详情见[核心调度文档](docs/framework.md)。

<a id="63-控制器构造函数与入口"></a>

#### 控制器构造函数与入口签名

涉及版本：6.3。

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

<a id="63-请求数据读取"></a>

#### 请求数据读取

| 原用法 | 当前用法 |
| --- | --- |
| `$inPath` / `$inPath[3]` | `$request->route` / `$request->route[3]` |
| `$_GET` / `$_POST` | `$request->get` / `$request->post` |
| `$_REQUEST` | `$request->request`，保留 PHP 配置决定的合并来源、顺序及数组语义 |
| `$_COOKIE` / `$_FILES` | `$request->cookie` / `$request->files` |
| `$_SERVER['REQUEST_METHOD']` | `$request->getMethod()` 或 `$request->server['request_method']` |
| `$_SERVER['HTTP_CONTENT_TYPE']` / `$_SERVER['CONTENT_TYPE']` | `$request->header['content-type'] ?? null` |
| `file_get_contents('php://input')` | `$request->getContent()` 或 `$request->rawContent()` |

超全局变量仍可使用；Request 保存的是构造时的快照，修改对象中的数组不会更新超全局变量，构造后再修改超全局变量也不会更新已有快照。`server` 的键统一为小写，`header` 的键统一为小写并使用连字符。

JSON 正文不会自动进入 `post`，需先检查 `getContent()` 是否为 `false`，再自行解码和校验。路径数组不含查询串且不做 URL 解码。`run('/user/profile/edit/123')` 创建的 Request 仍读取当前 PHP 请求数据，只将 `route` 设置为显式路径，不会为内部调用合成新的 GET、POST 或请求正文。

<a id="63-响应输出与返回值"></a>

#### 响应输出与返回值

涉及版本：6.3、6.5。

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
| 找不到可调用入口，或路径超出深度限制 | 抛出 `SlightPHP\Exception` |

从 6.5.0 起，应用入口应捕获 `SlightPHP\Exception` 处理调度失败，使用 `is_string($result)` 判断是否输出返回正文。控制器返回的 `false` 属于正常业务结果，已经结束响应时返回 `null`；两者均不表示调度失败。完整入口示例见[快速开始](docs/getting-started.md)及[异常迁移](docs/exceptions.md)。

`end()` 和成功的 `redirect()` 只标记本实例结束，不调用 `exit`，也不停止后续 PHP 语句；使用 `return $response->end(...)` 或 `return $response->redirect('/login')` 结束控制器执行。`write()` 不标记结束，混用 `write()` 和返回同一正文会导致重复输出。响应开始输出非空正文后，不能再通过该实例修改响应头、状态码或 Cookie；响应操作失败会返回 `false`，应按业务需要处理。

<a id="65-核心失败改为异常"></a>

#### 调度失败与异常处理

涉及版本：6.5。

将 `$result === false` 的调度失败分支改为捕获 `SlightPHP\Exception`。应用入口负责状态码、响应头和错误正文，核心不会自动生成 404/405/500 页面：

```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
SlightPHP::setDefaultZone('home');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
SlightPHP::setPathInfo(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

try {
    $result = SlightPHP::run();
    if (is_string($result)) {
        echo $result;
    }
} catch (\SlightPHP\Exception $error) {
    http_response_code($error->getStatusCode());
    if ($error->getCode() === \SlightPHP\Exception::METHOD_NOT_ALLOWED && !empty($error->getContext()['allowedMethods'])) {
        header('Allow: ' . implode(', ', $error->getContext()['allowedMethods']));
    }
    echo $error->getStatusCode() === 404 ? '页面不存在' : '请求失败';
}
```

`getCode()` 是原因码，`getStatusCode()` 是建议的 HTTP 状态码；路径、控制器和允许方法等诊断信息通过 `getContext()` 读取。不要直接向客户端展示包含文件路径的异常消息。完整原因码见 [核心异常与处理](docs/exceptions.md)。

需要检查的调用点包括：

- 主入口、CLI 任务以及 `run('/...')` 内部调用。
- 模板 `part`：未匹配入口和重复内部调用现在抛出异常，可选模块需由调用方按原因码处理；空路径仍不调用入口。
- `getZoneAlias()`、`getPageAlias()`：别名未设置时抛出 `ALIAS_NOT_FOUND`，有效别名仍返回原字符串。
- 超出路径深度、应用目录无效及控制器文件/类/方法不可用等情况。

业务入口主动返回的 `false` 仍是正常业务返回值；响应通过 `end()` / `redirect()` 结束后，调度返回 `null`。这两种结果均不能作为调度失败标志。控制器文件、构造函数、入口及注解构造函数自身的异常原样传播。

<a id="65-请求类型前缀校验652"></a>

#### 请求类型前缀校验

涉及版本：6.5.2。

候选方法名以 `setAllowRequestTypes()` 配置的类型开头时，当前 `Request-Type` 必须匹配。缺失或不匹配会立即抛出 `SlightPHP\Exception::METHOD_NOT_ALLOWED`（405），先于方法存在性、Route 注解与控制器构造，也不会继续尝试浅层候选。普通 HTTP 请求、显式 URL、`run('/...')` 与模板 `part` 均执行此校验。

例如，原来不带请求头直接访问 `/user/order/ajaxSave` 的调用会被拒绝；应使用 `/user/order/save` 并发送 `Request-Type: ajax` 来调用 `ajaxSave`。内部调用和 CLI 不会自动补充请求类型，模板 `part` 沿用当前请求头，升级时也需核对。

类型列表统一转为小写并过滤空字符串；请求头先去除首尾空白再转为小写。前缀按配置顺序首匹配，不要求单词边界，因此也需检查名称恰好以 `page`、`json` 等已配置类型开头的普通入口。自定义类型重叠时将较长类型排在前面；从列表移除某类型也会移除对应的前缀校验。该校验不代替 HTTP 方法、登录、对象权限或 CSRF 校验。

请求类型不匹配时，异常上下文包含 `requestType`、`requiredRequestType`，而 `allowedMethods` 为空数组；入口应仅在 `allowedMethods` 非空时输出 HTTP `Allow` 头，不要把 `ajax`、`part` 等类型写入该头。上方的入口示例已包含判断；详细规则见[方法前缀](docs/framework.md#方法前缀与-http-方法)与[异常说明](docs/exceptions.md)。

<a id="65-route-方法注解"></a>
<a id="66-控制器构造与示例验证"></a>

#### Route 方法与入口构造时机

涉及版本：6.5、6.6、6.7.1。

在真实入口上声明允许的 HTTP 方法：

```php
namespace Home;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

class MainController
{
    #[Route(method: 'POST')]
    public function Save(Request $request, Response $response)
    {
        return $response->end('saved');
    }
}
```

也可使用 `#[Route(methods: ['GET', 'POST'])]`；`method` 与 `methods` 同时设置时取并集，统一为大写并去重。未声明注解或未指定方法列表时不限制 HTTP 方法。GET 不自动允许 HEAD，CLI 未提供 HTTP 方法时会被有限制的入口拒绝。

限制在构造控制器之前检查，不匹配时抛出 `METHOD_NOT_ALLOWED`，由入口返回 405 和 Allow。继承方法使用实际声明上的注解，子类覆盖按覆盖方法处理；Request-Type 前缀使用实际调用方法上的注解。6.7.1 可通过 Route 的 `csrf` 参数按需启用 token 校验，见[CSRF 接入](#按需启用-csrf)；登录、对象权限和业务校验仍由应用负责。

核心现在在实例化控制器前确认入口存在且为 public。缺失或非公开入口不再执行构造函数，不能依赖构造函数为这些请求生成响应或产生副作用；正常入口、候选回退、请求类型及 Route HTTP 方法校验继续生效。

同一入口可以声明其他类型的属性，但重复 Route 注解会抛出 `InvalidArgumentException`。

<a id="65-自定义-url-路由"></a>

#### 自定义 URL 路由

静态 `Route::set()` 和 `setConfigFile()` 继续可用，与方法注解共用 `Route` 类。规则只匹配路径，忽略查询串；重复前导斜杠不会被当作主机名。占位符按完整名称替换；6.5.1 的 JSON/JSONC 数组按加载顺序追加，不覆盖先前规则。

新增公开的 `Route::parse(?string $path = null)`。入口若已剥离 `index.php` 或站点前缀，应在注册规则后调用 `Route::parse($path)`，匹配规范化后的实际路径。配置不可读、JSON 解析失败、规则或正则无效时抛出明确异常；检查已有配置，尤其是空 pattern 和占位符约束类型。完整示例见 [自定义 URL 路由](docs/routes.md)。

<a id="65-调度优化与缓存边界"></a>

#### 调度缓存边界

普通候选文件缺失使用分支继续查找，类或入口不可用时只记录诊断，最终失败才构造异常。普通文件缺失不再产生中间异常链，文件越界等校验仍保留原因。候选回退不会吞掉业务异常。

应用目录在设置或首次使用时按目录路径规范化并缓存，调度不再重复执行 `is_dir()`；候选仍需通过普通文件与目录边界检查。方法元数据先按实际控制器和入口查缓存，未命中才反射，无注解和非公开方法也缓存；HTTP 方法是否允许仍逐次判断，无效注解不会被缓存为无限制。

**普通 PHP-FPM 的元数据缓存只在当前请求内复用，不会跨请求保留。** 本版本没有引入持久化注解缓存。常驻进程应按发布流程重启以加载新代码，OPcache 刷新也由发布流程管理。

### 模板迁移

涉及版本：6.2 的目录和编码规则、6.4 的实例变量与 part、6.6 的辅助函数、6.7 的默认上下文自动转义。当前模板及示例统一使用默认 `{` / `}` 分隔符。

<a id="64-模板变量与实例"></a>

#### 模板变量与实例

`Tpl::$_tpl_vars` 改为受保护的实例属性，`assign()` 和 `fetch()` 改为实例方法。将原有静态赋值及加载调用改为同一个对象：

```php
// 升级前
\SlightPHP\Tpl::assign('title', '示例');
\SlightPHP\Tpl::fetch('index.tpl');

// 升级后，模板目录、编译目录及分隔符仍通过静态配置设置。
$tpl = new \SlightPHP\Tpl();
$tpl->assign('title', '示例');
$tpl->fetch('index.tpl');
// 也可由 render 初始化默认配置并分配变量。
$tpl->render('index.tpl', ['title' => '示例']);
```

直接写入公开静态变量表的代码需改用 `$tpl->assign()`；不再提供从外部读取变量表的接口。检查自定义子类覆盖的 `assign()`、`fetch()` 是否仍声明为 static，以及是否直接使用 `self::$_tpl_vars`。依赖不同对象共享变量的业务需改为复用同一个实例或显式传递数据。

模板中的 `{include "child.tpl"}` 和 `{include $child}` 写法保持不变，编译器自动传入当前实例，多层子模板共享父模板数据。PHP 代码直接调用辅助函数时改为 `\SlightPHP\Tpl\Func::include('child.tpl', $tpl)`；自定义模板函数应显式接收或持有需要的实例。同一个实例在连续调用 `assign()`、`render()`、`display()`、`fetch()` 时保留变量，包括模板循环产生的赋值；常驻进程按请求创建新实例。

<a id="66-内部类与模板辅助函数迁移"></a>
<a id="65-内部类与模板辅助函数迁移"></a>

#### 内部类与模板辅助函数

数据库结果类与适配器分别从 `SlightPHP\DbData`、`SlightPHP\DbPDO` 迁移为 `SlightPHP\Db\Data`、`SlightPHP\Db\PDO`，文件位于 `plugins/Db/`。直接使用旧类名或文件路径的应用需更新；Db 查询接口及读取 COUNT 第一行的行为保留。

模板辅助函数由全局函数改为 `SlightPHP\Tpl\Func`、`SlightPHP\Tpl\Modifier` 的静态方法：

```php
\SlightPHP\Tpl\Func::include('child.tpl', $tpl);
\SlightPHP\Tpl\Func::part('/widgets/template/card/demo');
$safeHtml = \SlightPHP\Tpl\Modifier::escape($value);
```

分别替代 `tpl_function_include()`、`tpl_function_part()`、`tpl_modifier_escape()`；其余内置辅助函数同样去掉前缀后作为静态方法调用。删除旧文件的手动 require，使用 Composer PSR-4 自动加载，并重新生成自动加载文件。旧全局函数和旧 Db 类名不提供别名。已有编译模板会按文件头自动重建，部署用户需有重写权限。

不再通过 `tpl_function_*` / `tpl_modifier_*` 前缀查找自定义扩展。模板中的内置标签和修饰器写法继续可用；自定义扩展需改为同名普通 PHP 函数，并在应用中显式加载。例如 `{myhelper($value)}` / `{$value|myhelper}` 对应 `myhelper()`，不再隐式查找旧的前缀函数。自动模式的函数与修饰器应返回数据，最终编码及限制见[上下文自动转义](docs/templates.md#上下文自动转义)。

<a id="67-安装与接口移除"></a>
<a id="67-安全模式与编译入口移除"></a>

#### 删除模板模式接口

6.7 将上下文自动转义设为统一行为。即使原来从未开启自动模式，也必须检查依赖原样输出的模板。

删除所有模式设置和读取调用，包括 `setContextMode(true)`、`setContextMode(false)` 和 `getContextMode()`；这两个方法不提供兼容别名。直接创建模板实例：

```php
$tpl = new \SlightPHP\Tpl();
$tpl->render('page.tpl', ['name' => '<访客>', 'query' => '中文 & 字符']);
```

```html
<p>{$name}</p>
<input value="{$name}">
<a href="/search?q={$query}">搜索</a>
```

`assign()`、`render()`、`fetch()`、`display()`、默认 `{` / `}`、include 共享变量和 part 的独立实例继续可用。所有实例均按实际输出上下文编码，不能通过模式开关恢复旧的原样输出。

删除 `Tpl::$safe_mode` 和 `Tpl::setSafeMode()` 的访问或调用；不再提供安全模式开关，也不再将原生 PHP 标签替换成注释。静态的 `<?php ... ?>` / `<?= ... ?>` 保留为模板字面量，不作为 PHP 代码执行；动态模板逻辑继续使用 `{...}` 表达式及控制标签。模板是可信代码，上下文编码不将其变成沙箱。

`Tpl::_compile()` 已移出入口类，源码编译统一由 `Tpl\Compiler::compileSource($source, $left, $right)` 完成分词和代码生成；应用仍通过 render/fetch 使用编译缓存。缓存文件头不再包含安全模式配置，旧配置生成的缓存自动失效并重建。

<a id="67-手工编码与原始数据"></a>
<a id="66-tpl-自动转义迁移启用时不兼容"></a>
<a id="66-完整-url-与业务编码仍需保留构造逻辑"></a>
<a id="65-tpl-自动转义迁移"></a>

#### 原始数据与上下文自动转义

模板应接收原始数据。仅用于 HTML 输出防护的 `htmlspecialchars()`、`htmlentities()`，以及独立 URL 组成部分的 `urlencode()`、`rawurlencode()` 通常可以移除：

| 旧写法 | 当前推荐写法 |
| --- | --- |
| `{htmlspecialchars($address['address'] ?? '')}` | `{$address['address'] ?? ''}` |
| `<a href="/search?q={urlencode($q)}">` 或使用 `rawurlencode($q)` | `<a href="/search?q={$q}">` |

普通函数返回值没有编码意图标记，保留预编码可能再次编码。例如原始值 `A&B <b>` 直接插值生成 `A&amp;B &lt;b&gt;`；先 `htmlspecialchars()` 再插值则会生成 `A&amp;amp;B &amp;lt;b&amp;gt;`。已编码的存量数据不会自动还原，应在明确其来源后迁移。

全部内置 Modifier 仍可用。末尾 HTML `|escape` 有专门的编码意图处理，可以保留；它与普通 `htmlspecialchars()` 的返回值不同。URL escape 的去重只适用于已有前缀的 HTML URL 属性组成部分，不能推广到任意上下文。修饰链先转换数据，再按实际位置编码。

完整 URL 仍需由业务代码正确构造，例如 `http_build_query()`、路径编码和签名编码不能批量删除。`<a href="{$url}">` 将值视为完整 URL，检查协议并执行 HTML 属性编码，不会替其中每个参数重新编码。JS 字符串中的 URL 仍需保留 URL 构造，再由模板执行 JS 字符串编码。URL 组成部分的空格采用 `%20`，与 `urlencode()` 的 `+` 不同，精确字节、缓存键与签名需核对。

例如 `q = 'a b&'` 时，直接插值输出 `a%20b%26`；保留 `urlencode()` 再插值则会得到 `a%2Bb%2526`。自动编码依据语法位置，不推断业务含义：`<script>const target="/search?q={urlencode($q)}";</script>` 中仍需保留 URL 构造编码，再由模板执行 JS 字符串编码。完整 URL 中已有的 `%xx` 不会自动解码。

<a id="67-可信-html属性与输出函数"></a>

#### 可信 HTML 与动态属性

只有已确认可信且适合当前位置的原文使用末尾 `|raw`：

```html
<article>{$document['html']|raw}</article>
<p {if $hidden}hidden{/if}>{$message}</p>
```

文档示例中的 `document['html']` 必须来自应用自己的安全渲染器，不能直接传用户输入。raw 会跳过最终编码，并继续影响后续的真实上下文；不要用它批量绕过迁移。

自动模式拒绝动态标签名、属性名及动态标记。不要写 `<p {$hidden ? 'hidden' : ''}>`，应像上例那样用条件输出完整静态属性。动态属性值仍可直接插值。

普通表达式、函数及 Stringable/JSON 转换回调必须返回数据，直接 echo、改变输出缓冲或嵌套渲染改变已选上下文会报错。需要输出模块 HTML 时使用模板 include/part；part 只能位于 HTML 文本中，并临时收集可信片段。模块异常时丢弃该失败片段的缓冲，之前已提交的页面内容保留；业务副作用不回滚。PHP 直接调用 `Tpl\Func::part()` 仍直接输出，不管理调用方缓冲。

<a id="67-json-数据块与翻译"></a>

#### JSON 数据块与模板翻译

JSON 数据块直接传数组/对象，不要先 json_encode 后再把 JSON 字符串当作数据输出：

```html
<script type="application/json">{$messages}</script>
<script>const messages = {$messages};</script>
```

第一种输出标准 JSON，第二种输出带表达式括号的 JS 值。将已有 JSON 字符串直接插入数据块会得到 JSON 字符串，而不是原来的对象；优先在业务层保留结构化数据。

模板翻译应返回原文，让 Tpl 执行最终编码。samples 的模板已从 `SampleI18n::e()` 改为 `SampleI18n::t()`；PHP 直接拼接 HTML 的位置仍保留显式转义。`SampleI18n::url()` 构造完整 URL，`clientMessages()` 返回数组，由模板分别完成属性编码和 JSON 序列化。

<a id="64-part-的输出与缓冲"></a>

#### include 与 part 的输出边界

多层 include 复用当前模板实例的变量和输出 Context；part 调用控制器，控制器新建 Tpl 实例时变量独立。`part` 只可插入普通 HTML 文本位置，模块负责输出可信 HTML。

6.4.0 的 part 曾返回收集到的 HTML 字符串；当前 `\SlightPHP\Tpl\Func::part($path)` 直接输出模块内容，再输出其字符串返回值，函数自身返回 null。布尔值、数字、数组或对象不会作为 HTML 输出；需要原始结果时使用 `SlightPHP::run()`，需要捕获完整 HTML 时由调用方管理输出缓冲。

PHP 直接调用 `Tpl\Func::part()` 不管理缓冲；当前模板标签的 part 则会临时收集片段并跟踪输出上下文，异常时丢弃尚未提交的片段。模块自行创建的缓冲应配对关闭，包括异常分支。空路径不调用入口，未匹配入口抛出调度异常。

模块调用 `Response::end()` 后，片段输出保留，父页面继续渲染。已经提交的正文和业务副作用不会回滚，片段响应状态和响应头由外层页面统一管理。完整示例见 [Tpl 手册](docs/templates.md)。

### 配置、JSON 与语言资源

配置格式转换应修改文件内容及所有加载路径，不能只重命名扩展名。

<a id="60-config-配置迁移"></a>
<a id="62-配置文件"></a>

#### Config 配置格式与缓存

涉及版本：6.2。

`SlightPHP\Config` 仅支持 `.json` 和 `.jsonc`，不再解析 `.conf` 等旧格式。将分组转换为 JSON 对象、重复键转换为数组，并更新 `Db::setConfigFile()`、`Redis::setConfigFile()` 及 `Config::getConfig()` 等调用中的路径。`.json` 使用严格 JSON；需要注释时使用 `.jsonc`。

Config 已取消序列化文件缓存，只保留由 `Config::$CACHE` 控制的进程内缓存；旧的 `0SConfig_Cache_*` 文件不会再读取。关闭缓存不会清空已有内存结果，常驻进程更新配置后应保持缓存关闭或重启进程。

严格 JSON 和 JSONC 均不支持尾随逗号；Config 不检查配置文件修改时间。返回值与异常处理见 [Config 说明](docs/plugins.md#config)。

<a id="65-json-插件移除"></a>

#### JSON 插件移除

6.5.1 删除 `plugins/Json.php` 和 `SlightPHP\Json` 类，不保留兼容别名。移除 `use SlightPHP\Json;` 和手动引入该文件的代码，改为直接调用 PHP 原生函数：

| 原调用 | 原生替代 |
| --- | --- |
| `Json::encode($value, $flags, $depth)` | `json_encode($value, $flags, $depth)` |
| `Json::decode($json, $assoc, $depth, $flags)` | `json_decode($json, $assoc, $depth, $flags)` |
| `Json::error()` | `json_last_error()` |
| `Json::errorMsg()` | `json_last_error_msg()` |

原插件只是原生函数的封装，替换时保留现有参数、flags、depth 和错误处理即可。原 `decode()` 的 `$assoc` 默认值为 `false`；需要保留对象解码行为时可显式传入 `false`，尤其是同时使用 `JSON_OBJECT_AS_ARRAY` 时。

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => '你好'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$data = json_decode('{"ok":true}', true, 512, JSON_THROW_ON_ERROR);
```

`JSON_THROW_ON_ERROR` 模式下编解码错误抛出 `\JsonException`，由应用捕获处理；未启用时使用 `json_last_error()` / `json_last_error_msg()` 查询错误。升级时同时检查控制器、模板修饰器、CLI 任务与测试中的旧类引用。

<a id="65-路由配置改为-jsonjsonc"></a>

#### 路由配置转换为 JSON/JSONC

6.5.1 的 `Route::setConfigFile()` 不再解析 INI，改为调用 `Config::parse()`；PHP 的 `Route::set()` 和方法注解 API 保持不变。

将原文件的每个 INI 节转换为一个 JSON 对象，再按原顺序放入顶层数组，并将加载路径改为 `.json` 或 `.jsonc`：

```json
[
  {
    "pattern": "^\\/r-json\\/:slug$",
    ":slug": "[A-Za-z0-9_]{1,40}",
    "zone": "lab",
    "page": "route",
    "entry": "entry"
  }
]
```

```php
Route::setConfigFile(__DIR__ . '/../config/routes.json');
```

保留 `pattern`、`:name`、`zone`、`page`、`entry` 字段，JSON 字符串中的反斜杠需要双写。顶层对象（包括命名分组或 `{ "routes": [...] }`）和数组中的非对象元素均不接受。多文件仍按加载顺序追加，首条匹配规则优先，所有规则校验通过后才追加。

INI 的常量或环境变量插值不会被 JSON 自动执行；这类动态规则可以继续使用 PHP 的 `Route::set()`。JSON 语法错误现在抛出 `JsonException`，不再统一为 INI 解析的 `RuntimeException`；`.ini` 扩展名会被拒绝。Config 默认缓存且不检查文件修改时间，常驻进程应只注册一次并在配置发布后重启。完整规则与缓存说明见[自定义路由文档](docs/routes.md#jsonjsonc-规则)。

<a id="65-language-语言文件改为-jsonjsonc653"></a>
<a id="66-language-语言切换与请求重置"></a>

#### 语言文件转换与请求重置

涉及版本：6.2、6.5.3、6.6。

6.5.3 的 `Language::tr()` 与 Route 一样使用 `Config::parse()`，不再读取 `locale/zone.ini`。将原 INI 词条转换成 JSON 对象，保存到相同语言目录中的 `locale/zone.json` 或 `locale/zone.jsonc`；不能仅修改文件扩展名。例如，`languages/en/main.json`：

```json
{
  "welcome": "Welcome to SlightPHP",
  "任务已添加": "Task added"
}
```

顶层必须为对象，所有词条值必须为字符串；原有数字、布尔值等内容如需作为翻译，应显式写为字符串。空对象和空字符串有效。`.json` 使用严格 JSON，`.jsonc` 允许行注释和块注释；都不支持尾随逗号。同名文件优先使用 `.json`，不与 `.jsonc` 合并。

`setLanguageDir()`、`setLocale()`、`tr($source, $zone)` 的调用方式保持不变。语言列表、主语言回退、缺失词条返回原文及目录边界校验保持不变；首次翻译前应设置语言目录和 locale。6.6.0 进一步修复 setter 的缓存失效行为并新增 `resetRequest()`，用法见下文。JSON 格式错误抛出 `JsonException`，非对象顶层或非字符串词条抛出 `InvalidArgumentException`，无法读取已有文件时抛出 `RuntimeException`，应修复资源文件，不能依赖解析失败后返回原文。

Language 的已加载词条仍保留在进程内；首次加载文件也受 `Config::$CACHE` 影响。发布时同步部署所有语言文件，常驻进程按发布流程重启。完整格式见 [Language 插件](docs/plugins.md#language)，可运行示例见 `/lab/language/entry`。

`Language::setLocale()` 会清除候选语言缓存，首次翻译之后切换语言也立即生效；`setLanguageDir()` 会清除已加载词条，避免跨目录复用。新增 `resetRequest()`，清除强制语言及候选列表，保留应用配置与词典缓存：

```php
\SlightPHP\Language::resetRequest();
try {
    \SlightPHP\Language::setLocale('zh-cn');
    echo \SlightPHP\Language::tr('welcome');
} finally {
    \SlightPHP\Language::resetRequest();
}
```

串行常驻请求应在每次请求开始、设置本次语言前重置。该接口只重置 Language，不重置路由、会话或其他插件，也不提供同进程并发请求隔离。Config 及词典仍有进程内缓存，更新资源后按部署流程重启；完整边界见 [Language 插件](docs/plugins.md#language)。

语言文件必须位于语言根目录内。Language 忽略非法语言标签、非法 q 值及 `q=0`，请求头最多读取 8192 字节、前 32 项，按出现顺序选择，不按 q 值排序。发布时同步部署所有 locale/zone 资源。

<a id="63-数据库调用"></a>

### 数据库与 Redis

已适配相应版本的调用无需重复修改；直接引用 Db 内部类时，还需完成[内部类迁移](#内部类与模板辅助函数)。

<a id="63-621移除自动重试"></a>

#### 数据库失败与自动重试移除

涉及版本：6.2.1。

`Db::query()` 现在只接受 `$sql, $params = []`。移除调用中的第三个 `$retry` 参数，包括位置参数和命名参数 `retry:`；自定义子类也应检查覆盖的方法签名。

```php
// 升级前
$rows = $db->query('SELECT * FROM t_user WHERE pk_user = ?', [$id], 1);
// 升级后
$rows = $db->query('SELECT * FROM t_user WHERE pk_user = ?', [$id]);
```

查询失败会保留错误信息、触发 `E_USER_WARNING` 并返回 `false`，不会自动重连或重试；`select()`、`selectOne()` 及增删改操作同样不再依靠自动恢复。应用需明确检查失败结果，并按任务语义决定是否重新建立连接、恢复会话设置或重试完整事务；连接重建不能恢复已有事务和锁，写入结果不确定时应先核实实际结果。

直接使用数据库适配器（现为 `SlightPHP\Db\PDO`，原 DbPDO）的代码需移除对 `$connectionError` 的依赖；该公开标记已删除，改为检查操作返回值，并通过 `errno()` / `error()` 读取错误。Db 的错误协议保持不变，成功查询不保证清空以前的错误，应优先依据本次返回值判断是否成功。

<a id="63-622查询锁参数"></a>

#### 数据库查询锁参数

涉及版本：6.2.2。

`select()` 和 `selectOne()` 当前参数顺序均为 `table, condition, item, groupby, orderby, leftjoin, hits, for`。`select()` 新增末尾 `$for`；`selectOne()` 新增末尾 `$hits` 和 `$for`，已有位置参数调用保持兼容。覆盖这些方法的自定义子类应同步可选参数签名。

```php
// $db 已初始化，并在同一连接上成功开启事务。
$row = $db->selectOne('t_invoice', ['pk_invoice' => $id], for: 'UPDATE');
// 检查查询结果，执行所需更新后提交；失败路径回滚。
```

仅精确的大写 `'UPDATE'` / `'SHARE'` 追加 `FOR UPDATE` / `FOR SHARE`，传入 `'FOR UPDATE'`、小写或其他值均不会追加。锁子句位于主查询 LIMIT 之后，COUNT 查询不加锁；`selectOne()` 继续限制一条并关闭计数。调用方负责使用同一连接管理事务，并确认数据库支持所选语法。详情见[数据库说明](docs/plugins.md#数据库失败处理)。

<a id="66-redis-sentinel"></a>

#### Redis Sentinel

涉及版本：6.6。

`engine: "sentinel"` 现在使用 RedisSentinel 按监控服务名发现主节点，不再创建 RedisArray。原来使用该模式名但把数据节点地址当作 `host` 的配置必须迁移：`host` 应为 Sentinel 地址列表，`options.service` 是 Sentinel 监控的服务名；认证分别使用 `sentinel_auth` 和 `auth`。完整 JSON 配置见 [Redis 插件](docs/plugins.md#redis)。

连接后会验证主节点身份；断线或 READONLY 后，后续调用重新发现当前主节点，不重放失败命令。应用仍需判断失败并处理结果不确定的写入，不能依赖框架自动重试写命令。

### Cookie、会话与安全

Cookie 和会话参数应在发送 Cookie 或启动会话前明确配置；CSRF 按入口需要启用。

<a id="66-cookie-默认策略与会话"></a>

#### Cookie 默认策略与会话

涉及版本：6.6。`cookie()` 与 `rawcookie()` 均采用以下默认策略。

`Response::cookie()` 省略安全选项时，现在采用 `Secure=true`、`HttpOnly=true`、`SameSite=Lax`。纯 HTTP 开发站点必须明确关闭 Secure，否则浏览器不会通过普通 HTTP 回传 Cookie：

```php
// HTTPS 生产环境。
\SlightPHP\Response::setCookieDefaults();

// 仅用于纯 HTTP 开发环境。
// \SlightPHP\Response::setCookieDefaults(secure: false);

// 启动会话前复用 Cookie 策略。
session_set_cookie_params(
    ['lifetime' => 0, 'path' => '/', 'domain' => '']
    + \SlightPHP\Response::getCookieDefaults()
);
session_start();
```

单次 `cookie()` 调用可显式覆盖默认值；需要浏览器脚本读取的特定 Cookie 应显式设置 `httponly: false`。跨站 Cookie 的 `SameSite=None` 必须与 `Secure=true` 同时使用，不合法组合抛出 `ValueError`。会话不会因设置 Response 默认值而自动更新，需在 `session_start()` 前配置；HTTPS 与可信反向代理的完整示例见 [生产部署](docs/deployment.md#cookie与会话安全)。不要直接信任客户端传来的 `Forwarded` / `X-Forwarded-Proto`。

<a id="671-按需启用-csrf"></a>

#### 按需启用 CSRF

6.7.1 新增 `SlightPHP\Csrf`，入口通过 `#[Route(method: 'POST', csrf: true)]` 启用；字符串形式（如 `csrf: '--csrf--token'`）可同时指定表单字段与请求头名称。省略或设置 false 的入口保持原有行为。

应用先启动 Session，再通过 `Csrf::token()` 手写隐藏字段，或用 `Csrf::tokenInput()` 生成 HTML。Tpl 支持预先传入 `{$csrf_input|raw}`，也支持直接调用 `{\SlightPHP\Csrf::tokenInput()|raw}`。公共静态变量 `$fieldName` / `$sessionKey` 默认分别为 `_csrf` / `_slightphp_csrf`。无效 token 抛出 `CSRF_INVALID`（403），Session 未启动抛出 `CSRF_SESSION_NOT_STARTED`（500），由入口生成响应。

HTTP 方法检查通过后、构造控制器之前执行 CSRF 校验；应用需自行生成异常响应。完整接入、Ajax 和 token 更新示例见 [CSRF 使用手册](docs/csrf.md)。

<a id="62-验证码"></a>

#### 验证码会话与一次性校验

涉及版本：6.0、6.2。

验证码实现使用 `Gregwar\Captcha\CaptchaBuilder`；旧 `SimpleCaptcha` 专有属性和方法不再可用，检查图片扩展、字体和输出流程。`Captcha::check()`、`del()` 使用当前会话。

每个会话前缀只保留最新验证码，刷新会替换旧记录；默认有效期为 300 秒，可在生成前设置 `Captcha::$ttl` 为正整数。`check()` 成功后立即移除验证码，同一业务流程只校验一次。生成及校验时需保持活动会话。

旧的“前缀 + 验证码”会话记录不再接受，升级后应提示用户重新获取验证码。不同用途可使用不同前缀隔离。并发一次性校验依赖会话后端的互斥锁配置，详见 [Captcha 说明](docs/plugins.md#captcha)。

<a id="62-错误与日志"></a>

#### 错误输出与日志格式

涉及版本：6.2。

`Error::$LOGARGS` 默认为 `false`，错误日志和控制台输出不再包含调用栈参数；如需恢复，可显式设为 `true`，但字段名脱敏不能覆盖所有敏感值。错误和异常消息本身的数据不受此开关影响。HTML 错误输出及 Log 的网页控制台注释会转义 HTML；JSON 和纯文本接口应由应用设置正确的 `Content-Type`。

`Log::$LOGTYPE = 'json'` 现在输出 JSON Lines，每条记录包含 `time`、`level` 和始终为参数数组的 `message`。升级日志采集及解析程序，为新格式使用独立日志文件或先轮转旧文件。默认 `text` 模式保留多行文本。格式示例见 [Log 说明](docs/plugins.md#log)。

<a id="60-升级检查"></a>
<a id="62-验证"></a>
<a id="62-模板与语言文件"></a>
<a id="63-升级验证"></a>
<a id="64-部署与验证"></a>
<a id="65-升级验证"></a>
<a id="67-编译缓存与升级验证"></a>

### 部署与验证

模板源文件和编译文件必须分别位于模板目录和编译目录内；检查是否依赖跨目录路径或指向目录外的符号链接。新建编译目录默认 `0700`，新建或重新写入的编译文件为 `0600`；已有目录和命中缓存不会自动调整权限。目录、分隔符和强制编译选项仍为静态共享配置，安全模式选项已移除。内置 `escape` 转义单双引号并替换非法 UTF-8。

编译格式升级为 v10，统一保留 `.context.php` 文件后缀。6.6 的上下文编译文件会自动重建；旧兼容文件不再执行。编译目录必须可写，常驻进程需重启，并按发布流程刷新 OPcache。缓存不保存变量或最终页面输出，完整计划命中后仍无需 JS 词法扫描。

大模板首次编译仍有分析成本，建议预热模板及所需上下文变体。上下文编码及校验仍有运行成本，不能将重复值编码复用的收益推广到所有模板；上线前分别评估首次编译与缓存命中情况。

发布时同步应用代码、依赖、配置和语言资源。常驻进程重启以加载新类和配置，PHP-FPM 的调度元数据只在请求内复用；OPcache 由部署流程刷新。模板、配置和语言缓存不能替代代码迁移，原 PAGE 文件和旧类名不会自动恢复工作。

升级后按实际使用范围验证：

- 路由与控制器：默认及多层入口、路径参数与深度、构造时机、缺失入口/别名、重复内部调用和原始业务异常。
- 请求与响应：查询、表单、JSON、上传、请求头、Cookie、CLI 和内部调用；字符串返回、JSON 输出、重定向及 `end()` 后无重复正文或后续业务操作。
- Route：GET/POST/HEAD、继承/覆盖、匹配或缺失的 Request-Type、HTTP 方法的 405/Allow；类型拒绝不回退、不构造控制器，也不输出空 Allow。
- 模板：实例隔离与连续赋值、多层 include、part 的混合输出和异常缓冲；HTML、input 值、实际链接、JSON/JS/CSS、可信 HTML、条件属性、修饰器和 literal。
- 资源与服务：PHP/JSON/JSONC 路由及多文件顺序、Config 缓存、语言选择与请求重置、数据库失败/事务/锁、Redis Sentinel 发现及失败命令不重放。
- 会话与安全：HTTP/HTTPS 和可信代理下 Cookie 回传、验证码刷新/过期/重复提交、启用 CSRF 的表单与 Ajax、token 更新、异常响应和日志采集。
- 部署缓存：模板首次编译、缓存复用、变量更新、动态上下文变体，以及工作进程重启后的实际入口。

samples 当前均默认自动转义，模板实验为 `/lab/template/entry`、`/widgets/template/context`，语言实验为 `/lab/language/entry`，CSRF 实验为 `/lab/csrf/entry`。统一指南可在 `/site/main/docs?doc=CHANGELOG.md#6x-升级与迁移指南` 阅读。部署验证步骤见[部署与排错](docs/deployment.md#验证)，完整编码边界见 [Tpl 手册](docs/templates.md#上下文自动转义)。

## 版本更新记录

<a id="671--2026-11-18"></a>
<a id="670--2026-11-18"></a>
<a id="660--2026-11-18"></a>
<a id="653--2026-11-16"></a>
<a id="652--2026-11-16"></a>
<a id="651--2026-11-16"></a>
<a id="650--2026-11-15"></a>
<a id="642--2026-11-15"></a>
<a id="641--2026-11-15"></a>
<a id="640--2026-11-14"></a>
<a id="新增与不兼容变更"></a>
<a id="630--2026-11-14"></a>
<a id="新增与不兼容变更-1"></a>
<a id="622--2026-11-11"></a>
<a id="621--2026-11-10"></a>
<a id="620--2026-11-09"></a>
<a id="新增与安全修复"></a>
<a id="613--2026-11-09"></a>
<a id="612--2026-11-04"></a>
<a id="611--2026-11-03"></a>
<a id="601610--2026-11-02"></a>
<a id="600--2026-11-01"></a>

### 6.x

合并 6.0 至 6.7.1 的修改，按模块说明当前行为；重复记录合并，已被替代的中间行为以最终实现为准。升级步骤见 [6.x 升级与迁移指南](#6x-升级与迁移指南)。

#### 核心调度与请求响应

- 控制器统一使用命名空间模式及 Composer 自动加载，移除 PAGE 模式、默认 `page` 方法前缀和旧模式配置接口。新增 `Request` / `Response`：构造函数接收 Request，入口按 Request、Response 的顺序传参，完整路径通过 `$request->route` 读取。
- Request 保存请求数据快照，提供查询、表单、Cookie、上传、请求头、原始正文和 `$_REQUEST` 数组；后者保留 PHP 配置决定的合并来源、覆盖顺序与数组语义。
- Response 提供状态码、响应头、Cookie、正文、结束和重定向接口。成功通过 `end()` / `redirect()` 结束响应后，`run()` 返回 null，避免重复输出；控制器仍需显式 return 结束方法执行。
- 核心调度失败统一抛出 `SlightPHP\Exception`，提供原因码、诊断上下文和建议 HTTP 状态；路径缺失/超深、目录无效、文件/类/方法不可用、文件越界、重复内部调用和缺失别名均由应用处理。业务入口主动返回的 false 保留，应用自身异常原样传播。
- 新增 `setMaxDepth()` / `getMaxDepth()`，路径默认最多 10 段，包含区域、page、entry 和路径参数；正常请求、CLI 和内部调用均受限制，超限时在加载控制器前抛出异常。
- 请求类型前缀按规范化后的小写类型列表顺序首匹配。缺失或不匹配的 Request-Type 在方法存在性、Route 策略和控制器构造前抛出 405，不继续候选回退；显式 URL、CLI、内部调度和模板 part 均适用。类型拒绝的 `allowedMethods` 为空，应用只在 HTTP 方法列表非空时发送 Allow。
- 构造控制器前确认入口存在且为 public，缺失或非公开入口不触发构造函数。普通候选文件缺失使用分支继续查找，类或入口不可用时记录诊断，最终失败才构造异常；保留浅层回退、文件边界校验和有效入口的构造函数异常传播。
- 应用目录规范化时校验类型，减少重复 `is_dir()`；Route 元数据按实际控制器和入口缓存，包含无注解及非公开方法，继承与覆盖相互隔离。HTTP 方法与 CSRF 每次重新校验；PHP-FPM 的元数据缓存仅在当前请求内复用。

#### Route 与 CSRF

- Route 保留静态 URL 映射，并支持 `#[Route(method: 'POST')]` / `#[Route(methods: ['POST', 'GET'])]`。两者取并集，方法统一大写去重；未声明限制时不增加 HTTP 方法校验。不匹配时在构造控制器前抛出 405，由应用输出 Allow；同一入口重复 Route 注解抛出 `InvalidArgumentException`。
- 新增 Session CSRF 防护 `SlightPHP\Csrf`，提供 `token()`、`tokenInput(?string $fieldName = null)`、`validate(Request $request, ?string $fieldName = null)`、`rotate()` 和 `clear()`。`$fieldName` / `$sessionKey` 默认分别为 `_csrf` / `_slightphp_csrf`，应用可统一配置。
- Route 的 `csrf: true` 使用默认表单字段和 `X-CSRF-Token`，字符串同时指定字段和同名请求头，省略或 false 不增加检查。应用先启动 Session，调度在 HTTP 方法通过后、构造控制器前校验；无效 token 抛出 `CSRF_INVALID`（403），Session 未启动抛出 `CSRF_SESSION_NOT_STARTED`（500）。完整接入见 [CSRF 手册](docs/csrf.md)。
- `Route::setConfigFile()` 使用 Config 加载 JSON/JSONC 顶层数组，每个对象为一条规则，校验通过后按加载顺序追加；不再读取 INI。保留 PHP 规则和占位符，首条匹配优先，修复相同前缀占位符替换及多文件规则覆盖问题。
- URL 规则只匹配路径，忽略查询串；保留重复前导斜杠，避免 `//articles/42` 的首段被误当作主机名。公开 `Route::parse($path)` 支持显式匹配已规范化路径，无效配置和正则抛出明确异常。示例见[自定义 URL 路由](docs/routes.md)。

#### Tpl 模板与输出

- 模板变量表改为受保护的实例属性，`assign()` / `fetch()` 改为实例方法；不同实例数据隔离，同一实例连续渲染保留赋值。include 自动传入父实例，多层子模板共享变量和输出 Context；目录、分隔符和强制编译选项仍为静态配置。
- 所有模板默认按实际 HTML、属性、URL、JS、CSS 上下文自动转义。移除兼容编译分支及 `setContextMode()` / `getContextMode()`；模板传原始数据，完整 URL 构造和业务/签名编码仍由应用保留。迁移规则见[原始数据与上下文自动转义](#原始数据与上下文自动转义)。
- 内置 raw、escape、default、tr、tostring、version 和修饰链继续可用，先转换再按实际输出位置编码。末尾 escape 保留编码意图以避免重复 HTML/URL 组件编码，末尾 raw 标记调用方负责的可信原文；普通 PHP 函数返回值没有该意图标记。
- 模板辅助函数改为 `SlightPHP\Tpl\Func`、`SlightPHP\Tpl\Modifier` 的静态方法，文件统一位于 `plugins/Tpl/`。移除旧全局辅助函数和 `tpl_function_*` / `tpl_modifier_*` 前缀查找，保留同名普通 PHP 函数回退；模板标签与修饰器写法继续可用。
- 模板和示例统一使用默认 `{` / `}` 分隔符；Lexer 保留普通 JS/CSS 花括号，处理引号和嵌套括号，与模板语法同形的原文使用 `{literal}...{/literal}`。支持 true/false/null 字面量、常量算式和带空格的大写函数调用；恢复 Else/ElseIf、结束控制标签和 include 的大小写兼容。
- `{* ... *}` 支持单行、多行模板注释，编译时移除且不执行内部标签；不支持嵌套，未闭合会报错，literal 中按原文保留。说明、API 注释和三语言示例同步更新。
- HTML 中 `${$price}` 保留美元符号加模板变量输出，仅在 JS 模板字符串中保留原生 `${...}` 插值。控制标签的原生 JS 块判断跳过字符串和注释，修复条件字符串含 `){` 时的误判。
- JS 区分控制条件后的正则和普通调用后的除法，修复后置 `++/--` 判断；歧义斜杠位置报错。支持数组、对象、函数参数展开后的动态操作数；完整 JS 值加圆括号隔离负数。正则起始处的空插值使用 `(?:)`，避免形成行注释，已有正则内容及字符类中的空插值语义保留。
- 按 script 首个 type 属性识别 JSON/JSON-LD、importmap 等数据块，输出标准 JSON；可执行 JS 保留表达式括号。JS 行首空白包含垂直制表符，保留 HTML 风格行注释和 script escaped/double-escaped 状态。
- 敏感属性拒绝缺少分号、未知或不支持的 HTML 实体；保护跨插值的实体前缀，并编码 JS 模板字符串中的动态花括号，避免与静态 `$` 形成可执行插值。字符串转换、JSON 序列化、普通表达式和函数的输出缓冲及上下文修订号守卫阻止转换回调直接输出或改变编码位置。
- CSS 已有 URL 前缀时仅对变量做 URL 组件编码，完整及最终拼接 URL 仍检查协议；未加引号的 `url()` 转义空白，已加引号的 URL 和字符串保留正常空格。动态 URL、事件、style、srcset 和 meta content 保留组合检查。
- 模板 part 临时收集模块直接输出及字符串返回值并跟踪上下文，非字符串不作为 HTML 输出；失败片段的缓冲被丢弃，已提交输出保留，业务副作用不回滚。PHP 直接调用 `Tpl\Func::part()` 则直接输出并返回 null，不管理调用方缓冲。模块需配对关闭自己的缓冲，遇到不可移除的缓冲时停止清理并抛出异常。

#### 模板编译、缓存与文件边界

- 源码编译统一由 `Tpl\Compiler::compileSource()` 完成 Lexer 分词和代码生成，移除入口类中的 `_compile()`、`$safe_mode` 和 `setSafeMode()`。静态 PHP 标签作为模板字面量输出；模板仍必须来自可信来源，上下文编码不提供沙箱。
- 编译格式统一为 v10，使用 `.context.php` 缓存，文件头校验格式、分隔符和计划版本，旧缓存自动重建。缓存保存执行计划，变量和最终页面输出不会固化到缓存中；部署时需可写编译目录、重启常驻进程并按流程刷新 OPcache。
- 编译期生成带实际入口守卫的静态 HTML 块、属性编码和 JS 状态推进计划。原生数组/标量的纯变量及常量键查找可直接取值、转义并输出，相同只读值在块内复用；对象、ArrayAccess、缺失键及动态上下文保留检查和回退。
- script/事件属性的 JS 词法结果编译到主文件，动态新入口首次生成有界上下文变体，完整命中后不再执行 JS 词法扫描；CSS 使用增量断点。静态标签前缀、引号闭合和纯文本整块输出减少重复解析，同时保留实际 HTML 入口、URL/CSS 校验及回调守卫。
- Lexer 在完整源码偏移上识别候选，避免花括号密集时复制剩余模板的二次方开销；raw-text 结束标签增量匹配，普通比较符不再反复扫描整段正文，保留跨片段结束标签和 script 状态判断。
- 模板源文件、编译文件及每级子目录检查规范路径边界，拒绝指向目录外的符号链接。新建编译目录默认 `0700`，新建或重写编译文件为 `0600`，已有目录和命中缓存的权限不自动修改。`escape` 明确使用 UTF-8 和 `ENT_QUOTES | ENT_SUBSTITUTE`。

#### 配置、语言与 JSON

- Config 仅接受 `.json` / `.jsonc`；JSON 使用严格语法，注释仅在 JSONC 中支持，两者均不允许尾随逗号。移除旧配置解析器及序列化文件缓存，保留进程内缓存；发布配置后按流程重启常驻进程。
- Language 使用 `Config::parse()` 加载 `locale/zone.json`（优先）或 `.jsonc`，顶层必须为对象且所有词条值为字符串；旧 INI 文件需要转换内容。保留语言选择和缺失词条回退，格式、结构和读取失败抛出异常。
- Language 校验语言标签、q 值及 zone，限制请求头长度与条目数，忽略非法项和 `q=0`，拒绝读取语言目录外的文件。`setLocale()` 清除候选语言缓存，`setLanguageDir()` 清除已加载词条；`resetRequest()` 重置强制语言和候选列表，保留应用配置与词典缓存，不提供并发请求隔离。
- 移除 `SlightPHP\Json` 及旧别名，使用 PHP 原生 `json_encode()`、`json_decode()`、`json_last_error()` 和 `json_last_error_msg()`；不保留兼容类。迁移映射见 [JSON 插件移除](#json-插件移除)。

#### 数据库与 Redis

- 数据库操作统一由 `SlightPHP\Db` 提供，内部结果类及适配器分别改为 `SlightPHP\Db\Data` / `SlightPHP\Db\PDO`，文件位于 `plugins/Db/`；COUNT 继续读取第一行，旧类名不提供别名。
- `Db::query()` 移除第三个 `$retry` 参数及自动重连、重试。查询失败保留错误、触发 `E_USER_WARNING` 并返回 false；应用处理失败结果和事务恢复。PDO 适配器删除 `$connectionError`，通过 `errno()` / `error()` 读取 SQLSTATE 及原始建连错误，重新连接重置旧状态。
- `select()` 新增末尾 `$for`，`selectOne()` 新增末尾 `$hits` / `$for`；仅精确大写 `UPDATE` / `SHARE` 在主查询 LIMIT 后追加锁子句，COUNT 不加锁。事务、连接及数据库语法支持由应用负责。
- RedisArray 使用构造函数传入的主机和选项，修复参数未生效的问题。Sentinel 模式使用 RedisSentinel 按服务名发现并验证主节点，支持多 Sentinel 回退、独立认证、选库和持久连接，需要 phpredis 6.0+；断线或 READONLY 后，后续调用重新发现主节点，不重放失败命令。

#### Cookie、验证码、错误与日志

- Response 的 `cookie()` / `rawcookie()` 默认启用 Secure、HttpOnly 和 SameSite=Lax，提供 `setCookieDefaults()` / `getCookieDefaults()`。单次调用可覆盖；SameSite=None 必须启用 Secure。会话在启动前配置，HTTP 开发环境明确关闭 Secure，HTTPS 和可信反向代理配置见[部署说明](docs/deployment.md#cookie与会话安全)。
- Captcha 基于 Gregwar Captcha，移除旧 SimpleCaptcha 专有接口。每个活动会话前缀只保留最新验证码，默认 TTL 为 300 秒，刷新替换旧记录，成功校验后立即移除；过期及旧格式记录不可用。
- Error 默认关闭 `Error::$LOGARGS`，不采集或输出调用栈参数；显式启用时对参数数组中的密码、密钥、令牌字段脱敏，对象替换为类名。关闭回调遵循 `error_reporting()`，不补记被屏蔽的错误；HTML 错误内容及网页中的非纯文本、非 JSON 错误输出进行转义。
- Log 网页控制台内容进行 HTML 转义，JSON 模式改为 JSON Lines，每条记录包含 `time`、`level` 和参数数组 `message`，控制字符使用 JSON 编码；日志采集程序需同步适配。

#### 依赖、文档与示例

- Composer 强制依赖仅保留 PHP >=8.0，数据库、Redis、Http 和 Captcha 的扩展/包改为 `suggest`，由应用按需安装。插件统一使用 `SlightPHP\` 命名空间与 PSR-4，删除旧全局类名、兼容别名及不再提供的历史插件。框架仓库忽略 `composer.lock`，应用仍可管理自己的锁文件。
- 官网统一为 [slightphp.com](https://slightphp.com/)。官网与工作台使用 Language 支持简体、繁体和英文，语言选择保存到 Cookie；主题默认跟随系统，并保留手动深浅色偏好。旧功能页跳转至首页功能区域，旧迁移文档地址统一跳转至 6.x 指南，旧章节锚点继续可用。
- 文档浏览器直接读取仓库 docs、ChangeLog 和生成的 API 参考；升级说明与迁移指南合并为 6.x。补齐 Tpl 参数、返回值及关键数据结构注释，公开示例与手册统一使用默认 `{` / `}` 分隔符。
- samples 使用 Request/Response、PHP/JSON 路由、Route 方法与 CSRF 策略，所有模板默认自动转义。模板实验覆盖 include/part、变量隔离、修饰器、HTML/URL/JS/CSS、注释、literal、缓存与错误处理；语言实验展示词条与回退，CSRF 实验展示三种表单写法、Ajax、字段/请求头定制、403 和 token 更新。
- samples 错误页渲染时临时禁用 Request-Type 前缀并在结束后恢复，避免错误处理中再次触发入口不存在。三语言页面、首次编译、缓存复用与交互示例随文档同步维护，部署检查见[部署与验证](#部署与验证)。
