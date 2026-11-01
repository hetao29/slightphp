> 本页为 5.x 及更早版本的历史资料，代码和配置不适用于 6.0。请参阅 [6.0 迁移指南](../docs/migration-6.0.md)及[当前文档](../docs/README.md)。

# Workerman 和 Slightphp 结合#


```php
require_once(ROOT_LIBS."/Workerman-master/Autoloader.php");

use Workerman\Worker;

$http_worker = new Worker("http://0.0.0.0:2345");
$http_worker->onMessage = function($connection, $data){
	if(($r=SlightPHP::run())===false){
		//
	}elseif(is_object($r) || is_array($r)){
		$connection->send(SJson::encode($r));
	}else{
		$connection->send($r);
	}
	$connection->close();
};
Worker::runAll();
```
