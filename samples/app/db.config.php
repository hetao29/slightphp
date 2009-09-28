<?php exit;?>
;配置一共有5个字段，分别用","分割，用":"连接，如果有空格，空格不会被忽略

;host 主机IP，或者主机名
;user 用户名
;database 数据库名
;password 密码
;charset  数据库字符集
;数据库配置有2种，一种是主库(main)，一种是查询库(query)
;你可以配置多个，按示例文件那样，多的就在后面加数字开始


;default 是默认的数据库配置，如果没有找到你获取的zone，比如要在这个文件里找SDb::getConfig("friends")，
;由于friends没有配置，就读取default里的
[default]
main    =   host:localhost1,user:root,database:db1,password:pw2,charset:utf8
main1   =   host:localhost2,user:root,database:db2,password:pw2,charset:latin1
query3  =   host:localhost3,user:root,database:db3,password:pw2

;这里配置了2个相同的main，后面的main会覆盖前面的，实际上只会获取到第二条
[user]
;这个会被忽略
main    =   host:localhost4,user:root,database:db1,password:pw2
;这个会覆盖上面的配置
main    =   host:localhost7,user:root,database:db1,password:pw2
main1   =   host:localhost5,user:root,database:db2,password:pw2
query3  =   host:localhost6,user:root,database:db3,password:pw2
;这里只配置了main，如果来获取query的话，会返回main的数据
;SDb::getConfig("blog","query")会和
;SDb::getConfig("blog"), SDb::getConfig("blog","main")
;一样的内容
[blog]
main    =   host:localhost4,user:root,database:db1,password:pw2
