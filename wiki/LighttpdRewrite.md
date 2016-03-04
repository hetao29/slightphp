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