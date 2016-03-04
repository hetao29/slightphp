# 主要特性 #

  * 支持多种数据底层驱动，如mysql,mysqli,pdo系列
  * 支持读写，主从分离
  * 支持ORM，与外键查询与更新
  * 自动分页功能
  * 支持字段名映射
  * 支持string,array,object多种参数格式，灵活
  * 简单高效，耦合性低，可以很方便移植到其它项目里


# 数据库连接与初始化 #
```sql

--新建2张表

CREATE TABLE user (
id int(11) NOT NULL auto_increment,
name varchar(32) not null default "",
email varchar(100) not null default "",
password varchar(32) not null default "",
status tinyint(4) default 1,
PRIMARY KEY  (id),
unique key email(email),
unique key name(name)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE user_profile (
user_id int(11) NOT NULL,
address varchar(32) default "",
phone varchar(100) default "",
mobile varchar(32) default "",
PRIMARY KEY  (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

```

```
$db = new SDb();
$db->init(
  array(
 "host"=>"localhost",
 "user"=>"root",
 "database"=>"test",
 "password"=>"",
 "port"=>3306,
 "charset"=>"utf8",
 "engine"=>"mysql"
)
);
```
  * host 主机名
  * user 数据库用户名
  * database 数据库名
  * password 数据库密码
  * port 端口号，默认3306
  * charset 就是字符集，可以是utf8,gbk,gb2312等数据库支持的字符集
  * engine  是数据库驱动引擎，有这么多
（
"mysql","mysqli",
"pdo\_mysql","pdo\_sqlite","pdo\_cubrid",
"pdo\_dblib","pdo\_firebird","pdo\_ibm",
"pdo\_informix","pdo\_sqlsrv","pdo\_oci",
"pdo\_odbc","pdo\_pgsql","pdo\_4d"
），默认是用的"mysql"


# 数据插入 #
  * insert 方法

```
boolean|int insert($table,$item="",$isreplace=false,$isdelayed=false,$update=array());
```

|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$table   |string|array|object  |是|  |要插入的表，格式参照下面select里定义|
|$item    |string|array|object  |是|  |要插入的字段，格式参照下面select里定义|
|$isreplace| boolean |  | false | 碰到重复字段，是否用替换模式，用replace代替insert|
|$isdelayed | boolean |  |false |是否延迟插入|
|$update | string|array|object |  |  |和$item的格式一样，碰到重复时要更新字,格式参照下面select里定义|

  * 返回值 boolean|int
如果false，失败;如果是普通插入，返回lastInsertId,如果设置了$isreplace或者$update的值，请用!==false判断是否成功

  * 实例

```
//增加一个用户
$user_id = $db->insert("user",array("name"=>"lili","email"=>"lili@lili.com","password"=>"pjf(@EF"));
//增加一个用户，当重复时，替换(注意这种方案会更新其主键ID)
$user_id = $db->insert("user",array("name"=>"lili@lili.com","email"=>"lili@lili.com","password"=>"pjf(@EF_2"),true);
//增加一个用户，当重复时，更新status为2时间
$db->insert(
"user",array("name"=>"lili",
"email"=>"lili@lili.com","password"=>"pjf(@EF"),
false,false
,array("status"=>2)
);
```

# 数据查询 #

数据查询主要提供2种：

  * 是select，这是查询多条，你可以用setLimit()与setPage(),setCount()方法，分页查询(关于分页查询，请参看下面的章节)

```
DbObject|boolean select($table,$condition="",$item="",$groupby="",$orderby="",$leftjoin="");
```

|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$table   |string|array|object  |是|  |要插入的表，支持别名|
|$condition|string|array|object  |是|  |查询条件|
|$item    |string|array|object  |  | 所有字段 |检索字段，支持别名|
|$groupby|string|array|object  |  |  |GROUP BY|
|$orderby|string|array|object  |  |  |ORDER BY|
|$leftjoin|string|array|object  |  |  |LEFT JOIN|

$table 可以是array|string|object，如:

```
//字符串
$table="user";
//数组
$table=array("user","user_profile");
//加了别名的数组
$table=array("table_alias"=>"user");

//对像,加了别名的
$table=new stdclass;
$table->table_alias1="user";
$table->table_alias1="profile";
```

$conditon 检索条件，可以为3种格式：

```
//字符串
$condition="id=1";
//数组
$condition=array("id=1");
$condition=array("id>0","id<3");
$condition=array("id"=>1);
//对像
$condition=new stdclass;
$condition->id=1;
```

$item是要取回的字段名，默认是所有，可以设置返回的别名，可以为如下格式：

