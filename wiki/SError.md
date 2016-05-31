#SError插件
自动记录错误的控件，只需要在 index.php或者入口方法里加上就可以了

# 使用方法 #

```
//设置是不是终端显示
SError::$CONSOLE = true;
//设置是不是需要记录到文件
SError::$LOG = false;
//设置记录的文件地址
SError::$LOGFILE="/tmp/tmp_serror.log";

```

