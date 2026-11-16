# SlightPHP 官网与交互示例

示例对应 SlightPHP 6.5.3，包含请求类型前缀校验、结构化调度异常、Route 方法注解与 PHP / JSON 路由。模板数据使用 Tpl 实例隔离，控制器入口使用 `Request $request, Response $response`，路径从 `$request->route` 读取。升级已有应用请先参阅 [6.5 迁移指南](../docs/migration-6.5.md)；模板实例迁移见 [6.4 迁移指南](../docs/migration-6.4.md)，旧控制器签名迁移见 [6.3 迁移指南](../docs/migration-6.3.md)。

正式官网为 [https://slightphp.com/](https://slightphp.com/)。自行部署示例站点后，访问部署地址的根路径 `/`，即可浏览框架介绍并体验交互示例。

- `/#features`：首页中的功能特性、调度流程与应用职责；旧 `/site/main/features` 跳转到该区域。
- `/site/main/plugins`：插件搜索、分类与运行条件。
- `/site/main/plugins#route-custom`、`#route-methods`：Route 自定义 URL 映射与方法注解，可复制代码并验证真实响应。
- `/site/main/guide`：入门，包含安装、入口、控制器与部署说明；导航位于首页之后。
- `/site/main/examples`：任务、路由、配置与 JSON 示例。
- `/site/main/docs`：完整 docs 目录，包含 16 篇 Markdown 文档与生成的 API 参考；`?doc=plugins.md` 可直接打开指定文档。
- `/site/main/changelog`：直接读取仓库 CHANGELOG.md 的版本更新记录。
- `/lab/template/entry`：跨目录 include、跨模块 part、变量隔离和嵌套调用。
- `/lab/language/entry`：多语言实验，切换简体、繁体和英文，输入称呼，查看 demo.json 词条、实际翻译与缺失词条回退。
- `/widgets/template/card/direct`：可独立访问的模块卡片片段。
- `/workspace/main/entry`：可运行任务工作台。
- `/r/hello?source=php`：PHP 自定义路由，查看映射结果和查询参数。
- `/r-json/hello?source=json`：JSON 自定义路由。
- `/r-pair/2026/11`：JSON 多参数及月份约束。
- `/tasks`、`/tasks.json`、`/tasks/save`：任务页面、GET 接口与 POST 写操作的短地址。

公开 URL 不包含 index.php，静态资源为 `/assets/...`。部署时以 `samples/www` 为站点根目录，将 URI 转发到 `index.php` 并保留原始 REQUEST_URI。

官网与交互工作台支持简体中文、繁體中文和 English，导航中的语言菜单可在当前页面切换。也可使用 `?lang=zh-cn`、`?lang=zh-tw`、`?lang=en`；选择通过 Cookie 保存一年。选择顺序为有效查询参数、已保存的 Cookie、浏览器 Accept-Language，最后回退到简体中文。浏览器的 en-*、zh-Hans / zh-CN、zh-Hant / zh-TW / zh-HK / zh-MO 会归入对应语言；与 Language 插件一致，按请求头中的出现顺序选择，并跳过 q=0。

翻译使用 `SlightPHP\Language`，入口在首次翻译前设置语言目录与 locale。词条位于 `app/languages/{zh-cn,zh-tw,en}/main.json`，格式为字符串键值对象，与 Route 一样通过 Config 解析 JSON/JSONC；不再使用 INI。控制器用 `SampleI18n::t()` 取文本，模板用 `SampleI18n::e()` 翻译并转义，JavaScript 提示共用这些词条。新增界面文案时同步维护三份文件。用户任务原文与实际配置、示例源码不做替换；文档及 ChangeLog 直接显示仓库原文。

主题按钮按「自动 → 深色 → 浅色 → 自动」切换，默认自动跟随系统主题及其实时变化，用户选择保存在浏览器 localStorage。官网、工作台和文档页共享偏好；存储不可用时仍可切换，禁用 JavaScript 时由 CSS 跟随系统。

入口通过仓库根目录 `vendor/autoload.php` 自动加载框架与插件；缺少 vendor 时先在仓库根目录执行 `composer install`。框架使用命名空间控制器；官网、任务和路由功能分别位于 Site、Workspace、Lab 命名空间模块，模板使用原生转义与根路径链接。

自定义规则位于 `config/routes.php` 和 `config/routes.json`，由 `www/index.php` 在调度前加载。入口保存已去除 index.php 的原始路径，再通过 `Route::parse($routePath)` 显式匹配；查询串不参与匹配，未命中时保留默认路径。路由实验室展示完整配置、原始 URI、映射后的 Request::route 与查询参数。详细用法见 [自定义 URL 路由](../docs/routes.md)。

工作台 `Save` 使用 `#[Route(method: 'POST')]`，`Api` 使用 `#[Route(method: 'GET')]`；路由实验室 `Entry` 使用 `#[Route(methods: ['GET', 'POST'])]`。方法不匹配时框架抛出异常，由示例入口返回 405 和 Allow；找不到入口时同样捕获异常生成 404。请求类型前缀不匹配也会返回 405，但此时 allowedMethods 为空，入口不会输出空 Allow 头；内部调用与模板 part 同样需要匹配当前 Request-Type。规则见 [核心异常与迁移](../docs/exceptions.md)及 [Route 方法注解](../docs/framework.md#route-方法注解)。

仅保留官网、当前可运行示例、模板、静态资源、配置与验证脚本。旧后台、业务控制器、外部服务配置、图片和模板缓存已移除。

```bash
php tests/RouteMappingTest.php
php samples/tests/routing.php
php samples/tests/tpl-composition.php
read -r -p "站点地址（包含 http:// 或 https://）: " SITE_URL
python3 samples/tests/website.py "$SITE_URL"
python3 samples/tests/smoke.py "$SITE_URL"
python3 samples/tests/localization.py "$SITE_URL"
```

[Tpl include / part 手册](../docs/templates.md) · [官网维护说明](../docs/website.md) · [部署与排错](../docs/deployment.md) · [框架文档](../docs/README.md)
