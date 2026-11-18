# 升级到 SlightPHP 6.5.3

升级到当前版本还需完成 [6.7 迁移](migration-6.7.md)，Tpl 现在统一自动转义并移除模式接口。

本文记录 6.5.x 的历史变更；当前版本为 6.7.0，升级时还需阅读 [6.6 迁移指南](migration-6.6.md)，处理类名、模板辅助函数及 Cookie 默认策略变化。

6.5.3 将 Language 语言文件由 INI 改为 JSON/JSONC；从 6.5.2 升级也需要转换现有语言文件，详见 [Language 迁移](#language-语言文件改为-jsonjsonc653)。官网与 samples 同步支持三种语言、默认自动的主题和完整文档阅读。

6.5.2 新增请求类型前缀校验。从 6.5.1 升级也需检查带类型前缀的入口、内部调用及 405 响应处理，具体见[请求类型前缀校验](#请求类型前缀校验652)。

6.5.1 移除 `SlightPHP\Json` 插件，Route 文件配置改为 JSON/JSONC 数组，并将插件依赖改为 Composer `suggest`，由应用按需安装。从 6.5.0 升级也需要替换 JSON 插件调用、转换 INI 路由配置并显式声明所用插件的依赖。

6.5.0 新增 Route 方法注解，核心失败统一抛出结构化异常，完善 PHP / INI 自定义路由，并优化候选查找、应用目录校验及请求内元数据缓存。官网为 [https://slightphp.com/](https://slightphp.com/)。完整记录见 [Changelog](../CHANGELOG.md)。

**从 6.4.x 升级也需要修改调度失败处理。** 旧的 `^6.4`、`^6.3`、`^6.2` 约束均允许解析到 6.5.0，不能据此认为原有的 `false` 失败判断仍然适用。从 6.3.x 或更早版本升级时，还需完成 [6.4 模板迁移](migration-6.4.md)；旧控制器签名的迁移见 [6.3 迁移指南](migration-6.3.md)。

## 安装

当前安装仅强制要求 PHP 8.0+；PDO、Redis 扩展、Guzzle 和 Gregwar Captcha 已改为按需安装。使用相关插件的应用应先按[插件指南](plugins.md#按需安装依赖)将所需依赖加入自己的 `require`，再更新框架：

```bash
composer require hetao29/slightphp:^6.5.3
```

仓库自身使用 `composer install` 安装依赖；6.7 起不再跟踪框架仓库的 `composer.lock`，应用项目仍可管理自己的锁文件。此版本不新增 APCu 等扩展要求。

## Language 语言文件改为 JSON/JSONC（6.5.3）

6.5.3 的 `Language::tr()` 与 Route 一样使用 `Config::parse()`，不再读取 `locale/zone.ini`。将原 INI 词条转换成 JSON 对象，保存到相同语言目录中的 `locale/zone.json` 或 `locale/zone.jsonc`；不能仅修改文件扩展名。例如，`languages/en/main.json`：

```json
{
  "welcome": "Welcome to SlightPHP",
  "任务已添加": "Task added"
}
```

顶层必须为对象，所有词条值必须为字符串；原有数字、布尔值等内容如需作为翻译，应显式写为字符串。空对象和空字符串有效。`.json` 使用严格 JSON，`.jsonc` 允许行注释和块注释；都不支持尾随逗号。同名文件优先使用 `.json`，不与 `.jsonc` 合并。

`setLanguageDir()`、`setLocale()`、`tr($source, $zone)` 的调用方式保持不变。语言列表、主语言回退、缺失词条返回原文及目录边界校验保持不变；首次翻译前应设置语言目录和 locale。6.6.0 进一步修复 setter 的缓存失效行为并新增 `resetRequest()`，见 [6.6 迁移指南](migration-6.6.md#language-语言切换与请求重置)。JSON 格式错误抛出 `JsonException`，非对象顶层或非字符串词条抛出 `InvalidArgumentException`，无法读取已有文件时抛出 `RuntimeException`，应修复资源文件，不能依赖解析失败后返回原文。

Language 的已加载词条仍保留在进程内；首次加载文件也受 `Config::$CACHE` 影响。发布时同步部署所有语言文件，常驻进程按发布流程重启。完整格式见 [Language 插件](plugins.md#language)，可运行示例见 `/lab/language/entry`。

samples 已将简体、繁体、英文资源转换为 JSON。官网的功能特性合入首页，旧 `/site/main/features` 跳转至首页功能区域；主题默认自动跟随系统，文档和 ChangeLog 直接读取仓库内容。应用自行使用框架时，只需迁移所使用的 Language 资源，无需采用 samples 的界面与导航。

## 请求类型前缀校验（6.5.2）

候选方法名以 `setAllowRequestTypes()` 配置的类型开头时，当前 `Request-Type` 必须匹配。缺失或不匹配会立即抛出 `SlightPHP\Exception::METHOD_NOT_ALLOWED`（405），先于方法存在性、Route 注解与控制器构造，也不会继续尝试浅层候选。普通 HTTP 请求、显式 URL、`run('/...')` 与模板 `part` 均执行此校验。

例如，原来不带请求头直接访问 `/user/order/ajaxSave` 的调用会被拒绝；应使用 `/user/order/save` 并发送 `Request-Type: ajax` 来调用 `ajaxSave`。内部调用和 CLI 不会自动补充请求类型，模板 `part` 沿用当前请求头，升级时也需核对。

类型列表统一转为小写并过滤空字符串；请求头先去除首尾空白再转为小写。前缀按配置顺序首匹配，不要求单词边界，因此也需检查名称恰好以 `page`、`json` 等已配置类型开头的普通入口。自定义类型重叠时将较长类型排在前面；从列表移除某类型也会移除对应的前缀校验。该校验不代替 HTTP 方法、登录、对象权限或 CSRF 校验。

请求类型不匹配时，异常上下文包含 `requestType`、`requiredRequestType`，而 `allowedMethods` 为空数组；入口应仅在 `allowedMethods` 非空时输出 HTTP `Allow` 头，不要把 `ajax`、`part` 等类型写入该头。下面的入口示例已包含判断；详细规则见[方法前缀](framework.md#方法前缀与-http-方法)与[异常说明](exceptions.md)。

## JSON 插件移除

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

## 路由配置改为 JSON/JSONC

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

INI 的常量或环境变量插值不会被 JSON 自动执行；这类动态规则可以继续使用 PHP 的 `Route::set()`。JSON 语法错误现在抛出 `JsonException`，不再统一为 INI 解析的 `RuntimeException`；`.ini` 扩展名会被拒绝。Config 默认缓存且不检查文件修改时间，常驻进程应只注册一次并在配置发布后重启。完整规则与缓存说明见[自定义路由文档](routes.md#jsonjsonc-规则)。

## 核心失败改为异常

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

`getCode()` 是原因码，`getStatusCode()` 是建议的 HTTP 状态码；路径、控制器和允许方法等诊断信息通过 `getContext()` 读取。不要直接向客户端展示包含文件路径的异常消息。完整原因码见 [核心异常与迁移](exceptions.md)。

需要检查的调用点包括：

- 主入口、CLI 任务以及 `run('/...')` 内部调用。
- 模板 `part`：未匹配入口和重复内部调用现在抛出异常，可选模块需由调用方按原因码处理；空路径仍不调用入口。
- `getZoneAlias()`、`getPageAlias()`：别名未设置时抛出 `ALIAS_NOT_FOUND`，有效别名仍返回原字符串。
- 超出路径深度、应用目录无效及控制器文件/类/方法不可用等情况。

业务入口主动返回的 `false` 仍是正常业务返回值；响应通过 `end()` / `redirect()` 结束后，调度返回 `null`。这两种结果均不能作为调度失败标志。控制器文件、构造函数、入口及注解构造函数自身的异常原样传播。

## Route 方法注解

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

限制在构造控制器之前检查，不匹配时抛出 `METHOD_NOT_ALLOWED`，由入口返回 405 和 Allow。继承方法使用实际声明上的注解，子类覆盖按覆盖方法处理；Request-Type 前缀使用实际调用方法上的注解。CSRF、权限和业务校验仍由应用负责。

## 自定义 URL 路由

静态 `Route::set()` 和 `setConfigFile()` 继续可用，与方法注解共用 `Route` 类。规则只匹配路径，忽略查询串；重复前导斜杠不会被当作主机名。占位符按完整名称替换；6.5.1 的 JSON/JSONC 数组按加载顺序追加，不覆盖先前规则。

新增公开的 `Route::parse(?string $path = null)`。入口若已剥离 `index.php` 或站点前缀，应在注册规则后调用 `Route::parse($path)`，匹配规范化后的实际路径。配置不可读、JSON 解析失败、规则或正则无效时抛出明确异常；检查已有配置，尤其是空 pattern 和占位符约束类型。完整示例见 [自定义 URL 路由](routes.md)。

## 调度优化与缓存边界

普通候选文件缺失使用分支继续查找，类或入口不可用时只记录诊断，最终失败才构造异常。普通文件缺失不再产生中间异常链，文件越界等校验仍保留原因。候选回退不会吞掉业务异常。

应用目录在设置或首次使用时按目录路径规范化并缓存，调度不再重复执行 `is_dir()`；候选仍需通过普通文件与目录边界检查。方法元数据先按实际控制器和入口查缓存，未命中才反射，无注解和非公开方法也缓存；HTTP 方法是否允许仍逐次判断，无效注解不会被缓存为无限制。

**普通 PHP-FPM 的元数据缓存只在当前请求内复用，不会跨请求保留。** 本版本没有引入持久化注解缓存。常驻进程应按发布流程重启以加载新代码，OPcache 刷新也由发布流程管理。

## 升级验证

- 检查正常入口、缺失入口、路径超限、重复内部调用和缺失别名的处理。
- 验证 GET/POST/HEAD、继承与覆盖，以及 Route HTTP 方法不匹配时 405 的 Allow 响应。
- 验证匹配、缺失和不匹配的 Request-Type，覆盖显式 URL、内部调用、CLI 与模板 part；确认类型拒绝时不构造控制器、不回退且不输出空 Allow 头。
- 验证包含查询串的 PHP / JSON/JSONC 路由、重复斜杠和多文件规则顺序。
- 检查可选模板模块的异常处理，以及原始业务异常的传播。

部署仓库示例后，通过浏览器验证真实入口、自动加载、模板与 URI 解析；具体步骤见[部署文档](deployment.md#验证)。

## 内部类与模板辅助函数迁移

此项变更归入 6.6.0，详见 [6.6 类名与辅助函数迁移](migration-6.6.md#内部类与模板辅助函数迁移)。

## Tpl 自动转义迁移

上下文自动转义从 6.6.0 开始提供，默认关闭；启用后的手工编码替换规则见 [6.6 自动转义迁移说明](migration-6.6.md#tpl-自动转义迁移启用时不兼容)。
