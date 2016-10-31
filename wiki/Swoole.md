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
