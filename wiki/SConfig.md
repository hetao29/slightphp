# 使用方法 #

```
#解析全部文件
SConfig::parse("/etc/nginx/nginx.conf");

#获取其中某一个节点数据（SlightPHP框架使用）
SConfig::getConfig("/etc/nginx/nginx.conf",$zone);

```

```
/*定义如下*/
/**
  * @param string $configFile
  * @return mixed $result
  */
public static function parse($configFile);

```

# 配置文件 #

```
说明:
1.每行以;(分号结尾)
2.变量与值的定义可以用空格或者冒号分开
3.相同的变量(数组定义),定义多次就行了
4.#号为注释
```

```
user www-data;
worker_processes 40;
pid /var/run/nginx.pid;
events {
	worker_connections 768;
	# multi_accept on;
	use epoll;
}

http {
	limit_conn_zone $binary_remote_addr zone=addr:10m;

	##
	# Basic Settings
	##

	sendfile on;
	tcp_nopush on;
	tcp_nodelay on;
	keepalive_timeout 65;
	types_hash_max_size 2048;
	# server_tokens off;

	# server_names_hash_bucket_size 64;
	# server_name_in_redirect off;

	include /etc/nginx/mime.types;
	default_type application/octet-stream;

	##
	# Logging Settings
	##

	access_log /var/log/nginx/access.log;
	error_log /var/log/nginx/error.log;

	##
	# Gzip Settings
	##

	gzip on;
	gzip_disable "msie6";

	proxy_cache_path /tmp/cache1 levels=1:2 keys_zone=news1:100m inactive=2h max_size=10g;
	proxy_cache_path /tmp/cache2 levels=1:2 keys_zone=news2:100m inactive=2h max_size=10g;
	include /etc/nginx/conf.d/*.conf;
	include /etc/nginx/sites-enabled/*;
}

```

# 结果值 #

```
$conf->parse($file);
```

```
stdClass Object
(
    [user] => www-data
    [worker_processes] => 40
    [pid] => /var/run/nginx.pid
    [events] => stdClass Object
        (
            [worker_connections] => 768
            [use] => epoll
        )

    [http] => stdClass Object
        (
            [limit_conn_zone] => $binary_remote_addr zone=addr:10m
            [sendfile] => on
            [tcp_nopush] => on
            [tcp_nodelay] => on
            [keepalive_timeout] => 65
            [types_hash_max_size] => 2048
            [include] => Array
                (
                    [0] => /etc/nginx/mime.types
                    [1] => /etc/nginx/conf.d/*.conf
                    [2] => /etc/nginx/sites-enabled/*
                )

            [default_type] => application/octet-stream
            [access_log] => /var/log/nginx/access.log
            [error_log] => /var/log/nginx/error.log
            [gzip] => on
            [gzip_disable] => msie6
            [proxy_cache_path] => Array
                (
                    [0] => /tmp/cache1 levels=1:2 keys_zone=news1:100m inactive=2h max_size=10g
                    [1] => /tmp/cache2 levels=1:2 keys_zone=news2:100m inactive=2h max_size=10g
                )

        )

)

```