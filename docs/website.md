# SlightPHP 框架介绍官网

官网由 SlightPHP + STpl 构建，内容依据当前仓库源码整理，不依赖外部图片、字体或 JavaScript 库。首页介绍核心定位，内页覆盖功能、插件、入门与可运行示例。

## 访问与部署

部署示例站点后，访问其根路径 `/`。Web 根目录设为 `samples/www`，由服务器将原始 URI 转发到 `index.php`；公开 URL 无需包含入口文件名。配置参见 [部署说明](deployment.md)。

## 页面与路由

以下路径从网站根目录访问：

| 路径 | 内容 |
| --- | --- |
| `/`、`/site/main/entry` | 框架首页、安装命令、特性概览 |
| `/site/main/features` | 路径调度、双模式、CLI 与应用职责 |
| `/site/main/plugins` | 插件分类、搜索、依赖说明、SJson / STpl 示例 |
| `/site/main/guide` | 运行样例、Composer 安装、入口、控制器、部署注意事项 |
| `/site/main/examples` | 任务、路由、配置和 JSON 的实际运行入口 |
| `/site/main/about` | 框架定位、许可与源码入口 |

旧企业页及咨询逻辑已经移除，不存在的路径返回 404。

## 视觉与主题

整站采用开发者工具与文档站布局：紧凑导航、等宽代码标签、边框面板、文档侧栏和简化页脚。深色主题使用石墨灰与紫色强调色，浅色主题保留相同的信息层级。

官网和工作台共享 site.css 中的颜色变量；demo.css 仅提供工作台布局。导航中的主题按钮可切换深浅色，选择保存在浏览器 localStorage，存储不可用时仍可切换。页面禁用 JavaScript 时使用默认深色主题，所有基础内容仍可阅读。

## 交互

- 导航标记当前页，移动菜单支持按钮开关、外部点击与 Escape 关闭。
- 插件可以按基础、数据、扩展分类，并与关键词搜索组合；结果数通过状态区域播报，支持空结果提示。
- 使用指南提供 PAGE / NAMESPACE 示例切换。禁用 JavaScript 时两个代码块均可阅读。
- 代码和命令支持复制；剪贴板不可用时选中代码并提示手动复制。
- FAQ 使用原生 details，不依赖 JavaScript。
- 静态资源使用不含域名的根路径 `/assets/site.css` 和 `/assets/site.js`，并附加文件修改时间作为 v 参数，更新后自动绕过旧缓存。

## 控制器模式

本站入口显式设置 `SlightPHP::setModes(['NAMESPACE'])`，仅启用命名空间模式。官网位于 `Site\MainController`，任务工作台位于 `Workspace\MainController`，路由实验位于 `Lab\RouteController`。方法分别为 `Entry`、`Features`、`Save`、`Api` 等，不使用 page 前缀。插件通过 use 导入全局类，框架按路径规则加载控制器。

URL 无需改变，例如 `/workspace/main/save` 对应 `app/Workspace/MainController.php` 的 `Workspace\MainController::Save()`。指南仍保留 PAGE 模式的框架能力对比，但本站实际控制器全部采用 NAMESPACE。

## 文件结构与内容维护

- `samples/app/Site/MainController.php`：官网路由入口、页面数据组织和私有模板渲染方法。
- `samples/app/data/site/features.php`、`plugins.php`、`code.php`：按页面需要加载的模块内容。
- `samples/app/Workspace/MainController.php`：任务、会话、CSRF、JSON 和工作台模板渲染。
- `samples/app/Lab/RouteController.php`：独立的命名空间路由实验与模板渲染。
- `samples/www/index.php`：通过 `vendor/autoload.php` 加载核心与插件，只做环境配置、URI 解析和调度。
- `samples/app/templates/head.tpl`、`footer.tpl`：公共布局与元信息。
- `samples/app/templates/site/`：首页、功能、插件、指南、示例、关于、错误页。
- `samples/www/assets/site.css`、`site.js`：响应式样式与交互。

动态文本在模板中使用原生 `htmlspecialchars()` 转义，固定链接直接使用根路径；示例代码作为字符串传入模板，避免被 STpl 当作模板代码执行。模板继续使用 `{%` / `%}` 分隔符，编译目录是 `app/templates_c`。

更新核心或插件实现时，同步更新官网与 docs 文档，尤其是模式优先级、路径规则、响应行为及插件依赖。不要把所有历史插件表述为无需外部服务或已全面兼容当前 PHP。

## 验证

部署示例站点后，输入站点地址运行 HTTP 验证：

```bash
read -r -p "站点地址（包含 http:// 或 https://）: " SITE_URL
python3 samples/tests/website.py "$SITE_URL"
python3 samples/tests/smoke.py "$SITE_URL"
```

官网验证覆盖页面、当前导航、代码转义、插件清单、无入口前缀链接、错误页与真实示例入口；smoke 回归任务生命周期、校验与会话隔离。前端脚本可通过 `node --check samples/www/assets/site.js` 检查语法。

已通过系统 Firefox 在线验证 1440、1024、820、390 宽度下的导航选中下划线、实际点击主题切换、刷新后偏好保留及无横向溢出；工作台和 localStorage 不可用时的主题切换也已验证。插件筛选、复制降级和控制器切换可结合实际页面继续验收。
