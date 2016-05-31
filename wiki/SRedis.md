#SRedis插件
依赖phpredis扩展 phpredis https://github.com/phpredis/phpredis

# 安装 #
```
sudo apt-get install php5-redis
```

# 主要特性 #


# 使用方法 #

```
//初始化对像，也可以直接用静态方法
$s = new SRedis;

//设置配置文件地址
$s->setConfigFile(ROOT_CONFIG. "/redis.conf");
//或者
SRedis::setConfigFile(ROOT_CONFIG. "/redis.conf");

//设置配置文件节点
$s->useConfig("default");
SRedis::useConfig("default");

//掉用Redis类的方法，详细 https://github.com/phpredis/phpredis/tree/master
//用静态方法也是可以的

var_dump($s->set("D","ww"));
var_dump($s->get("D"));
var_dump($s->zAdd('k1', 0, 'val0'));
var_dump($s->zAdd('k1', 1, 'val1'));


```

# 配置文件 #
  * 配置文件模板样例
```conf
#节点名，默认采用default
default{
    #主机和IP，可以设置1个到多个，采用一致性算法
    host 127.0.0.1:6379;
    host 127.0.0.1:6379;
    #参考https://github.com/phpredis/phpredis/blob/master/arrays.markdown#readme
    options{
    }
}


```
