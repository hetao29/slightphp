> 本页为 5.x 及更早版本的历史资料，代码和配置不适用于 6.0。请参阅 [6.0 迁移指南](../docs/migration-6.0.md)及[当前文档](../docs/README.md)。

# Swoole 和 Slightphp 结合#


```php
$http = new swoole_http_server("0.0.0.0", 2346);
$http->on('request', function ($request, $response) {
	if(($r=SlightPHP::run($request->server['request_uri']))===false){
		//
	}elseif(is_object($r) || is_array($r)){
		$response->end(SJson::encode($r));
	}else{
		$response->end($r);
	}
});

$http->start();
```
