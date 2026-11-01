> 本页为 5.x 及更早版本的历史资料，代码和配置不适用于 6.0。请参阅 [6.0 迁移指南](../docs/migration-6.0.md)及[当前文档](../docs/README.md)。

# zoneAlias 功能说明 #


主要是对zone的名字加上别外，这样可以用更短或者更方便直观的方式URL，或者隐藏自己的类名啥的。
如原来是

http://myhost/index.php/myzone

加上SlightPHP::setZoneAlias("myzone","z")就可以用这样的方式了

http://myhost/index.php/z

```
SlightPHP::setZoneAlias("zone","z");
```


# 运行环境 #
  * apache 2.0 以上
  * php 5.0 以上
  * slightphp 153以上