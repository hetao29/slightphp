# SlightPHP 6.3.0 框架使用文档

本目录的 Markdown 文档根据当前 PHP 源码整理。[API 参考](index.html)由当前源码生成；安装 Composer 依赖后可运行 `make -C docs` 重新生成。

- [更新记录](../CHANGELOG.md)
- [6.3 升级与迁移说明](migration-6.3.md)
- [6.2 升级说明](migration-6.2.md)
- [从 5.x 迁移到 6.x](migration-6.0.md)
- [快速开始与项目结构](getting-started.md)
- [核心调度、路由与 API](framework.md)
- [Request、Response 与控制器新签名](http.md)
- [框架官网与维护说明](website.md)
- [完整示例与交互说明](samples.md)
- [插件与扩展](plugins.md)
- [部署、排错与安全边界](deployment.md)

SlightPHP 是轻量级调度框架：核心负责从路径加载控制器并调用方法，应用负责响应输出、状态码、权限、校验和数据管理。插件独立使用，并不由核心自动启用。

6.3.0 将控制器的 `$inPath` 数组参数替换为 Request，并向入口方法传入 Response；已有 6.2 应用也需要检查签名。路径数组改从 `$request->route` 读取，具体步骤见 [6.3 迁移指南](migration-6.3.md)。
