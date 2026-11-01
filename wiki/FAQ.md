> 本页为 5.x 及更早版本的历史资料，代码和配置不适用于 6.0。请参阅 [6.0 迁移指南](../docs/migration-6.0.md)及[当前文档](../docs/README.md)。

# 错误信息 #

No input file specified.

# 请修改成下面设置 #

change .htaccess file:
```
#RewriteRule   ^(.*)$ index.php/$1 [L]
RewriteRule   .$ index.php/$1 [L]
```