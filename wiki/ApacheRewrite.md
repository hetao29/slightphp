# Apache Rewrite 例子 #


请在apache的配置文件中加入如下的代码：

```
RewriteEngine   on
RewriteCond   %{THE_REQUEST} !^(.*)/assets(.*)$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

这样就可以这样直接访问了：

http://myhost/main/default/test/a.html

如果加上了如下的代码

```
SlightPHP::setSplitFlag("-_");
//or
SlightPHP::setSplitFlag("-_.");
```

就是这样的格式也可以：

http://myhost/main-default-test-a.html

http://myhost/main.default.test.a.html


如何支持短格式的路由，如

http://myhost/show/id

这样的方式，


请用下面的重定向规则，main/show/video改成你自己的方法，就可以了：
```
RewriteRule   ^(/show/.*)$ /index.php/main/show/video/$1 [E=PATH_INFO:$1,L]
```

# 运行环境 #
  * apache 2.0 以上
  * php 5.0 以上
  * slightphp 130以上