<?php
// Module content, loaded only for the pages that use it.
return [
            ['id' => 'dispatch', 'icon' => '↗', 'title' => '清晰的路径调度', 'text' => 'URL 映射到目录、控制器与入口方法。支持多级目录，从最深候选逐级查找，让请求的执行过程直观可见。', 'note' => '路径解析 → 文件加载 → 方法调用'],
            ['id' => 'modes', 'icon' => '⌘', 'title' => '两种控制器组织方式', 'text' => '支持传统 PAGE 文件结构和 NAMESPACE 命名空间结构，可设置模式优先级和控制器后缀。', 'note' => 'PAGE / NAMESPACE'],
            ['id' => 'plugins', 'icon' => '◎', 'title' => '核心与插件分离', 'text' => '核心专注调度，模板、配置、数据库、日志和 HTTP 等能力按需加载，保持应用结构清晰。', 'note' => '按需使用，独立组合'],
            ['id' => 'cli', 'icon' => '›_', 'title' => '支持显式路径调用', 'text' => '通过 SlightPHP::run($path) 在 CLI 或内部调用中执行控制器。非空路径使用 part 语义，不更新顶层路由状态。', 'note' => 'CLI / 内部调用'],
            ['id' => 'config', 'icon' => '≡', 'title' => '可配置的调度规则', 'text' => '自定义默认 zone、page、entry、分隔符、命名空间和允许的方法前缀。短 URL 可交给 SRoute 映射。', 'note' => '默认值与目录结构由您定义'],
            ['id' => 'boundary', 'icon' => '◇', 'title' => '明确的应用边界', 'text' => '加载文件时验证 appDir 目录边界，仅调用可公开访问的方法。响应输出、HTTP 状态、权限和业务校验由应用负责。', 'note' => '轻量核心，显式控制'],
        ];
