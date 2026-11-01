# SlightPHP 官网与交互示例

部署示例站点后，访问站点根路径 `/`，即可浏览框架介绍并体验交互示例。站点地址由部署环境决定。

- `/site/main/features`：功能特性。
- `/site/main/plugins`：插件搜索、分类与运行条件。
- `/site/main/guide`：安装、入口、控制器与部署说明。
- `/site/main/examples`：任务、路由、配置与 JSON 示例。
- `/workspace/main/entry`：可运行任务工作台。

公开 URL 不包含 index.php，静态资源为 `/assets/...`。部署时以 `samples/www` 为站点根目录，将 URI 转发到 `index.php` 并保留原始 REQUEST_URI。

入口通过仓库根目录 `vendor/autoload.php` 自动加载框架与插件；缺少 vendor 时先在仓库根目录执行 `composer install`。入口通过 `SlightPHP::setModes(['NAMESPACE'])` 仅启用命名空间模式；官网、任务和路由功能分别位于 Site、Workspace、Lab 命名空间模块，模板使用原生转义与根路径链接。

仅保留官网、当前可运行示例、模板、静态资源、配置与验证脚本。旧后台、业务控制器、外部服务配置、图片和模板缓存已移除。

```bash
php samples/tests/routing.php
read -r -p "站点地址（包含 http:// 或 https://）: " SITE_URL
python3 samples/tests/website.py "$SITE_URL"
python3 samples/tests/smoke.py "$SITE_URL"
```

[官网维护说明](../docs/website.md) · [部署与排错](../docs/deployment.md) · [框架文档](../docs/README.md)
