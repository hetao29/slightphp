# SlightPHP 高效的PHP敏捷开发框架

### v3.1更新如下
  - 更新pecl，更好支持php7，php5
  - 优化SDb,SRedis，增加错误连接重置，可以更好的支持WorkerMan

### v3.0支持composer,同时需要php5.3及以上
  - 安装方法一 ，执行下面的命令
```bash
composer require "hetao29/slightphp:dev-master"
```

  - 安装方法二 ，新增 composer.json，然后 composer install就可以了
```json
{
    "require": {
        "hetao29/slightphp": "dev-master"
    }
}
```

  - 优化，默认我们已经做了加载优化，你也可以手动执行一次
```bash
composer dump-autoload --optimize
```

  - v2.0升级方法 ，加载SlightPHP.php改成如下方法就可以了，自定义slightphp_path的路径
```php
<?php
//require_once($slightphp_path."/SlightPHP.php"); #不用这个方法了
require_once($slightphp_path."/vendor/autoload.php");
```

  - v2.0版本支持php4,php5,php7等主要版本
  
### pecl 模块支持php7
  - 最新更新，支持php7模块
  - 支持composer
  - 更简单方便的命令行执行模式
  
### 主要特点：
  - 独有的"框架"与"plugins"分离方式，与现在主流框架完全不同，把核心框架与其它功能独立分开，灵活性大，耦合度小，很方便移植 
  - 支持composer(3.0及以上)
  - 支持php7(3.0及以上)
  - 支持pecl的模块加载 请编译pecl目录下的源码，或者直接编译到php里
  - 支持命令行(cli)模式 只需要在执行代码里时定义 SlightPHP::setPathInfo($path_info) 的值，可以用于WorkerMan等PHP 服务端项目
  - 框架本身核心代码非常小
  - 框架支持nginx,lighttpd,apache,iis等web服务器
  - 插件SDb 支持mysql,mysqli,pdo,mssql,oracle等主流数据库，同时更支持数据库读写库分离，特适合大流量网站
  - 插件SRoute 支持各种简洁路由支持，精简URL
  - 插件STpl模板类，高效与灵活，比Smarty轻量级不少！
  - 插件SCache(memcache)采用consistent hashing算法，支持分布式服务与依赖KEY，同时也支持file,apc缓存
  - 其它更多灵活可定制的插件，请查看wiki或者samples下的例子

### Hello, world!
#### 第一步
在网站根目录下，建立index.php

```php
<?php
//加载方式1，直接加载框架
require_once("SlightPHP.php");

//加载方式2，使用扩展，或者直接编译进PHP也可以
//dl("slightphp.so");
//或者在php.ini里增加（如果不支持dl的话）
//extension = slightphp.so

//加载方式3，使用composer的autoload
//require_once("vendor/autoload.php");

SlightPHP::run();
?>
```
#### 第二步
第二步 请在index.php所在目录下新建zone目录，在zone目录下新建page.page.php
```php
<?php 
class zone_page{ 
    function PageEntry($inPath){
        echo "Hello, world!";
    } 
} 
?>
```
#### 第三步
请在你的地址栏里访问index.php，如
```html
http://localhost/index.php
```


### 基本概念

```sh
zone 映射为一个目录名，默认为"zone"
page 映射为一个文件名，以.page.php为扩展名，默认为"page"
entry 映射为方法名，以Page开头的方法名，默认为"entry"
appDir 应用程序目录，默认为"."，就是当前目录
splitFlag 分割符，默认为"/"
inPath entry入口参数，数组，下面会有更详细的介绍
```

#### 地址解析
这个地址 http://localhost/index.php 实际上和 http://localhost/index.php/zone/page/entry 一样
会执行你的$appDir/zone/page.page.php下的pageEntry方法


