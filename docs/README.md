# SlightPHP 6.7.1 框架使用文档

6.7.1 新增 Session CSRF 防护，通过 Route 的 `csrf` 参数启用，支持自定义字段/请求头、`tokenInput()` 及公共配置。samples 提供三种表单写法与 Ajax、token 更新实验；接入见 [CSRF 使用手册](csrf.md)。

6.7.0 默认启用 Tpl 上下文自动转义，移除 `setContextMode()`、`getContextMode()` 及兼容编译分支。模板应传入原始数据，可信 HTML 用末尾 `|raw`；升级前需迁移手工编码、JSON 数据块和动态属性名，见 [6.7 迁移与不兼容说明](../CHANGELOG.md#67-迁移指南)。

6.6.0 新增可选的 Tpl 上下文自动转义、编译期执行计划和默认花括号识别；默认仍为兼容模式。本版还迁移 Db 内部类和模板辅助函数，调整 Cookie 默认策略，实现 Redis Sentinel，并修复 Language 切换缓存。即使不开启自动转义，升级也需检查类名、全局函数及 Cookie 配置，见 [6.6 迁移与不兼容说明](../CHANGELOG.md#66-迁移指南)。

6.5.3 将 Language 语言文件改为 JSON/JSONC，与 Route 共用 Config 解析；旧 INI 词条需转换为字符串键值对象。官网与示例支持简体、繁体和英文，新增多语言实验、完整文档阅读与默认自动的主题切换，见 [6.5.3 迁移说明](../CHANGELOG.md#65-language-语言文件改为-jsonjsonc653)。

6.5.2 新增请求类型前缀校验，缺失或不匹配的 `Request-Type` 会在方法存在性、Route 注解和控制器构造前被拒绝（405），显式 URL 与内部调用均适用。应用仅在异常的 `allowedMethods` 非空时输出 `Allow`；从 6.5.1 升级也需检查带类型前缀的入口及异常处理，见 [6.5 迁移指南](../CHANGELOG.md#65-请求类型前缀校验652)。

本目录的 Markdown 文档根据当前 PHP 源码整理。[API 参考](index.html)由当前源码生成。

官网：[https://slightphp.com/](https://slightphp.com/)。

- [核心异常与处理](exceptions.md)
- [快速开始与项目结构](getting-started.md)
- [核心调度、路由与 API](framework.md)
- [自定义 URL 路由、PHP / JSON 配置与示例](routes.md)
- [Request、Response 与控制器新签名](http.md)
- [框架官网与维护说明](website.md)
- [完整示例与交互说明](samples.md)
- [插件与扩展](plugins.md)
- [CSRF 使用手册与可运行示例](csrf.md)
- [Tpl 模板与模块组合手册](templates.md)
- [Language 使用手册与示例](language.md)
- [Config 使用手册与示例](config.md)
- [Log 使用手册与示例](log.md)
- [Db 使用手册与示例](db.md)
- [Redis 使用手册与示例](redis.md)
- [Http 使用手册与示例](http-client.md)
- [Error 使用手册与示例](error.md)
- [Captcha 使用手册与示例](captcha.md)
- [Request 使用手册与示例](request.md)
- [Response 使用手册与示例](response.md)
- [部署、排错与安全边界](deployment.md)
- [ChangeLog：更新记录与升级迁移](../CHANGELOG.md)

SlightPHP 是轻量级调度框架：核心负责从路径加载控制器并调用方法，应用负责响应输出、状态码、权限、校验和数据管理。插件按需使用；Route 方法注解的 HTTP 方法与 CSRF 策略由核心调度时执行，Csrf 仍需应用显式启动 Session。

6.5.1 移除 `SlightPHP\Json` 插件，改用原生 JSON 函数；Route 文件配置改为 JSON/JSONC 数组；插件依赖改为按需安装。6.5.0 新增 Route 方法注解，核心失败统一抛出结构化异常，并完善自定义 URL 路由与请求内元数据缓存。从 6.4.x 升级也需调整调度失败处理，具体见 [6.5 迁移指南](../CHANGELOG.md#65-迁移指南)。

6.4.0 将 Tpl 模板变量及 `assign()`、`fetch()` 改为实例级；已有静态调用和直接变量表访问需迁移，具体步骤见 [6.4 迁移指南](../CHANGELOG.md#64-迁移指南)。

6.3.0 将控制器的 `$inPath` 数组参数替换为 Request，并向入口方法传入 Response；已有 6.2 应用也需要检查签名。路径数组改从 `$request->route` 读取，具体步骤见 [6.3 迁移指南](../CHANGELOG.md#63-迁移指南)。
