# SlightPHP 官网与交互示例

示例展示 SlightPHP 6.x 当前行为，安装命令使用发布版本 6.7.2，包含请求类型前缀校验、结构化调度异常、Route 方法注解与 PHP / JSON 路由。模板数据使用 Tpl 实例隔离，控制器入口使用 `Request $request, Response $response`，路径从 `$request->route` 读取。修改说明统一收录在 [6.x 更新记录](../CHANGELOG.md#6x)；升级已有应用请参阅 [6.x 升级与迁移指南](../CHANGELOG.md#6x-升级与迁移指南)，按起始版本和功能模块执行适用步骤。

正式官网为 [https://slightphp.com/](https://slightphp.com/)。自行部署示例站点后，访问部署地址的根路径 `/`，即可浏览框架介绍并体验交互示例。

所有页面与 Context 模块均默认自动转义，不再调用 `setContextMode()` 或 `getContextMode()`。模板传原始数据和翻译原文，文档浏览器仅对自身生成的可信 HTML 使用末尾 raw；客户端词条以数组传入 application/json 数据块。入口在会话启动前设置安全 Cookie 参数，反向代理需按[部署说明](../docs/deployment.md#cookie与会话安全)配置可信转发。文档页首提供 6.x 更新记录和统一迁移指南的直达链接。

- `/#features`：首页中的功能特性、调度流程与应用职责；旧 `/site/main/features` 跳转到该区域。
- `/site/main/plugins`：插件搜索、分类与运行条件。
- `/site/main/plugins#route-custom`、`#route-methods`：Route 自定义 URL 映射与方法注解，可复制代码并验证真实响应。
- `/site/main/guide`：入门，包含安装、入口、控制器与部署说明；导航位于首页之后。
- `/site/main/examples`：以 samples 源码讲解目录规划、应用入口、路由映射、Route 方法注解、Request-Type、多语言、配置管理、模板组织、表单处理和数据库操作；提供交互运行入口。
- `/site/main/docs`：统一浏览 docs 中的 Markdown 文档、API 参考和根目录 ChangeLog；`?doc=plugins.md` 打开插件文档，`?doc=CHANGELOG.md` 打开更新记录。
- `/site/main/changelog`：旧地址跳转至文档中的 ChangeLog。
- `/lab/template/entry`：跨目录 include、跨模块 part、变量隔离，以及全部修饰器、HTML/URL/JS/CSS 自动转义、模板注释、分隔符和缓存用例。
- `/widgets/template/context`：独立自动模式片段，展示 raw/escape/default/tr/tostring/version、修饰链、JS/事件的实际运行、默认 {} 与 literal、模板注释、缓存变量更新和错误捕获。
- `/lab/language/entry`：多语言实验，切换简体、繁体和英文，输入称呼，查看 demo.json 词条、实际翻译与缺失词条回退；[独立使用手册](../docs/language.md)。
- `/lab/csrf/entry`：CSRF 实验，对比普通表单、JSON 请求、默认/自定义名称、缺失或错误 token，以及更新后旧表单失效；手册位于 `/site/main/docs?doc=csrf.md`。
- `/lab/type/entry`：普通请求调用 `Entry()`，带 `Request-Type: ajax` 时调用 `ajaxEntry()`；示例页可直接发送请求对比 200 与 405。
- `/widgets/template/card/direct`：可独立访问的模块卡片片段。
- `/workspace/main/entry`：可运行任务工作台。
- `/r/hello?source=php`：PHP 自定义路由，查看映射结果和查询参数。
- `/r-json/hello?source=json`：JSON 自定义路由。
- `/r-pair/2026/11`：JSON 多参数及月份约束。
- `/tasks`、`/tasks.json`、`/tasks/save`：任务页面、GET 接口与 POST 写操作的短地址。

公开 URL 不包含 index.php，静态资源为 `/assets/...`。部署时以 `samples/www` 为站点根目录，将 URI 转发到 `index.php` 并保留原始 REQUEST_URI。

全部模板和页面展示的代码统一使用 Tpl 默认 `{` / `}` 分隔符；入口调用 `Tpl::setDelimter()`，模块不再局部切换。与模板语法完全同形的原生 JS/CSS 代码使用 `{literal}...{/literal}` 保留。

默认 `{` / `}` 分隔符下，模板注释使用 `{* ... *}`，支持单行、多行，编译时移除且不执行内部标签；不能嵌套，未闭合会抛出 `InvalidArgumentException`。`app/templates/widgets/template-comments.tpl` 实际展示注释内的赋值与 HTML 被移除，变量仍输出 `VISIBLE`，literal 内的注释语法则按原文显示。完整规则、literal 写法及与 HTML 注释的区别见[模板注释手册](../docs/templates.md#模板注释)。

官网与交互工作台支持简体中文、繁體中文和 English，导航中的语言菜单可在当前页面切换。也可使用 `?lang=zh-cn`、`?lang=zh-tw`、`?lang=en`；选择通过 Cookie 保存一年。选择顺序为有效查询参数、已保存的 Cookie、浏览器 Accept-Language，最后回退到简体中文。浏览器的 en-*、zh-Hans / zh-CN、zh-Hant / zh-TW / zh-HK / zh-MO 会归入对应语言；与 Language 插件一致，按请求头中的出现顺序选择，并跳过 q=0。

翻译使用 `SlightPHP\Language`，入口在首次翻译前设置语言目录与 locale。词条位于 `app/languages/{zh-cn,zh-tw,en}/main.json`，格式为字符串键值对象，与 Route 一样通过 Config 解析 JSON/JSONC；不再使用 INI。控制器用 `SampleI18n::t()` 取文本，模板用 `SampleI18n::t()` 获取翻译原文并由 Tpl 编码，JavaScript 提示共用这些词条。新增界面文案时同步维护三份文件。用户任务原文与实际配置、示例源码不做替换；文档及 ChangeLog 直接显示仓库原文。

主题按钮按「自动 → 深色 → 浅色 → 自动」切换，默认自动跟随系统主题及其实时变化，用户选择保存在浏览器 localStorage。官网、工作台和文档页共享偏好；存储不可用时仍可切换，禁用 JavaScript 时由 CSS 跟随系统。

入口通过仓库根目录 `vendor/autoload.php` 自动加载框架与插件；缺少 vendor 时先在仓库根目录执行 `composer install`。框架使用命名空间控制器；官网、任务和路由功能分别位于 Site、Workspace、Lab 命名空间模块，模板使用上下文自动转义与根路径链接。

自定义规则位于 `config/routes.php` 和 `config/routes.json`，由 `www/index.php` 在调度前加载。入口保存已去除 index.php 的原始路径，再通过 `Route::parse($routePath)` 显式匹配；查询串不参与匹配，未命中时保留默认路径。路由实验室展示完整配置、原始 URI、映射后的 Request::route 与查询参数。详细用法见 [自定义 URL 路由](../docs/routes.md)。

工作台 `Save` 使用 `#[Route(method: 'POST', csrf: true)]`，`Api` 使用 `#[Route(method: 'GET')]`；路由实验室 `Entry` 使用 `#[Route(methods: ['GET', 'POST'])]`。方法不匹配时框架抛出异常，由示例入口返回 405 和 Allow；找不到入口时同样捕获异常生成 404。请求类型前缀不匹配也会返回 405，但此时 allowedMethods 为空，入口不会输出空 Allow 头；内部调用与模板 part 同样需要匹配当前 Request-Type。规则见 [核心异常与处理](../docs/exceptions.md)及 [Route 方法注解](../docs/framework.md#route-方法注解)。

仅保留官网、当前可运行示例、模板、静态资源与配置。旧后台、业务控制器、外部服务配置、图片和模板缓存已移除。

部署后通过浏览器访问官网、任务工作台、路由和模板实验，检查页面、交互及错误响应。具体步骤见[部署与排错](../docs/deployment.md#验证)。

[Tpl include / part 手册](../docs/templates.md) · [官网维护说明](../docs/website.md) · [部署与排错](../docs/deployment.md) · [框架文档](../docs/README.md)

[CSRF 使用手册](../docs/csrf.md)提供默认/自定义字段、`tokenInput()`、Ajax 请求头、token 生命周期与异常处理示例。

## 插件交互实验

官网插件页为 Tpl、Config、Log、Db、Redis、Http、Error、Captcha、Request、Response 提供独立运行与手册入口。Tpl 沿用 `/lab/template/entry`；其他入口为 `/lab/plugin/entry/{插件小写名}`。

完整索引见[插件指南](../docs/plugins.md)。[Request 手册](../docs/request.md)包含 GET、multipart 上传元信息与 JSON / 请求头实验；[Response 手册](../docs/response.md)包含状态、响应头、write / end、Cookie 和 303 跳转实验。

所有 POST 实验由 Route 校验 CSRF，提交结果后 303 跳转；Request JSON 返回独立 JSON 响应。日志和错误实验使用临时文件并清理，验证码在 Session 中一次性校验。Db / Redis 必须在私有的 `samples/config/services.local.json` 中配置服务并启用，配置模板为 `samples/config/services.example.json`；也支持环境变量 `SLIGHTPHP_SAMPLE_SERVICES` 指向私有文件。未准备好依赖或配置时显示提示。Http 默认访问固定 httpbin 服务，网络失败会提示错误。

文档页左侧将所有版本更新与迁移说明统一放在最下方的 ChangeLog 入口；旧的迁移文档地址统一跳转到 6.x 升级与迁移指南，旧章节锚点继续可用。
