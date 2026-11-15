# 升级到 SlightPHP 6.5.0

6.5.0 新增 Route 方法注解，核心失败统一抛出结构化异常，完善 PHP / INI 自定义路由，并优化候选查找、应用目录校验及请求内元数据缓存。官网为 [https://slightphp.com/](https://slightphp.com/)。完整记录见 [Changelog](../CHANGELOG.md)。

**从 6.4.x 升级也需要修改调度失败处理。** 旧的 `^6.4`、`^6.3`、`^6.2` 约束均允许解析到 6.5.0，不能据此认为原有的 `false` 失败判断仍然适用。从 6.3.x 或更早版本升级时，还需完成 [6.4 模板迁移](migration-6.4.md)；旧控制器签名的迁移见 [6.3 迁移指南](migration-6.3.md)。

## 安装

PHP 和依赖要求保持为 PHP 8.0+、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha。在应用项目中更新依赖：

```bash
composer require hetao29/slightphp:^6.5
```

仓库自身使用 `composer install` 安装锁定依赖。此版本不新增 APCu 等扩展要求。

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
    if ($error->getCode() === \SlightPHP\Exception::METHOD_NOT_ALLOWED) {
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

静态 `Route::set()` 和 `setConfigFile()` 继续可用，与方法注解共用 `Route` 类。规则只匹配路径，忽略查询串；重复前导斜杠不会被当作主机名。占位符按完整名称替换，多个 INI 文件中的同名节按加载顺序追加，不再相互覆盖。

新增公开的 `Route::parse(?string $path = null)`。入口若已剥离 `index.php` 或站点前缀，应在注册规则后调用 `Route::parse($path)`，匹配规范化后的实际路径。INI 不可读、规则或正则无效时抛出明确异常；检查已有配置，尤其是空 pattern 和占位符约束类型。完整示例见 [自定义 URL 路由](routes.md)。

## 调度优化与缓存边界

普通候选文件缺失使用分支继续查找，类或入口不可用时只记录诊断，最终失败才构造异常。普通文件缺失不再产生中间异常链，文件越界等校验仍保留原因。候选回退不会吞掉业务异常。

应用目录在设置或首次使用时按目录路径规范化并缓存，调度不再重复执行 `is_dir()`；候选仍需通过普通文件与目录边界检查。方法元数据先按实际控制器和入口查缓存，未命中才反射，无注解和非公开方法也缓存；HTTP 方法是否允许仍逐次判断，无效注解不会被缓存为无限制。

**普通 PHP-FPM 的元数据缓存只在当前请求内复用，不会跨请求保留。** 本版本没有引入持久化注解缓存。常驻进程应按发布流程重启以加载新代码，OPcache 刷新也由发布流程管理。

## 升级验证

- 检查正常入口、缺失入口、路径超限、重复内部调用和缺失别名的处理。
- 验证 GET/POST/HEAD、Request-Type 前缀、继承与覆盖，以及 405 的 Allow 响应。
- 验证包含查询串的 PHP / INI 路由、重复斜杠和多文件规则顺序。
- 检查可选模板模块的异常处理，以及原始业务异常的传播。

仓库示例可执行 `php samples/tests/routing.php` 检查真实入口、自动加载、模板与 URI 解析；HTTP 验证方式见 [部署文档](deployment.md#验证)。