```
//字符串，当只需要返回一个或者全部时
$item="*";
$item="id";
//数组
$item=array("*");
$item=array("id","name");
//数组，加上别名，会用UserID，替换数据库里的真实字段id和name
$item=array("UserID"=>"id","UserName"=>"name");
//对像，用返回别名是，很有用，这个会用UserID代替id
$item=new stdclass;
$item->UserID = "id";
$item->UserName = "name";
```

$groupby 是要GROUP的要求，可以为如下格式:

```
//字符串,只需要一个时
$groupby = "id";
//多个时，请用数组或者对像
$groupby = array("id","name");
$groupby = new stdclass;
$groupby->g1 = "id";
$groupby->g2 = "name";
```

$orderby 是要排序的字段，可以为如下格式:

```
//字符串,当只需要一个字段，并按默认排序时
$orderby = "id";
//如果你的$item里设置了字段映射，你可以用映射后的字段名
$orderby ="UserID";
//如果有多个，并不按默认排序，请用数组
$orderby = array("id"=>"desc","name"=>"asc");
//对像
$orderby = new stdclass;
$orderby->id="desc";
$orderby->name="asc";
```

$leftjoin 是要LEFT JOIN的字段，可以为如下格式:

```
//字符串
$left="user_profile on user_profile.user_id = user.id";
//数组
$left=array("user_profile"=>"user_profile.user_id=user.id");
//对像
$left=new stdclass;
$left->user_profile="user_profile.user_id=user.id";
$db->select(array("user"),"","","","",$left);
```

返回值
如果为false,你可以打印出 $db->error()信息

```
$result = $db->select(array("user"),"","","","",$left);
if($result===false)｛
   print_r($db->error());
｝
```

  * 是selectOne,这是查询一条

```
array selectOne($table,$condition="",$item="",$groupby="",$orderby="",$leftjoin="");
```

  * 实例

```
//取一个用户记录
$user = $db->selectOne("user",array("user_id"=>1));
//取10个
$db->setLimit(10);
//取第2页
$db->setPage(2);
//算出总数
$db->setCount(true);
$users= $db->select("user",array("userid>30");
```

# 数据更新 #

  * update方法

```
boolean|int update($table,$condition="",$item="");
```


|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$table   |string|array|object  |是|  |与上面参数形式一样|
|$condition|string|array|object  |是|  |修改的条件，和上面的一样|
|$item    |string|array|object  |是|  |要修改的字段，和上面的定义一样|


  * 返回值，如果为false就失败了，否则返回更新的记录数

  * 实例

```
//把用户ID为1的用户，更新nickname为xxxx
$db->update("user",array("id"=>1),array("password"=>"xxxx"));
//多表联合更新
$db->update(array("user","profile") , array("user.id"=>1,"user.id=profile.userid"),array("profile.comment"=>"我的注释"));
```


# 删除 #

  * 定义

```
boolean|int delete($table,$condition="");
```


|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$table   |string|array|object  |是|  |与上面参数形式一样|
|$condition|string|array|object  |是|  |修改的条件，和上面的一样|

  * 返回值

如果为false就失败了，否则返回删除的记录数

# 执行原始SQL #

```
int|boolean|Array execute($sql);
```

  * $sql 原始SQL
  * 返回值，如果为false就失败了，错误请看$db->error()信息，如果是更新，删除之类，返回删除的记录数，如果是查询之类的，返回查询结果
  * 实例

```
print_r (  $db->execute("show databases") );
print_r (  $db->execute("select * from user") );
var_dump(  $db->execute("set names utf8") );
if(!  $db->execute("select * from userx") ){
        print_r($db->error());
};
```

# SDb数据库配置文件 #

  * 设置配置文件

```
void static funcion setConfigFile($file);
```


|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$file|string|是|  |文件全路径|


  * 获取配置

```
array static funcion getConfig($zone,$type="main")
```
|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$zone|string|是|  |zone|
|$type|string|否|  |main，query(主库，从库)|


  * 切换配置参数，提供在SDb类中，切换数据库配置的方法

```
void useConfig($zone,$type="main")
```
|**参数**|**类型**|**必须**|**默认**|**说明**|
|:---------|:---------|:---------|:---------|:---------|
|$zone|string|是|  |zone|
|$type|string|否|  |main，query(主库，从库)|


  * 配置文件模板样例

```
;配置一共有7个字段

;host 主机IP，或者主机名
;user 用户名
;database 数据库名
;password 密码
;port 端口号
;charset  数据库字符集
;engine 数据库驱动引擎，如mysql,mysqli等，详见上面
;数据库配置有2种，一种是主库(main)，一种是查询库(query)
;你可以配置多个，按示例文件那样

;default 是默认的数据库配置，如果没有找到你获取的zone，比如要在这个文件里找
SDb::getConfig("friends")，
;由于friends没有配置，就读取default里的

default{

        main{
                host localhost;
                user root;
                database test;
                password ;
                charset latin1;
        }
}
user{
        main{
                host localhost;
                user root;
                database test;
                password ;
                charset utf8;
        }
        query{
                host localhost;
                user root;
                database test;
                password ;
                charset latin1;
        }
        query{  
                host localhost;
                user root;
                database test;
                password ;
                charset gbk;
        }       
}               

```

  * 实例

