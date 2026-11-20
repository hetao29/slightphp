# SlightPHP 6.x

SlightPHP 是一个轻量 PHP 框架，按照请求路径加载命名空间控制器并调用公开方法。应用负责响应输出、状态码、权限和输入校验。

[官网](https://slightphp.com/) · [源码](https://github.com/hetao29/slightphp)

6.x 提供 Session CSRF 防护，通过 Route 的 `csrf` 参数启用，支持自定义字段/请求头、`tokenInput()`、`tokenMeta()` 及公共配置。samples 提供三种表单写法与 Ajax、token 更新实验；接入见 [CSRF 使用手册](docs/csrf.md)。

Tpl 默认启用上下文自动转义，已移除 `setContextMode()`、`getContextMode()` 及兼容编译分支。模板应传入原始数据，可信 HTML 用末尾 `|raw`；升级前需迁移手工编码、JSON 数据块和动态属性名，见 [6.x 升级与迁移指南](CHANGELOG.md#6x-升级与迁移指南)。

已有应用升级统一参阅 [6.x 升级与迁移指南](CHANGELOG.md#6x-升级与迁移指南)，按起始版本检查控制器签名、模板实例、调度异常、JSON/语言资源、插件类名、Cookie 及默认自动转义。6.0 至 6.7 的修改统一汇总在 [6.x 更新记录](CHANGELOG.md#版本更新记录)。

## 安装

核心需要 PHP 8.0+。插件依赖通过 Composer 的 `suggest` 声明，默认不安装；使用 Db、Redis、Http、Session、Csrf 或 Captcha 时，再按需安装对应扩展或 Composer 包，见[插件依赖](docs/plugins.md#按需安装依赖)。

当前发布版本为 6.7.4，新增 `SlightPHP\Session`，统一配置会话名称、Cookie 和缓存策略；Csrf 默认通过 `Session::start()` 按需启动会话，并保留 `Csrf::$sessionHandler` 自定义回调。用法见 [Session 手册](docs/session.md)，升级见 [6.7.4 修改说明](CHANGELOG.md#674--2026-11-20)。安装命令使用对应的包版本：

```bash
composer require hetao29/slightphp:^6.7.4
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
    if ($error->getCode() === \SlightPHP\Exception::METHOD_NOT_ALLOWED && !empty($error->getContext()['allowedMethods'])) {
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

写操作可在 Route 注解中设置 `csrf: true`，例如 `#[\SlightPHP\Route(method: 'POST', csrf: true)]`：通过按需启动会话的 `Csrf::token()` 生成表单 `_csrf` 字段或 `X-CSRF-Token` 请求头的值，调度器在构造控制器前校验，失败抛出异常。应用可配置 `Csrf::$sessionHandler` 自定义启动，默认按需调用 `Session::start()`。详见 [Csrf 插件](docs/plugins.md#csrf)。

核心调度失败统一抛出 `SlightPHP\Exception`，不再返回 `false` 或直接生成错误响应。已有入口及内部调用需按 [核心异常与处理](docs/exceptions.md)调整。

## 文档

- [6.x 更新记录](CHANGELOG.md#6x)
- [6.x 升级与迁移指南](CHANGELOG.md#6x-升级与迁移指南)
- [快速开始](docs/getting-started.md)
- [核心调度和 API](docs/framework.md)
- [自定义 URL 路由与 PHP / JSON 示例](docs/routes.md)
- [Request 与 Response](docs/http.md)
- [插件使用](docs/plugins.md)
- [Session 使用手册与示例](docs/session.md)
- [Tpl include / part 使用手册](docs/templates.md)
- [示例与部署](docs/README.md)

本项目采用 [MIT 许可证](LICENSE.txt)。
