# 模板使用

SlightPHP 6.0 请参阅[当前使用文档](../docs/plugins.md#tpl)及[迁移指南](../CHANGELOG.md#6x-升级与迁移指南)。

模板语法、上下文转义及 include/part 组合请参阅 [Tpl 手册](../docs/templates.md)。默认 `{` / `}` 分隔符下，`{* ... *}` 支持单行、多行模板注释，编译时移除且不执行内部标签；不能嵌套，未闭合会报错。literal 中保留注释原文的写法见[模板注释](../docs/templates.md#模板注释)。
