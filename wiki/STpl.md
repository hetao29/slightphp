# 主要特性 #

  * 速度快，效率高
  * 接近原生PHP代码，学习成本低
  * 支持原生php模板代码，不过你需要设置STpl::$safe\_mode=false
  * 支持自定义方法(function)和自定义修改器(modifier)
  * 简单高效，耦合性低，可以很方便移植到其它项目里

# 与Smarty的比较 #
下面的比较在完全相同的环境下，同时使用了part这样的扩展方法，并且是一个相对全面的TPL的比较，数据是每秒响应请求数，值越大效果越好，下面的数据是10次运算后的平均值：

  * 无APC强制编译(force\_compile)比较
|**STpl**|**Smarty2.6**|**Smarty3**|
|:-------|:------------|:----------|
|104.70|55.60|8.26|

  * 无APC普通运算比较
|**STpl**|**Smarty2.6**|**Smarty3**|
|:-------|:------------|:----------|
|305.45|198.99|132.25|

  * 有APC强制编译(force\_compile)比较
|**STpl**|**Smarty2.6**|**Smarty3**|
|:-------|:------------|:----------|
|141.55|82.07|8.97|

  * 有APC普通运算比较
|**STpl**|**Smarty2.6**|**Smarty3**|
|:-------|:------------|:----------|
|913.34|684.53|592.46|


# 使用方法 #

普通类方法：

```
STpl::$force_compile=true;
STpl::$safe_mode=false;
STpl::$template_dir=".";
STpl::assign("key","value");
echo STpl::fetch("test.tpl");
```

扩展类方法：

```
class index_main extends STpl{
 public function pageEntry($inPath){
  return $this->fetch("header.tpl");
 }
}
```

# 模板参数 #

  * boolean $force\_compile = false
设置是否强制编译，默认false

  * boolean $safe\_mode = true
设置是否在模板里支持php代码，默认true

  * string $left\_delimiter = "{"
设置左边的分割符

  * string $right\_delimiter = "}"
设置右边的分割符

  * string $template\_dir = "template\_dir"
设置模板路径

  * string $compile\_dir= "templates\_c"
设置编译后的路径，请确保有写权限

# 模板语法 #

  * 注释代码
```
默认是{*  与 *}注释，其实就是delimiter加*号就注释了
```

  * 显示变量

```
{$key}
{$key|tostring}
{$key_array['key']}
{$key_object->key}
```

  * 模板里变量赋值

```
{$key="name"}
{$key}
```

  * 判断

```

{if($set)}

{/if}

{if($set)}
{elseif}
{/if}

{if $a=="b"}
{elseif $a=="c"}
{/if}
```

  * 循环
for循环

```
{for($i=0;$i<=32;$i++)}
{$i}
{/for}

{foreach($v as $k=>$x)}
{$k}:{$v}
{/foreach}

```

  * 自定义修改器
你可以定自己的修改器(modifier)，加入到自己的包含文件里就可以了，加命令方法如下：

```
//默认第一个参数是前面的值
function tpl_modifier_xxxx($param1,$param2,$param3...)
```

```
{$key|tr}
{$key|default:"xx"}
{$key|xxxx:$param2:$param3}
```


  * 自定义插件
你可以定自己的插件(function)，加入到自己的包含文件里就可以了，加命令方法如下：

```
function tpl_function_xxxx($param1,$param2,$param3...)
```

```
{xxxx "param1" "param2"}
{part "/index.main.entry"}
```

# 系统插件 #
  * part 插件
这个插件能帮你包含另一个路径里的代码,这个路径就是URL，如
{part "/index.main.header"}

  * include 插件
包含另一个tpl文件，这是相对于tpl本地文件来的。
{include "header.tpl"}

  * tostring 插件
这个插件，主要是显示string,array,object的内容

  * default 修改器

  * tr 修改器
这个主要是用来翻译的，详细请看demo

  * tostring 修改器
