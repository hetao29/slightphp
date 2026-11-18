# 快速开始

当前版本为 SlightPHP 6.6.0，官网：[https://slightphp.com/](https://slightphp.com/)。

## 运行仓库中的完整示例

要求 PHP 8.0+，启用 session。入口通过仓库的 vendor/autoload.php 加载框架及 Tpl、Config、Route、Language 插件，JSON 输出使用原生函数；缺少 vendor 时先执行 composer install，无需数据库或 Redis；PHP 的系统临时目录必须可写。

部署示例站点后，访问其根路径 `/`。首页包含完整功能特性，导航顺序为「首页 → 入门 → 示例 → 插件 → 文档」。ChangeLog 已合并在文档目录中。从示例索引或工作台侧栏可进入多语言实验；页面支持简体、繁体和英文，主题默认自动跟随系统。以 `samples/www` 为网站根目录并转发原始 URI，具体配置见 [部署说明](deployment.md)。

```text
samples/
  www/index.php              官网与交互示例入口（网站根目录为 www）
  www/assets/site.css        框架官网响应式样式
  www/assets/site.js         插件搜索、代码复制和示例切换
  www/assets/theme.js        自动、深色和浅色主题偏好
  www/assets/demo.css        任务工作台样式
  www/assets/demo.js         任务搜索、筛选、删除确认
  app/Site/                  框架官网 NAMESPACE 控制器
  app/data/site/features.php      功能介绍数据
  app/data/site/plugins.php       插件介绍数据
  app/data/site/code.php          入门指南代码
  app/Workspace/             任务工作台 NAMESPACE 控制器
  app/Lab/                   路由、模板和多语言实验
  app/I18n.php               Language 语言选择与文本转义
  app/languages/*/main.json  简体、繁体和英文界面词条
  app/languages/*/demo.json  多语言实验的独立词条
  app/templates/             Tpl 页面模板与公共布局
  config/example.json        Config 可运行配置
```

`app` 和配置不在公开目录内。模板编译输出位于 PHP 的系统临时目录，首次渲染自动生成。新示例沿用现有 app、config、www 目录与 Tpl 模板结构；无关的旧业务示例已清理。

## 在新项目中使用 Composer

```bash
composer require hetao29/slightphp:^6.6.0
```

包仅强制要求 PHP >=8.0；PDO、Redis 扩展、Guzzle 与 Gregwar Captcha 等插件依赖通过 `suggest` 提示，默认不安装。使用对应插件时，在应用项目中[按需安装依赖](plugins.md#按需安装依赖)；额外依赖可能提高应用的最低 PHP 版本。

已有项目升级请先阅读 [6.6 迁移指南](migration-6.6.md)，检查类名、模板辅助函数、Cookie 默认值与可选自动转义；从 6.4.x 或更早版本升级，还需按 [6.5 迁移指南](migration-6.5.md)调整核心异常处理。从 6.3.x 或更早版本升级时，还需按 [6.4 迁移指南](migration-6.4.md)修改模板静态调用，从 6.2.x 或更早版本升级时还需按 [6.3 迁移指南](migration-6.3.md)修改控制器构造函数与入口参数。

创建 `public/index.php`：

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

创建 `app/Home/MainController.php`：

```php
<?php
namespace Home;

class MainController {
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Entry(\SlightPHP\Request $request, \SlightPHP\Response $response) {
        return 'Hello, SlightPHP!';
    }
}
```

将网站根目录设为 `public`，通过重写传入路径，或访问 `/home/main/entry`。核心在 REQUEST_URI 回退分支中不会主动剥离 `index.php` 或站点子目录，部署时须正确设置 PATH_INFO 或调用 `setPathInfo()`。
