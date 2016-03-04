# 简要说明 #

  * Route功能,可以实现自定义的url配置与隐藏
  * url检验功能
  * 支持多个配置文件与多次set

# 注意事项 #
  * 请在加载框架文件后调用SRoute
  * 请在SlightPHP::run()之前设置route选项

# 使用方法 #

```
//设置配置文件(你可以设置多个)
SRoute::setConfigFile("plugins/route1.ini");
SRoute::setConfigFile("plugins/route2.ini");
//或者单独设置
SRoute::set(
　　array(
　　　　"pattern"=>"bb_(.*).html"
　　)
);

if(($r=SlightPHP::run())===false){

}
```

# SRoute方法 #

  * static function setConfigFile($file)
设置配置文件地址

  * static function getConfigFile()
获取配置文件地址

  * static function set(array $route)
单独设置$route,主要包括,
```
$route = array(
    "pattern"=>"",
    "zone"=>"",
    "page"=>"",
    "entry"=>"",
    //与严格的限制
```
详细设置方法见下一章

# 路由语法 #
```
[bbs]
pattern = "bbs_:id.html"
zone="bbs"
page="main"
entry="show"
:id="\d+"

[user]
pattern = "user_(.*).html"
zone="user"
page="main"
entry="show"

```

# 路由示例1 #
  * 把http://yourdomain.com/userid_232_username_MyName.html 这样的地址，定向到(zone为"user",page为"main",entry为"show",其中userid的id必须为整数。

配置方法
```
;[user]这个为名称，可以随意设置，主要是用来区别不同的配置的
[user]
pattern = "userid_:id_username_:name.html"
zone="user"
page="main"
entry="show"
:id="\d+"
:name="\w+"
```

或者直接设置

```
//或者单独设置
SRoute::set(
        array(
                "pattern"=>"userid_:id_username_:name.html",
                "zone"=>"user",
                "entry"=>"show",
                ":id"=>"\d+", 
                ":name"=>"\w+",
        )
);

if(($r=SlightPHP::run())===false){
}
```

在pageShow方法里如何获取id值,$inPath[3](3.md)为第1个参数值，$inPath[4](4.md)为第2个，以此类推
```
class user_main{
 function pageShow($inPath){
   print_r($inPath);
   $zone = $inPath[0];
   $page = $inPath[1];
   $entry = $inPath[2];
   $id = $inPath[3];
   $name = $inPath[4];
 }
}
```


# 路由示例2 #
  * 把http://yourdomain.com/vXXXXXXXXX 这样的以v开头的地址，定向到(zone为"index",page为"main",entry为默认(entry),。

配置方法(或者单独设置)
```
[v]
pattern = "v(.*)"
zone="user"
page="main"
entry="show"
```