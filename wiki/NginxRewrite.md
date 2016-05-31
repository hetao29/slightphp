# Nginx Rewrite 例子 #

```
location / {
	root   /var/www/slightphp/samples/www;
	index  index.php;
	if (!-e $request_filename){
		rewrite ^/(.+?)$ /index.php last;
	}
}
location ~ \.php$ {
	fastcgi_pass   127.0.0.1:9000;
	fastcgi_index  index.php;
	fastcgi_param  SCRIPT_FILENAME  /var/www/slightphp/samples/www$fastcgi_script_name;
	include        fastcgi_params;
}
```

# 运行环境 #
  * slightphp 254以上
  * /var/www/slightphp/samples,修改成实际路径