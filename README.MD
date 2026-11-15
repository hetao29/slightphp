# SlightPHP 6.5.0

SlightPHP 是一个轻量 PHP 框架，按照请求路径加载命名空间控制器并调用公开方法。应用负责响应输出、状态码、权限和输入校验。

[官网](https://slightphp.com/) · [源码](https://github.com/hetao29/slightphp)

6.5.0 新增 Route 方法注解和结构化调度异常，完善 PHP / INI 自定义路由，并优化候选查找、目录校验和请求内注解缓存。核心失败不再返回 `false`，已有入口与内部调用需要按 [6.5 迁移指南](docs/migration-6.5.md)调整。

6.4.0 将 Tpl 模板变量及 `assign()`、`fetch()` 改为实例级，升级前请阅读 [6.4 迁移指南](docs/migration-6.4.md)。

6.3.0 引入 `SlightPHP\Request` 和 `SlightPHP\Response`，替换控制器构造函数和入口方法原有的 `$inPath` 数组传参。这是不兼容变更，升级前请阅读 [6.3 迁移指南](docs/migration-6.3.md)。从较早版本升级还需留意 6.2.1 的数据库自动重试移除及 [6.2 升级说明](docs/migration-6.2.md)中的配置、验证码和日志变化。完整记录见 [Changelog](CHANGELOG.md)。

## 安装

需要 PHP 8.0+、PDO 和 Redis 扩展；Composer 还会安装 Guzzle 和 Gregwar Captcha。使用数据库、Redis 服务或验证码时，还需准备相应驱动、服务和扩展。

```bash
composer require hetao29/slightphp:^6.5
```

## 创建应用

入口 `public/index.php`：

```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
SlightPHP::setDefaultZone('home');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
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
    echo '请求失败';
}
```

控制器 `app/Home/MainController.php`：

```php
<?php
namespace Home;

class MainController
{
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Entry(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        return 'Hello, SlightPHP!';
    }
}
```

`/home/main/entry` 映射到 `Home\MainController::Entry()`。可在 Web 服务器中设置 `PATH_INFO`，或使用 `SlightPHP::setPathInfo()` 明确指定路径。插件通过 Composer 加载，例如 `\SlightPHP\Config`、`\SlightPHP\Db`、`\SlightPHP\Tpl`。

请求路径默认最多 10 段，包含区域、page、entry 和路径参数。可通过 `SlightPHP::setMaxDepth(15)` 调整，通过 `SlightPHP::getMaxDepth()` 读取；必须大于等于 1。超过上限时 `run()` 抛出 `SlightPHP\Exception`，未超限的参数保持原样。

控制器可使用 `Entry(\SlightPHP\Request $request, \SlightPHP\Response $response)`，通过 `$request->route` 读取完整路由分段，通过 `get/post/request/cookie/files/header/server` 读取请求数据，使用 Response 设置响应并输出。入口按 Request、Response 的顺序传入两个位置参数，参数名不限；原 `$inPath` 签名需要迁移，详见 [Request 与 Response](docs/http.md)。

在控制器方法上添加 `#[\SlightPHP\Route(method: 'POST')]` 或 `#[\SlightPHP\Route(methods: ['POST', 'GET'])]` 可限制 HTTP 方法；不匹配时抛出异常，由入口返回 405 和 Allow。详见 [Route 方法注解](docs/framework.md#route-方法注解)。

从 6.5.0 起，核心调度失败统一抛出 `SlightPHP\Exception`，不再返回 `false` 或直接生成错误响应。已有入口及内部调用需按 [核心异常与迁移](docs/exceptions.md)调整。

## 文档

- [更新记录](CHANGELOG.md)
- [6.5 升级与迁移说明](docs/migration-6.5.md)
- [6.4 升级与迁移说明](docs/migration-6.4.md)
- [6.3 升级与迁移说明](docs/migration-6.3.md)
- [6.2 升级说明](docs/migration-6.2.md)
- [6.0 迁移与不兼容事项](docs/migration-6.0.md)
- [快速开始](docs/getting-started.md)
- [核心调度和 API](docs/framework.md)
- [自定义 URL 路由与 PHP / INI 示例](docs/routes.md)
- [Request 与 Response](docs/http.md)
- [插件使用](docs/plugins.md)
- [Tpl include / part 使用手册](docs/templates.md)
- [示例与部署](docs/README.md)

本项目采用 [MIT 许可证](LICENSE.txt)。
