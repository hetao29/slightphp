# 完整示例说明

示例统一展示 SlightPHP 6.x 当前行为，当前发布版本为 6.7.4。Session 实验位于 `/lab/plugin/entry/session`，展示引用读写、跨请求保存和会话隔离，见 [Session 手册](session.md)。6.0 至 6.7 的修改已合并为 [6.x 更新记录](../CHANGELOG.md#6x)，旧应用按[统一迁移指南](../CHANGELOG.md#6x-升级与迁移指南)中的起始版本对照表和模块步骤升级。官网文档页首提供这两个入口。

启动方法见 [快速开始](getting-started.md)。默认首页为框架介绍官网，详见 [框架官网说明](website.md)。从页脚进入任务工作台后，可按「任务工作台 → 路由实验室 → 插件与环境」体验框架。

官网 `/site/main/examples` 以 samples 源码为主线，分十节讲解目录规划、应用入口、路由映射、Route 方法注解、Request-Type、多语言设置、配置管理、模板组织、表单处理和数据库操作，并提供源码链接与交互验证。数据库章节演示 MySQL 建表、连接配置和 Db 增删改查；当前工作台仍使用 Session。

## 示例功能

- 任务列表包含初始示例任务，可新增、切换完成状态和删除。
- 按全部、待完成、已完成筛选，可同时按标题搜索；无匹配结果时显示空状态。
- 列表显示总数、待完成数量和完成进度。
- 提交成功或校验失败后显示提示；POST 返回 303，刷新不会重复提交。
- 页面支持窄屏、键盘焦点、输入标签、当前导航、状态播报和跳转到正文。
- JavaScript 负责搜索筛选、删除确认及路由实验输入跳转；任务增删和完成切换不依赖 JavaScript，关闭 JavaScript 后删除会直接提交。

## 路由与职责

以下为从网站根目录访问的路径：

| 路径 | HTTP 方法 | 行为 |
| --- | --- | --- |
| `/workspace/main/entry` | GET | 显示会话任务列表 |
| `/workspace/main/save` | POST | `action=add/toggle/delete`，需要 CSRF token |
| `/workspace/main/api` | GET | 当前会话任务 JSON，含 data 和 meta |
| `/lab/route/entry` | GET、POST | 命名空间控制器与实际路径数组 |
| `/lab/route/entry/hello` | GET、POST | 演示附加路径参数 |
| `/r/hello?source=php` | GET、POST | PHP 自定义路由与查询参数 |
| `/r-json/hello?source=json` | GET、POST | JSON 自定义路由 |
| `/r-pair/2026/11` | GET、POST | JSON 多参数，月份限制 01–12 |
| `/tasks`、`/tasks.json` | GET | 任务页面和 JSON 接口的短地址 |
| `/tasks/save` | POST | 写操作短地址，仍需 CSRF token |
| `/workspace/main/plugins` | GET | 真实解析 Config 配置及检测扩展 |
| `/lab/template/entry` | GET | include/part、变量作用域、内置/自定义/PHP 系统 Modifier、多级串联、模板注释、上下文转义与编译缓存 |
| `/lab/csrf/entry` | GET | 表单、Ajax、自定义字段、403 拒绝与 token 更新实验 |
| `/lab/csrf/submit`、`/lab/csrf/customsubmit` | POST | 校验成功后计数并 303 跳转 |
| `/lab/csrf/check`、`/lab/csrf/customcheck` | POST | 默认或自定义请求头校验，返回 JSON |
| `/lab/csrf/rotate` | POST | 校验当前 token 后更新 |
| `/lab/language/entry` | GET | 简体、繁体、英文切换、动态称呼、JSON 词条与缺失词条回退 |
| `/widgets/template/card/direct` | GET | 独立渲染模块卡片，读取路径参数 direct |
| `/widgets/template/context` | GET | 独立自动模式片段：自定义函数加载、系统函数、多级修饰链、JS/CSS、事件、分隔符、缓存和错误用例 |
| 不存在的控制器或方法 | 任意 | HTTP 404 和可返回首页的错误页 |

`save` 的错误 HTTP 方法返回 405、CSRF 失败返回 403；业务输入错误通过跳转后的提示呈现。`Save`、`Api` 与路由实验室 `Entry` 使用 [Route 注解](framework.md#route-方法注解)限制 HTTP 方法；不匹配时框架抛出异常，由示例入口生成 405、Allow 和 `Method Not Allowed` 正文。入口同时将找不到文件或入口的异常转换为 404。

自定义配置在 `samples/config/routes.php` 和 `routes.json`，入口在 run() 前加载并显式匹配规范化后的路径。路由实验室支持输入短地址参数，并显示原始请求、映射后的路径、查询参数和配置源码。用法、匹配规则与验证入口见 [自定义 URL 路由](routes.md)。

## 模板与模块组合

从工作台侧栏或官网示例索引进入 `/lab/template/entry`。父模板通过动态文件名跨目录 include 公共模板，再通过 part 调用 Widgets 模块的 Card、Returned、Mixed、Nested 和 Ended 方法。页面展示 PARENT、CHILD、NESTED 三个实例的变量作用域、路径参数和实际输出顺序。调用规则及代码位置见 [Tpl 模板与模块组合手册](templates.md)。

第 5 节的 Context 模块与主页面默认自动转义，运行 raw、escape、default、tr、tostring、version 和串联修饰器，并展示 HTML 属性、URL、JS 值/字符串/正则/事件、CSS、默认 {}、模板注释、literal 和缓存变量更新。JS 结果由浏览器实际计算；错误示例由控制器分别捕获，不向主页面插入未完成的输出。三种界面语言均有对应说明。

samples 的全部模板和展示代码统一使用默认 `{` / `}`。入口调用不带参数的 `Tpl::setDelimter()`，各模块无需切换分隔符；原生 JS/CSS 中与模板同形的代码使用 `{literal}...{/literal}`。

模板注释示例实际执行 `widgets/template-comments.tpl`：`{* ... *}` 内的赋值和 HTML 被移除，输出仍为 `VISIBLE`；literal 中的注释语法原样显示。支持单行、多行，不能嵌套，未闭合会报错，详见[模板注释](templates.md#模板注释)。

访问 `/lab/template/entry` 检查模板组合与变量作用域；预期结果见 [Tpl 模板与模块组合手册](templates.md#samples-文件与预期结果)。

## 多语言实验

从官网示例索引、插件页的 Language 卡片或工作台侧栏进入 `/lab/language/entry`。表单通过 GET 提交 `lang=zh-cn/zh-tw/en` 和 `name`，无需 JavaScript；例如 `/lab/language/entry?lang=en&name=SlightPHP`。入口在第一次翻译前选择 locale，实验实际调用 `Language::tr('greeting', 'demo')`，再用 `strtr()` 替换 `{name}`。页面同时展示当前语言、翻译结果、实际读取的 `app/languages/{locale}/demo.json` 和可复制的 PHP 代码。

`missing_demo_key` 未在 demo 区域定义，实际返回原词条键。称呼要求 1–80 个字符；无效输入返回 400 并提示，所有动态文本在 HTML 输出时转义。示例切换语言会发起新请求，由入口调用 `setLocale()` 更新候选语言；应用在同一请求内也可通过该 setter 切换语言。JSON/JSONC 格式、回退与缓存规则见 [Language 使用手册](language.md)。

## CSRF 实验

从工作台侧栏、示例索引或插件页进入 `/lab/csrf/entry`。分别提交普通表单与 JSON 请求，比较有效、缺失和错误 token 的 200 / 403；自定义名称面板使用 `csrf: '--csrf--token'`，表单字段和请求头均同名。页面对比手写字段、预先传入 `Csrf::tokenInput()` 的 HTML、以及模板直接调用 `{\SlightPHP\Csrf::tokenInput()|raw}` 三种写法；Ajax 从 `<head>` 中由 `tokenMeta()` 生成的默认或自定义名称 meta 标签读取 `content`，发送对应请求头，成功提交才增加计数。更新 token 后，旧标签页及任务工作台中的旧表单均会失效。所有实验支持三种语言，表单和更新不依赖 JavaScript。完整接入步骤、API、异常响应与验证方法见 [CSRF 使用手册](csrf.md)。

## 数据与安全设计

入口显式选择 files 会话后端并使用系统临时目录，避免依赖机器默认的 Redis 会话配置。数据保存在 PHP session 中，首次访问初始化三条任务。不同会话相互隔离；同一会话的 JSON 接口与页面共享数据。关闭浏览器、会话清理等可能导致数据丢失，不承诺持久存储。

任务标题要求 1–80 个 Unicode 字符，每会话最多 50 项；浏览器校验之外还有服务端校验。动态 HTML 由 Tpl 按上下文自动转义；写操作使用 `#[Route(method: 'POST', csrf: true)]`，表单通过 `_csrf` 提交 `Csrf::token()` 的值，校验失败抛异常并由入口返回 403。不要将其当作带用户体系的生产任务系统：没有登录、跨设备同步、持久数据库或权限管理。

## 验证

部署示例站点后，按[部署文档](deployment.md#验证)检查页面、路由、任务操作与错误响应。使用独立会话验证任务隔离，并检查空标题、超长标题与动态文本转义。缩小至手机宽度，使用 Tab 操作表单与任务按钮，组合搜索和筛选，取消一次删除，然后刷新确认不会重复新增。

## 清理后的范围

samples 仅包含官网与当前可运行的任务、路由、配置、JSON、模板组合、多语言、Session 和 CSRF 示例。历史 user、photos、guestbook、Auction、数据库与 Redis 测试、backend、locale、旧配置和模板缓存已删除。插件实现仍保留在仓库 plugins 中，可参考插件文档独立使用。

## 插件交互实验

官网插件页为 Session、Tpl、Config、Log、Db、Redis、Http、Error、Captcha、Request、Response 提供独立运行与手册入口。Tpl 沿用 `/lab/template/entry`；其他入口为 `/lab/plugin/entry/{插件小写名}`。

完整索引见[插件指南](plugins.md)。[Request 手册](request.md)包含 GET、multipart 上传元信息与 JSON / 请求头实验；[Response 手册](response.md)包含状态、响应头、write / end、Cookie 和 303 跳转实验。

所有 POST 实验由 Route 校验 CSRF，提交结果后 303 跳转；Request JSON 返回独立 JSON 响应。日志和错误实验使用临时文件并清理，验证码在 Session 中一次性校验。Db / Redis 必须在私有的 `samples/config/services.local.json` 中配置服务并启用，配置模板为 `samples/config/services.example.json`；也支持环境变量 `SLIGHTPHP_SAMPLE_SERVICES` 指向私有文件。未准备好依赖或配置时显示提示。Http 默认访问固定 httpbin 服务，网络失败会提示错误。
