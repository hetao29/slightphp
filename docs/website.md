# SlightPHP 框架介绍官网（6.x）

官网由 SlightPHP + Tpl 构建，内容依据当前仓库源码整理，不依赖外部图片、字体或 JavaScript 库。首页介绍核心定位，内页覆盖功能、插件、入门与可运行示例。

正式官网地址为 [https://slightphp.com/](https://slightphp.com/)，页面统一展示 6.x 系列，安装命令使用当前发布版本 6.7.4。GitHub 链接用于源码与许可证，不作为官网地址。

## 访问与部署

部署示例站点后，访问其根路径 `/`。Web 根目录设为 `samples/www`，由服务器将原始 URI 转发到 `index.php`；公开 URL 无需包含入口文件名。配置参见 [部署说明](deployment.md)。

## 页面与路由

以下路径从网站根目录访问：

| 路径 | 内容 |
| --- | --- |
| `/`、`/site/main/entry`、`/#features` | 框架首页、安装命令、完整功能特性、调度流程、控制器映射与应用职责 |
| `/site/main/features` | 保留旧地址，跳转至首页功能特性区域并保留所选语言 |
| `/site/main/plugins` | 插件分类、搜索、依赖说明，各插件运行示例与独立手册，Tpl 与 Route 功能示例 |
| `/site/main/guide` | 入门：运行样例、Composer 安装、入口、控制器、部署注意事项 |
| `/site/main/examples` | 十节 samples 源码讲解：目录、入口、路由、Route 注解、Request-Type、多语言、配置、模板、表单与数据库操作，以及交互验证 |
| `/site/main/docs`、`?doc=plugins.md`、`?doc=index.html` | 完整 docs 目录、单篇指南与生成的 API 参考 |
| `/site/main/docs?doc=CHANGELOG.md#6x-升级与迁移指南` | 6.x 统一升级与迁移：起始版本对照表及各模块步骤 |
| `/site/main/docs?doc=CHANGELOG.md#6x` | 直接读取仓库 CHANGELOG.md 中合并后的 6.x 修改记录 |
| `/site/main/changelog` | 旧地址跳转至文档中的 ChangeLog |
| `/lab/template/entry`、`/widgets/template/context` | include / part、变量作用域、默认自动转义、全部修饰器及 HTML/URL/JS/CSS 自动转义 |
| `/lab/language/entry` | 三种语言的实时翻译、动态称呼、JSON 语言文件与缺失词条回退 |
| `/lab/plugin/entry/session` | Session 实验：引用读写、提交计数、刷新保留与浏览器会话隔离；手册 `?doc=session.md` |
| `/lab/csrf/entry` | CSRF 实验：普通表单、JSON 请求头、自定义名称、403 对比与 token 更新；手册 `?doc=csrf.md` |
| `/lab/type/entry` | Request-Type 示例：普通请求调用 Entry，ajax 请求头调用 ajaxEntry，直接访问前缀方法缺少匹配头返回 405 |
| `/r/hello`、`/r-json/hello`、`/r-pair/2026/11` | PHP / JSON 自定义路由实验与多参数 |
| `/site/main/about` | 框架定位、许可与源码入口 |

旧企业页及咨询逻辑已经移除，不存在的路径返回 404。

## 视觉与主题

整站采用开发者工具与文档站布局：紧凑导航、等宽代码标签、边框面板、文档侧栏和简化页脚。深色主题使用石墨灰与紫色强调色，浅色主题保留相同的信息层级。

官网和工作台共用 favicon_dark.png 与 favicon_light.png 作为浏览器图标，随自动或手动选择的深浅主题切换。禁用 JavaScript 时通过 prefers-color-scheme 选择图标。

官网和工作台共享 site.css 中的颜色变量；demo.css 仅提供工作台布局。导航中的主题按钮按「自动 → 深色 → 浅色 → 自动」切换，默认自动跟随系统主题，并实时响应系统变化。选择保存在浏览器 localStorage，存储不可用时仍可切换。theme.js 在页面正文解析前应用偏好，减少主题闪烁；禁用 JavaScript 时由 CSS 跟随系统主题，所有基础内容仍可阅读。

## 多语言与文档阅读

官网及交互工作台使用 `SlightPHP\Language` 提供简体中文（zh-cn）、繁體中文（zh-tw）与英文（en）。有效 `lang` 查询参数优先于 `slightphp-language` Cookie，然后按 Accept-Language 中的出现顺序匹配，跳过 q=0，最终回退到简体中文。浏览器语言的地区与文字变体归入这三种语言；手动选择保存一年，切换保留当前路径、查询参数和浏览器片段。

入口在首次调用 Language::tr() 之前设置 locale；三份 JSON 词条位于 `samples/app/languages/{zh-cn,zh-tw,en}/main.json`，通过 Config 解析，格式与语言文件说明见 [Language 使用手册](language.md)。`SampleI18n::t()` 提供控制器文本，`e()` 在翻译后转义 HTML，客户端主题、筛选、复制和删除提示通过 JSON 共享同一批词条。预置任务随语言切换，用户输入的任务内容保留原文。

顶栏导航顺序为「首页 → 入门 → 示例 → 插件 → 文档」。「文档」统一读取 docs 中的 Markdown、index.html API 参考和根目录 CHANGELOG.md，提供目录、代码块、表格、文档间跳转和章节锚点；页首提供 6.x 更新记录和升级迁移的直达链接。内容保留仓库原文，更新无需另行复制。仅允许文档清单中的文件和固定的根目录 ChangeLog，路径越界、非文档文件和无效参数返回 404。

## 交互

- 导航标记当前页，移动菜单支持按钮开关、外部点击与 Escape 关闭。
- 插件可以按基础、数据、扩展分类，并与关键词搜索组合；结果数通过状态区域播报，支持空结果提示。
- 「入门」紧随「首页」，展示安装步骤和命名空间控制器示例。
- 「示例」以 samples 为主线，提供十节源码讲解、章节导航、代码复制与源码链接；数据库章节演示 MySQL 接入和增删改查，当前工作台继续使用 Session。
- 功能特性与调度说明合并在首页，导航不再单列功能页，站内链接直接跳转首页区域。
- 多语言实验提供 GET 表单，可切换简体、繁体和英文、输入称呼，实际调用 Language::tr() 的 demo 区域并展示 JSON 源文件；无需 JavaScript 即可运行。
- 插件页的 Route 卡片可跳转到自定义 URL 映射和 HTTP 方法注解；包含 PHP / JSON 配置、复制按钮及真实 GET / POST 验证入口。
- 代码和命令支持复制；剪贴板不可用时选中代码并提示手动复制。
- FAQ 使用原生 details，不依赖 JavaScript。
- 静态资源使用不含域名的根路径 `/assets/site.css` 和 `/assets/site.js`，并附加文件修改时间作为 v 参数，更新后自动绕过旧缓存。

## 控制器模式

本站使用命名空间控制器。官网位于 `Site\MainController`，任务工作台位于 `Workspace\MainController`，路由实验位于 `Lab\RouteController`。方法分别为 `Entry`、`Features`、`Save`、`Api` 等，不使用 page 前缀。插件通过 use 导入命名空间类，框架按路径规则加载控制器。

URL 无需改变，例如 `/workspace/main/save` 对应 `app/Workspace/MainController.php` 的 `Workspace\MainController::Save()`。框架仅支持命名空间控制器。

## 文件结构与内容维护

- `samples/app/Site/MainController.php`：官网路由入口、页面数据组织和私有模板渲染方法。
- `samples/app/data/site/features.php`、`plugins.php`、`code.php`、`examples.php`：按页面需要加载的模块内容；examples.php 维护示例讲解及数据库扩展代码，说明文案由三套语言词条提供。
- `samples/app/I18n.php`、`languages/`：通过 Language 插件选择语言并提供三套界面词条。
- `samples/app/Documents.php`：读取完整文档与更新记录，渲染 Markdown 并解析站内文档链接。
- `samples/app/Workspace/MainController.php`：任务、会话、CSRF、JSON 和工作台模板渲染。
- `samples/app/Lab/RouteController.php`：独立的命名空间路由实验与模板渲染。
- `samples/config/routes.php`、`routes.json`：自定义 URL 映射，配置与验证见 [自定义路由手册](routes.md)。
- `samples/app/Lab/TemplateController.php`、`samples/app/Widgets/TemplateController.php`：模板组合页面与跨模块片段，调用说明见 [Tpl 手册](templates.md)。
- `samples/app/Lab/LanguageController.php`、`samples/app/languages/*/demo.json`：可运行的多语言实验与三套独立词条。
- `samples/app/Lab/CsrfController.php`、`templates/workspace/csrf.tpl`：CSRF 交互实验，包含默认/自定义名称、tokenInput、tokenMeta 与 token 更新；手册位于 docs/csrf.md。
- `samples/app/Lab/TypeController.php`：返回纯文本方法名的 Request-Type 示例，供示例页发送真实请求。
- `samples/www/index.php`：通过 `vendor/autoload.php` 加载核心与插件，只做环境配置、URI 解析和调度。
- `samples/app/templates/head.tpl`、`footer.tpl`：公共布局与元信息。
- `samples/app/templates/favicons.tpl`：官网和工作台共用的深浅主题浏览器图标。
- `samples/app/templates/site/`：首页、功能、插件、指南、示例、文档、关于、错误页。
- `samples/www/assets/site.css`、`site.js`、`theme.js`：响应式样式、页面交互和默认自动的主题偏好。

动态文本和翻译原文由 Tpl 按上下文自动转义，固定链接直接使用根路径；示例代码作为字符串传入模板，避免被 Tpl 当作模板代码执行。模板及展示的代码统一使用默认 `{` / `}` 分隔符，编译目录是 PHP 的系统临时目录。

更新核心或插件实现时，同步更新官网与 docs 文档，尤其是路径规则、响应行为及插件依赖。不要把所有历史插件表述为无需外部服务或已全面兼容当前 PHP。

## 验证

部署示例站点后，按[部署文档](deployment.md#验证)访问官网和示例入口，检查当前导航、代码转义、复制按钮、插件清单、无入口前缀链接与错误页；在独立会话中检查任务操作、输入校验与会话隔离。前端脚本可通过 `node --check samples/www/assets/site.js` 检查语法。

已通过 Chromium 验证简体、繁体和英文在 1440、1101、1024、820、390、320 宽度下的官网、完整文档和工作台没有横向溢出；同时验证自动跟随系统、手动切换、刷新后偏好保留、存储不可用时切换、语言切换保留查询与片段，以及动态筛选提示。

文档页左侧将所有版本更新与迁移说明统一放在最下方的 ChangeLog 入口；旧的迁移文档地址统一跳转到 6.x 升级与迁移指南，旧章节锚点继续可用。
