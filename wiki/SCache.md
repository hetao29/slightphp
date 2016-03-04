# 主要特性 #

  * 多memcache server支持
  * 分布算法采用consistent hashing算法加weight(权重值)组合设置
  * memcache依赖key的设置(同时支持，完全匹配与部分匹配模式)
  * 新的conf配置文件格式

# 使用方法 #

```
//设置memcache服务器
$cache = new SCache;
$cache->addServer("10.10.221.12",10006);
//增加
$cache->set("key1","value1");
//增加，60秒后过期
$cache->set("key1","value1",60);
//读取
$cache->get("key1");
//删除
$cache->del("key1");
```

增加memcache服务器，可以是多个服务器。
```
$cache->addServer("10.10.221.12",10006);
$cache->addServer("10.10.221.12",10007);
$cache->addServer("10.10.221.12",10008);
```
增加几个key和value
```
$cache->set("key1","value1",10000);
$cache->set("key2","value2",10000);
$cache->set("key3","value3",10000);
$cache->set("key4","value4",10000);
$cache->set("key5","value5",10000);
$cache->set("key6","value6",10000);
$cache->set("key7","value7",10000);
```
获取几个简单key
```
$cache->get("key1");
$cache->get("key2");

//批量获取多个
$cache->get(array("key1","key2"));
```
删除key3的内容
```
$cache->del("key3");

//批量删除多个
$cache->del(array("key1","key2"));
```
获取key1,的内容，依赖key分别为key2,key3
```
//这里能取到内容value1
$cache->get("key1","key2");

//这里因为key3已经被删除，所以key1返回为空
$cache->get("key1","key3");
```

获取key1的内容，以来key都key2和key3
```
//用mode1完全依赖模式
//由于key3没有内容，所以key1返回为空
$cache->setMode(1);
$cache->get("key1",array("key2","key3"));

//用mode2部分依赖模式
//因为key2有内容，所以key1返回内容value1
SCache::setMode(2);
SCache::get("key1",array("key2","key3"));
```

# 什么是依赖Key #
比如，一个人生日等基本信息存在user里，这个人的对应的星座，年龄，属相等信息存在user\_profile里，如果当这个人的生日修改后，他的user\_profile很明显如果不更新，就会出现错误。于是就是
```
//user_profile是依赖user的值的，于是如下：
$cache->get("user_profile","user");
```
当user被删除，或者更新时，user\_profile自动从memcached里过期了，于是达到了我们的目的。

# 关于mode #
mode现在有2种值，1，2.<br />
1.是完全匹配，列表模式必须用此模式
```
$cache->setMode(1);
```
所有依赖key必须有内容，并且$key->t 时间大于或等于所有$depKeys->t，才返回内容，否则为失败<br />
好处：完全依赖，内容能更准确<br />
坏处：如果依赖Key，一直不存在，有可能导致cache命中降低

2.是部分匹配，效率高
```
$cache->setMode(2);
```
只要$key有内容，如果$depKeys有内容且 $depKeys->t大于$key->t时，才失败，否则为成功<br />
好处：部分依赖，cache命中率高<br />
坏处：如果某些时候依赖Key只删除了，没有更新的话，会导致内容不太准确。


# 列表Key的解决办法 #
列表Key的问题，在用列表Key时，请把模式设置成完全匹配模式。
```
$cache->setMode(1);
```
这里要做的就是要先保存列表的key和depKeys，然后再取就OK了。

1.get时流程如下<br />
获取列表如P\_1的内容，这里包含key(P\_1),depKeys，然后再获取key,depKeys就OK了。<br />
2.set流程如下<br />
获取一组列表如1,2,3,4,5,6，然后生成P\_1的key(P\_1),depKeys（1，2，3，4，5，6），设置P\_1


# 配置文件 #
  * 配置文件模板样例
```conf

#配置一共有4个字段
#host 主机IP，或者主机名
#port 端口号
#weight 权重，整数，默认为1
#timeout 连接超时设置，默认为1
#default 是默认的Cache配置


#default
default{ host 127.0.0.1; port 10006; weight 40; timeout 1; }
default{ host 127.0.0.1; port 10006; weight 40; timeout 1; }


#video
video   {       host:10.10.221.12;      port:10006;     weight 5; timeout 3;}
video   {       host:10.10.221.12;      port:11211;     weight 5; timeout 3;}


#user
user   {       host:10.10.221.12;      port:10006;     weight 5; timeout 3;}

```
  * 实例

```
//设置配置文件地址
$cache = new SCache;
$cache->setConfigFile($filePath="cache.ini");
$cache->useConfig("video");

$cache->set("key1","value1",10000);
$cache->set("key2","value1",10000);
```


# 其它apc,file操作类 #

```

/**
 * 文件cache例子 File Cache Samples
 */
$cache = SCache::getCacheEngine($cacheengine="File");
if(!$cache){
    die("File cache engine not exists");
}
/**
 * 初始参数，这里的dir为必要参数，depth表示目录深度
 */
$cache->init(array("dir"=>SlightPHP::$appDir."../cache","depth"=>3));
/**
 * 设置
 */
var_dump($cache->set("name",new stdclass));
/**
 * 获取
 */
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
/**
 * 删除
 */
var_dump($cache->del("name"));

/**
 * APC Cache Samples
 */

$cache = SCache::getCacheEngine($cacheengine="APC");
if(!$cache){
    die("APC cache engine not exists");
}

var_dump($cache->set("name",new stdclass));
var_dump($cache->get("name2"));
var_dump($cache->get("name"));
var_dump($cache->del("name"));
```