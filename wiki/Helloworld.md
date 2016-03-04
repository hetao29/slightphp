# Hello, world! #

第一步：index.php文件内容
```
<?php
require_once("SlightPHP.php");
SlightPHP::run();
?>
```
第二步：请在index.php所在目录下新建zone目录，在zone目录下新建page.page.php，源代码如下：
```
<?php 
class zone_page{ 
    function PageEntry($inPath){
        echo "Hello, world!";
    } 
} 
?>
```
第三步：正常访问你的index.php

# 运行环境 #
  * apache 2.0 以上
  * php 5.0 以上
  * slightphp 130以上