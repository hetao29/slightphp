> 本页为 5.x 及更早版本的历史资料，代码和配置不适用于 6.0。请参阅 [6.0 迁移指南](../docs/migration-6.0.md)及[当前文档](../docs/README.md)。

# Lighttpd Rewrite 例子 #

```
#增加重定向模块
server.modules += ("mod_rewrite")
fastcgi.server = ( ".php" =>
        ((
          "host" => "127.0.0.1",
          "port" => 9000
         ))
        )
url.rewrite-once  = (
        "^/assets/(.*)$"                       => "/assets/$1",
        "^/(.*)$"                              => "/index.php/$1",
        )
```

# 运行环境 #
  * slightphp 254以上
  * /var/www/slightphp/samples,修改成实际路径