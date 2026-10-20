# 快速开始

## 运行仓库中的完整示例

要求 PHP 8.0+，启用 session。入口通过仓库的 vendor/autoload.php 加载框架及 STpl、SConfig、SJson 插件；缺少 vendor 时先执行 composer install，无需数据库或 Redis；模板编译目录 `samples/app/templates_c` 必须可写。

部署示例站点后，访问其根路径 `/`。首页展示框架介绍；从导航进入功能、插件和使用指南，从页脚进入任务工作台。以 `samples/www` 为网站根目录并转发原始 URI，具体配置见 [部署说明](deployment.md)。

```text
samples/
  www/index.php              官网与交互示例入口（网站根目录为 www）
  www/assets/site.css        框架官网响应式样式
  www/assets/site.js         插件搜索、代码复制和示例切换
  www/assets/demo.css        任务工作台样式
  www/assets/demo.js         任务搜索、筛选、删除确认
  app/Site/                  框架官网 NAMESPACE 控制器
  app/data/site/features.php      功能介绍数据
  app/data/site/plugins.php       插件介绍数据
  app/data/site/code.php          使用指南代码
  app/Workspace/             任务工作台 NAMESPACE 控制器
  app/Lab/                   NAMESPACE 路由实验
  app/templates/             STpl 页面模板与公共布局
  config/example.conf        SConfig 可运行配置
  tests/website.py           官网 HTTP 验证
  tests/smoke.py             工作台 HTTP 验证
```

`app` 和配置不在公开目录内。模板编译输出位于 `app/templates_c`，首次渲染自动生成，勿提交版本控制。新示例沿用现有 app、config、www 目录与 STpl 模板结构；无关的旧业务示例已清理。

## 在新项目中使用 Composer

```bash
composer require hetao29/slightphp
```

包声明 PHP >=8.0、PDO 与 Guzzle；实际可用版本以 Composer 解析结果为准。核心本身不依赖 Guzzle。不要混淆完整安装要求与独立演示要求。

创建 `public/index.php`：

```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
SlightPHP::setModes(['NAMESPACE']);
SlightPHP::setDefaultZone('home');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
SlightPHP::setPathInfo(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$result = SlightPHP::run();
if ($result === false) {
    http_response_code(404);
    echo '页面不存在';
} elseif (is_string($result)) {
    echo $result;
}
```

创建 `app/Home/MainController.php`：

```php
<?php
namespace Home;

class MainController {
    public function Entry($inPath) {
        return 'Hello, SlightPHP!';
    }
}
```

将网站根目录设为 `public`，通过重写传入路径，或访问 `/home/main/entry`。核心在 REQUEST_URI 回退分支中不会主动剥离 `index.php` 或站点子目录，部署时须正确设置 PATH_INFO 或调用 `setPathInfo()`。
