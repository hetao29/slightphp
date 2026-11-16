# SlightPHP 6.5.3 框架使用文档

6.5.3 将 Language 语言文件改为 JSON/JSONC，与 Route 共用 Config 解析；旧 INI 词条需转换为字符串键值对象。官网与示例支持简体、繁体和英文，新增多语言实验、完整文档阅读与默认自动的主题切换，见 [6.5.3 迁移说明](migration-6.5.md#language-语言文件改为-jsonjsonc653)。

6.5.2 新增请求类型前缀校验，缺失或不匹配的 `Request-Type` 会在方法存在性、Route 注解和控制器构造前被拒绝（405），显式 URL 与内部调用均适用。应用仅在异常的 `allowedMethods` 非空时输出 `Allow`；从 6.5.1 升级也需检查带类型前缀的入口及异常处理，见 [6.5 迁移指南](migration-6.5.md#请求类型前缀校验652)。

本目录的 Markdown 文档根据当前 PHP 源码整理。[API 参考](index.html)由当前源码生成；运行 `composer install` 并按[插件指南](plugins.md#按需安装依赖)安装验证码插件依赖后，可运行 `make -C docs` 重新生成完整 API。

官网：[https://slightphp.com/](https://slightphp.com/)。

- [更新记录](../CHANGELOG.md)
- [6.5 升级与迁移说明](migration-6.5.md)
- [核心异常与迁移](exceptions.md)
- [6.4 升级与迁移说明](migration-6.4.md)
- [6.3 升级与迁移说明](migration-6.3.md)
- [6.2 升级说明](migration-6.2.md)
- [从 5.x 迁移到 6.x](migration-6.0.md)
- [快速开始与项目结构](getting-started.md)
- [核心调度、路由与 API](framework.md)
- [自定义 URL 路由、PHP / JSON 配置与示例](routes.md)
- [Request、Response 与控制器新签名](http.md)
- [框架官网与维护说明](website.md)
- [完整示例与交互说明](samples.md)
- [插件与扩展](plugins.md)
- [Tpl 模板与模块组合手册](templates.md)
- [部署、排错与安全边界](deployment.md)

SlightPHP 是轻量级调度框架：核心负责从路径加载控制器并调用方法，应用负责响应输出、状态码、权限、校验和数据管理。插件独立使用，并不由核心自动启用。

6.5.1 移除 `SlightPHP\Json` 插件，改用原生 JSON 函数；Route 文件配置改为 JSON/JSONC 数组；插件依赖改为按需安装。6.5.0 新增 Route 方法注解，核心失败统一抛出结构化异常，并完善自定义 URL 路由与请求内元数据缓存。从 6.4.x 升级也需调整调度失败处理，具体见 [6.5 迁移指南](migration-6.5.md)。

6.4.0 将 Tpl 模板变量及 `assign()`、`fetch()` 改为实例级；已有静态调用和直接变量表访问需迁移，具体步骤见 [6.4 迁移指南](migration-6.4.md)。

6.3.0 将控制器的 `$inPath` 数组参数替换为 Request，并向入口方法传入 Response；已有 6.2 应用也需要检查签名。路径数组改从 `$request->route` 读取，具体步骤见 [6.3 迁移指南](migration-6.3.md)。
