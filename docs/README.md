# SlightPHP 6.x 框架使用文档

当前发布版本为 **6.7.2**，新增 CSRF 会话按需启动与应用初始化回调，原生启动失败统一抛出框架异常。修改说明见 [6.7.2](../CHANGELOG.md#672--2026-11-19)，接入与兼容事项见 [CSRF 手册](csrf.md#1-配置-session-与按需启动)。

6.x 提供 Session CSRF 防护，通过 Route 的 `csrf` 参数启用，支持自定义字段/请求头、`tokenInput()` 及公共配置。samples 提供三种表单写法与 Ajax、token 更新实验；接入见 [CSRF 使用手册](csrf.md)。

Tpl 默认启用上下文自动转义，已移除 `setContextMode()`、`getContextMode()` 及兼容编译分支。模板应传入原始数据，可信 HTML 用末尾 `|raw`；升级前需迁移手工编码、JSON 数据块和动态属性名，见 [6.x 升级与迁移指南](../CHANGELOG.md#6x-升级与迁移指南)。

旧应用升级统一参阅 [6.x 升级与迁移指南](../CHANGELOG.md#6x-升级与迁移指南)。指南提供起始版本对照表，并按控制器、模板、配置、数据库与会话等模块合并迁移步骤；6.0 至 6.7 的修改统一汇总在[6.x 更新记录](../CHANGELOG.md#版本更新记录)。

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

SlightPHP 是轻量级调度框架：核心负责从路径加载控制器并调用方法，应用负责响应输出、状态码、权限、校验和数据管理。插件按需使用；Route 方法注解的 HTTP 方法与 CSRF 策略由核心调度时执行，Csrf 按需启动 Session，应用负责会话配置或提供初始化回调。