你可以改变其默认规则
```php
<?php
SlightPHP::setDefaultZone("user");
SlightPHP::setDefaultPage("profile");
SlightPHP::setDefaultEntry("update");
?>
```
当访问 http://localhost/index.php 时，就会执行
$appDir/user/profile.page.php里的pageUpdate方法，其实就是 
http://localhost/index.php/user/profile/update 
这个URL
#### 高级地址解析-别名
如果加了这代码
```php
SlightPHP::setZoneAlias("user","u");
```
你访问这个地址
http://localhost/index.php/u/profile/update
和http://localhost/index.php/user/profile/update
会是一样的效果
它的作用是增加user一个别名u

#### 分隔符(splitFlag)
zone,page,entry的分隔默认是用/来分的，你可以改成自己想要的，如
```php
SlightPHP::setSplitFlag(".")
```
就可以这样访问了
http://localhost/index.php/user.profile.update
或者更好看，加上.html
http://localhost/index.php/user.profile.update.html
这样的方式你也可以这样,用多个分割符
```php
SlightPHP::setSplitFlag("-.")
```
http://localhost/index.php/user-profile-update.html
和上面的是一样
#### Apache Rewrite
在你的.htaccess里或者apache的配置文件里加下类似代码
```conf
RewriteEngine   on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```
你就可以直接这样访问了
http://localhost/user/profile/update
如果你还加上了
```php
SlightPHP::setSplitFlag(".");
```
那么就可以用这样的方式 http://localhost/user.profile.update
#### 短路由
如你要直接这样，更短的地址
http://localhost/profile/update
请在apache的配置文件里加上类似这样的代码
```php
RewriteRule   ^(/profile/.*)$ /index.php/user/profile/update/$1 [E=PATH_INFO:$1,L]
```
#### 关于$inPath

  - $inPath[0] 就是当前的 zone的名字
  - $inPath[1] 就是当前的 page的名字
  - $inPath[2] 就是当前的 entry的名字
  - $inPath[...] 超过以前的就是后面更多的参数，如html

如 http://localhost/index.php/user/profile/update/other1/other2/... inPath是这样的
$inPath=array("user","profile","update","other1","other2","...")
#### appDir，程序目录设置
你可以自定义你的程序目录
```php
SlightPHP::setAppDir("/home/www/myAppdir");
```
如http://localhost/index.php/user/profile/update

就会执行

/home/www/myAppdir/user/profile.page.php下的pageUpdate方法

建议你的appDir目录不要让外部访问到

#### 插件导航 

文档地址：https://github.com/hetao29/slightphp/tree/master/wiki  
  
STpl 模板插件 https://github.com/hetao29/slightphp/blob/master/wiki/STpl.md  
SDb 数据库插件 https://github.com/hetao29/slightphp/blob/master/wiki/SDb.md  
  
SCache 缓存Memcache,File,Apc插件 https://github.com/hetao29/slightphp/blob/master/wiki/SCache.md  
SRedis 缓存Redis插件 https://github.com/hetao29/slightphp/blob/master/wiki/SRedis.md  
SConfig 配置文件插件 https://github.com/hetao29/slightphp/blob/master/wiki/SConfig.md  
SRoute 路由插件 https://github.com/hetao29/slightphp/blob/master/wiki/SRoute.md  
SError 错误插件 https://github.com/hetao29/slightphp/blob/master/wiki/SError.md  
...  
 
更多插件功能请看samples下的示例  


#### Nginx配置
```php
location / {
	root   /var/www/slightphp/samples/www;
	index  index.php;
	if (!-e $request_filename){
		rewrite ^/(.+?)$ /index.php last;
	}
}
location ~ \.php$ {
	fastcgi_pass   127.0.0.1:9000;
	fastcgi_index  index.php;
	fastcgi_param  SCRIPT_FILENAME  /var/www/slightphp/samples/www$fastcgi_script_name;
	include        fastcgi_params;
}
```

### Version
2.0.1 beta

**Free Software, Hell Yeah!**

