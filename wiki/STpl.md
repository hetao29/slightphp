# 模板使用

SlightPHP 6.0 请参阅[当前使用文档](../docs/plugins.md#tpl)及[迁移指南](../CHANGELOG.md#6x-升级与迁移指南)。

模板语法、上下文转义及 include/part 组合请参阅 [Tpl 手册](../docs/templates.md)。默认 `{` / `}` 分隔符下，`{* ... *}` 支持单行、多行模板注释，编译时移除且不执行内部标签；不能嵌套，未闭合会报错。literal 中保留注释原文的写法见[模板注释](../docs/templates.md#模板注释)。

Modifier 支持自定义 PHP 函数、系统函数（如 `trim`、`strtoupper`、`substr`）及多级串联。当前值作为第一个参数，冒号传递后续参数，多个 Modifier 从左到右执行；自定义函数在渲染前加载即可，无需注册。定义、加载、参数顺序与混合串联的完整说明见 [Modifier 手册](../docs/templates.md#modifier自定义方法系统方法与多级串联)，可运行示例位于 `/lab/template/entry` 第 5 节及 `/widgets/template/context`。
