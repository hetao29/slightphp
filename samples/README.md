# SlightPHP 官网与交互示例

示例对应 SlightPHP 6.4.0，模板数据使用 Tpl 实例隔离，控制器入口使用 `Request $request, Response $response`，路径从 `$request->route` 读取。升级已有应用请参阅 [6.4 迁移指南](../docs/migration-6.4.md)；旧控制器签名迁移见 [6.3 迁移指南](../docs/migration-6.3.md)。

部署示例站点后，访问站点根路径 `/`，即可浏览框架介绍并体验交互示例。站点地址由部署环境决定。

- `/site/main/features`：功能特性。
- `/site/main/plugins`：插件搜索、分类与运行条件。
- `/site/main/guide`：安装、入口、控制器与部署说明。
- `/site/main/examples`：任务、路由、配置与 JSON 示例。
- `/lab/template/entry`：跨目录 include、跨模块 part、变量隔离和嵌套调用。
- `/widgets/template/card/direct`：可独立访问的模块卡片片段。
- `/workspace/main/entry`：可运行任务工作台。

公开 URL 不包含 index.php，静态资源为 `/assets/...`。部署时以 `samples/www` 为站点根目录，将 URI 转发到 `index.php` 并保留原始 REQUEST_URI。

入口通过仓库根目录 `vendor/autoload.php` 自动加载框架与插件；缺少 vendor 时先在仓库根目录执行 `composer install`。框架使用命名空间控制器；官网、任务和路由功能分别位于 Site、Workspace、Lab 命名空间模块，模板使用原生转义与根路径链接。

仅保留官网、当前可运行示例、模板、静态资源、配置与验证脚本。旧后台、业务控制器、外部服务配置、图片和模板缓存已移除。

```bash
php samples/tests/routing.php
php samples/tests/tpl-composition.php
read -r -p "站点地址（包含 http:// 或 https://）: " SITE_URL
python3 samples/tests/website.py "$SITE_URL"
python3 samples/tests/smoke.py "$SITE_URL"
```

[Tpl include / part 手册](../docs/templates.md) · [官网维护说明](../docs/website.md) · [部署与排错](../docs/deployment.md) · [框架文档](../docs/README.md)
