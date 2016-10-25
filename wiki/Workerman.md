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
