# 主要特性 #

  * 支持并发请求,速度效率高
  * 支持多节点配置
  * 分布算法采用consistent hashing算法加weight(权重值)组合设置


# 示例代码(配置文件) #

```
[photo]
main1=host:"192.168.1.101",path:"query.php",weight:40,timeout:1
main2=host:"192.168.1.102",path:"query.php",weight:40,timeout:1
main3=host:"192.168.1.103",path:"query.php",weight:40,timeout:1
[user]
main1=host:"192.168.2.101",path:"query.php",weight:40,timeout:1
main2=host:"192.168.2.102",path:"query.php",weight:40,timeout:1
main3=host:"192.168.2.103",path:"query.php",weight:40,timeout:1
```

# 示例代码(PHP) #

```
SRestClient::setConfigFile(ROOT_CONFIG."/rest.ini");

$rest->addRequest($zone="ipquery",$parameters=array("ip"=>"61.139.2.61"),$key="key1",$method="POST");
$rest->addRequest($zone="ipquery",$parameters=array("ip"=>"61.139.2.62"),$key="key2",$method="DELETE");
$rest->addRequest($zone="ipquery",$parameters=array("ip"=>"61.139.2.63"),$key="key3",$method="GET");
$r = $rest->request();
print_r($r);

```

```
Array
(
    [key1] => Array
        (
            [error] => 
            [errno] => 
            [result] => OK
        )

    [key2] => Array
        (
            [error] => 
            [errno] => 
            [result] => OK
        )

    [key3] => Array
        (
            [error] => 
            [errno] => 
            [result] => OK
        )

)
```