# 错误信息 #

No input file specified.

# 请修改成下面设置 #

change .htaccess file:
```
#RewriteRule   ^(.*)$ index.php/$1 [L]
RewriteRule   .$ index.php/$1 [L]
```