```
//设置配置文件地址
SDb::setConfigFile($filePath="db.conf");
//多种获取方式
$config = SDb::getConfig("friends");//这里会返回[default]配置
$config = SDb::getConfig("blog");   //blog主库
$config = SDb::getConfig("blog","main"); //blog主库
$config = SDb::getConfig("blog","query"); //由于没有配置从库，所以返回主库
$db = new SDb;
//结合SDb，初始化数据库(主库）
$db->useConfig("user","main");
//结合SDb，初始化数据库(从库）
$db->useConfig("user","query");
```


# 其它技巧 #

  * 分页

```
//设置每页获取的大小
setLimit($limit);
//设置要获取的页数
setPage($page);
//设置是否要计算总数
setCount($count)
```

  * 调试与SQL显示

```
//只要在代码执行前加上
define("DEBUG",true);
//或者，如果结果为false，打印错误信息
if(($r = $db->select("user",array("id_xx"=>1)))===false){
        print_r($db->error());
}
/*
Array
(
    [code] => 1054
    [msg] => Unknown column 'id_xx' in 'where clause'
)
*/
```



# ORM 方法介绍 #

  * get方法

```
boolean|array get($foreign_info=false);
```

  1. $foreign\_info是否包含外键信息
  1. 返回值如果false，表明没有数据，你还可以通过对像本身去获取属性
  1. 实例

```
$config =   array(
                "host"=>"localhost",
                "user"=>"root",
                "password"=>"",
                "database"=>"test");
$user = new SDb("user",$config);
$user->id=1;
if($user->get()){
 echo $user->name;
}
```

  * getAll()方法,是get(true)的别名

```
boolean|array getAll()
```

  * reset方法，重置所有条件

```
void reset();
```

  * listALl()方法，读取一个列表

```
listAll($condition,$foreign_info=false)
```

  1. $condition，条件，参数形式参看上面的定义
  1. $foreign\_info 外键信息
  1. 实例

```
$user->listAll("id>0");
$user->listAll( array("id>0"),array("id<10"));;
```

  * setForeignKey设置关联外键，详细使用方法见下面

```
setForeignKey($keys = array());
```

  * set方法

```
boolean set();
```

  1. 返回值如果false，设置失败，否则返回插入/更新的主键ID
  1. 实例

```
$user = new SDb("user",$config);
$user->name="lili_3";
$user->email="lili_3@lili.com";
if($user->set()){

}

```


  * del方法

```
boolean del($foreign_info=false);
```

  1. $foreign\_info,是否删除外键信息
  1. 返回值如果false，设置失败，否则返回插入/更新的主键ID
  1. 实例

```
$user = new SDb("user",$config);
$user->name="lili_3";
//$user->id=1;或者用主键也可以
if($user->del()){

}

```


# ORM 外键操作 #
  * 外键的定义，为了更好的兼容所有的engine，我们使用的是配置方法，手工定义外键，格式如下：

```
//这个表明，字段id和 user_profile里的user_id是外键关系
$foreign_keys = array("id"=>" user_profile.user_id");
```
  * 外键的设置

```
$user = new SDb("user",$config);
$foreign_keys = array("id"=>" user_profile . user_id");
$user->setForeignKey($foreign_keys);
$user->id="1";
print_r($user->getAll());
print_r($user->get(true));
/*如果外键有值的话，应该会打印出如下信息：

Array
(
    [id] => 6
    [name] => lili_4
    [email] => lili_3@lili.com
    [password] => 
    [status] => 1
    [user_profile] => Array
        (
            [user_id] => 6
            [address] => a
            [phone] => b
            [mobile] => c
        )

)
*/
```
  * 如果你设置了外键的话，可以同步更新外键的信息，如

```
$user = new SDb("user",$config);
//设置外键信息
$foreign_keys = array("id"=>" user_profile . user_id");
$user->setForeignKey($foreign_keys);
$user->id="6";
$user->password="xx";
//设置外键内容
$user->user_profile->address="a2";
if($user->set()!==false){
 echo "SET OK";
};
```
  * 如果你自己觉得设置很麻烦，你可以封装你自己的类，如

```
class User extends SDb{
        function __construct(){
                $config =   array(
                        "host"=>"localhost",
                        "user"=>"root",
                        "password"=>"",
                        "database"=>"test",
                        "engine"=>"mysql");
                parent::__construct("user",$config);
                $foreign_keys = array("id"=>" user_profile . user_id");
                parent::setForeignKey($foreign_keys);
        }
}
```