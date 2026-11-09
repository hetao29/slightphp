# SlightPHP 6.2.0

SlightPHP 是一个轻量 PHP 框架，按照请求路径加载命名空间控制器并调用公开方法。应用负责响应输出、状态码、权限和输入校验。

6.2.0 增加请求路径深度限制，并加强配置、模板、多语言、验证码和日志处理。升级前请阅读 [6.2 升级说明](docs/migration-6.2.md)，特别留意配置格式、验证码会话及 JSON 日志格式的变化。完整记录见 [Changelog](CHANGELOG.md)。

## 安装

需要 PHP 8.0+、PDO 和 Redis 扩展；Composer 还会安装 Guzzle 和 Gregwar Captcha。使用数据库、Redis 服务或验证码时，还需准备相应驱动、服务和扩展。

```bash
composer require hetao29/slightphp:^6.2
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
$result = SlightPHP::run();
if ($result === false) {
    http_response_code(404);
} elseif (is_string($result)) {
    echo $result;
}
```

控制器 `app/Home/MainController.php`：

```php
<?php
namespace Home;

class MainController
{
    public function Entry($inPath)
    {
        return 'Hello, SlightPHP!';
    }
}
```

`/home/main/entry` 映射到 `Home\MainController::Entry()`。可在 Web 服务器中设置 `PATH_INFO`，或使用 `SlightPHP::setPathInfo()` 明确指定路径。插件通过 Composer 加载，例如 `\SlightPHP\Config`、`\SlightPHP\Db`、`\SlightPHP\Tpl`。

请求路径默认最多 10 段，包含区域、page、entry 和路径参数。可通过 `SlightPHP::setMaxDepth(15)` 调整，通过 `SlightPHP::getMaxDepth()` 读取；必须大于等于 1。超过上限时 `run()` 返回 `false`，未超限的参数保持原样。

## 文档

- [更新记录](CHANGELOG.md)
- [6.2 升级说明](docs/migration-6.2.md)
- [6.0 迁移与不兼容事项](docs/migration-6.0.md)
- [快速开始](docs/getting-started.md)
- [核心调度和 API](docs/framework.md)
- [插件使用](docs/plugins.md)
- [示例与部署](docs/README.md)

本项目采用 [MIT 许可证](LICENSE.txt)。
