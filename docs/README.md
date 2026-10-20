# SlightPHP 框架使用文档

本目录的 Markdown 文档根据当前 PHP 源码整理。原有 `index.html` 等文件是历史生成的 API 文档，可能与当前实现不一致；遇到差异以源码及本文档为准。

- [快速开始与项目结构](getting-started.md)
- [核心调度、路由与 API](framework.md)
- [框架官网与维护说明](website.md)
- [完整示例与交互说明](samples.md)
- [插件与扩展](plugins.md)
- [部署、排错与安全边界](deployment.md)

SlightPHP 是轻量级调度框架：核心负责从路径加载控制器并调用方法，应用负责响应输出、状态码、权限、校验和数据管理。插件独立使用，并不由核心自动启用。
