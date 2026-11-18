# 部署与排错

## 网站入口与 URL

正式官网地址为 [https://slightphp.com/](https://slightphp.com/)。自行部署时使用自己的域名，站内导航和静态资源继续使用根路径链接。

部署示例站点时，将网站根目录设为 `samples/www`，并把不存在的文件路径转发到 `index.php`。部署完成后访问站点根路径 `/`；站内链接以 `/` 开头，不带域名或 `/index.php` 前缀。

入口优先从 `REQUEST_URI` 提取原始路径，剔除查询串后传给 `setPathInfo()`。不要用 `PATH_INFO ?? '/'` 作为唯一路径来源：URI 转发后 PATH_INFO 往往为空，会把内页误判为首页。只有 REQUEST_URI 缺失时，才回退使用 PATH_INFO。

Nginx 配置示意（替换根路径、域名和 FPM 地址）：

```nginx
server {
    listen 80;
    server_name slightphp.com;
    root /var/www/slightphp/samples/www;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
    location ~ \.php(?:/|$) { return 404; }
}
```

以上只展示本地 HTTP 入口转发；生产部署使用下方 HTTPS 配置。不要公开仓库根目录、配置、应用源码或模板编译目录。

## Cookie与会话安全

Response Cookie 默认使用 `Secure=true`、`HttpOnly=true`、`SameSite=Lax`。示例入口在 `session_start()` 前明确启用严格会话 ID、仅 Cookie 传递会话、关闭 URL 会话 ID，并为会话设置 `HttpOnly`、`SameSite=Lax`、路径 `/`、空 Domain。Secure 根据 Web 服务器提供的 `HTTPS` 变量设置，因此纯 HTTP 本地开发仍可使用会话；语言偏好 Cookie 复用相同策略。入口不直接采用客户端发送的 `Forwarded` 或 `X-Forwarded-Proto`。

生产站点可固定安全策略，在第一次 `session_start()` 前完成配置：

```php
require dirname(__DIR__) . '/vendor/autoload.php';

\SlightPHP\Response::setCookieDefaults();
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
] + \SlightPHP\Response::getCookieDefaults());
session_start();
```

需关闭 `session.auto_start`，且入口前不能已有其他代码启动会话。示例不会重新配置已经活动的会话；由宿主预先启动会话时，宿主负责同等安全配置。生产环境的会话存储位置及访问权限由部署配置管理；这里沿用示例的存储方式。安全设置的含义及调用顺序见 PHP 官方的[会话安全设置](https://www.php.net/manual/en/session.security.ini.php)和[会话 Cookie 参数](https://www.php.net/manual/en/function.session-set-cookie-params.php)。

### Nginx 直接终止 HTTPS

使用实际证书、域名、根路径和 FPM 地址替换示例。HTTP 只重定向到固定 HTTPS 域名，HTTPS 请求才进入 PHP：

```nginx
server {
    listen 80;
    server_name app.example.test;
    return 308 https://app.example.test$request_uri;
}
server {
    listen 443 ssl;
    server_name app.example.test;
    ssl_certificate /etc/nginx/tls/fullchain.pem;
    ssl_certificate_key /etc/nginx/tls/privkey.pem;
    root /var/www/slightphp/samples/www;
    index index.php;
    location / { try_files $uri $uri/ /index.php$is_args$args; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_param HTTPS on;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
    location ~ \.php(?:/|$) { return 404; }
}
```

如果发行版的 `fastcgi_params` 已定义 `HTTPS`，应统一修改原定义或使用专用参数文件，确保没有冲突的同名参数。[Nginx FastCGI 参数说明](https://nginx.org/en/docs/http/ngx_http_fastcgi_module.html#fastcgi_param)

### HTTPS 在反向代理处终止

例如可信代理位于 `10.0.0.10`，应用后端位于 `10.0.0.20:8080`。代理的 HTTP 入口同样只做 HTTPS 重定向；在代理的 HTTPS server 中转发：

```nginx
location / {
    proxy_pass http://10.0.0.20:8080;
    proxy_set_header Host app.example.test;
    proxy_set_header X-Forwarded-Proto https;
    proxy_set_header X-Forwarded-For $remote_addr;
}
```

后端监听私网地址，并通过防火墙和访问控制仅接受该可信代理。这个后端专门承载来自代理 HTTPS 入口的请求，所以明确传递 `HTTPS on`，不使用客户端可伪造的请求头判断：

```nginx
server {
    listen 10.0.0.20:8080;
    server_name app.example.test;
    allow 10.0.0.10;
    deny all;
    root /var/www/slightphp/samples/www;
    index index.php;
    location / { try_files $uri $uri/ /index.php$is_args$args; }
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_param HTTPS on;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
    location ~ \.php(?:/|$) { return 404; }
}
```

不要把代理的明文 HTTP 请求转发到这个 HTTPS 专用后端。不要直接将 `$http_x_forwarded_proto` 传作可信 HTTPS 状态；代理应覆盖转发头，后端也必须限制访问来源。生产验证时检查响应中会话 Cookie 及语言 Cookie 均含 `Secure; HttpOnly; SameSite=Lax`，并确认通过 HTTPS 刷新后会话仍然保留。

## 会话和模板

示例使用 files 会话后端和系统临时目录，确保该目录可写。模板编译目录为 PHP 的系统临时目录，首次渲染自动生成编译子目录，需可写；新建编译目录及子目录默认权限为 `0700`（受 umask 影响），已有目录权限不修改。缓存不提交版本控制。模板源文件及编译文件分别校验其目录边界；清理旧模板后不应依赖其编译产物继续运行。

`compile_dir` 及其 CRC32 子目录不检查归属或权限，允许使用 `/tmp`，不会自动修改已有目录权限。编译文件也不检查所属用户或权限：新建或重新写入时设为 `0600`，命中缓存时不修改权限。新目录默认使用 `0700`，因为目录必须具备执行位才能访问内部文件，不能使用 `0600`。实际读写仍受操作系统权限限制。

## 常见问题

| 现象 | 排查 |
| --- | --- |
| 任何地址都显示首页 | 保留原始 REQUEST_URI，入口不得用缺失的 PATH_INFO 覆盖为 / |
| URL 包含 index.php | 模板、表单和控制器跳转直接使用以 / 开头的根路径 |
| 静态资源 404 | 网站根目录应为 samples/www，资源地址使用 /assets/... |
| 命名空间控制器 404 | 检查文件大小写、Controller 后缀与 appDir |
| 模板不可写或页面 500 | 检查系统临时目录权限及服务端错误日志 |
| 会话数据不保留 | 检查 Cookie 与系统临时目录权限 |
| Nginx 返回 503 | 检查服务端限流、FPM 状态或临时可用性；与应用返回的 404 区分 |

## 验证

部署完成后，通过浏览器访问以下路径并检查结果：

- `/`、`/site/main/guide`、`/site/main/examples`：页面和静态资源正常加载，导航与示例链接可用。
- `/r/hello?source=php`、`/r-json/hello?source=json`：路由实验室显示映射结果，查询参数保留。
- `/tasks`：添加、切换完成状态并删除测试任务；刷新后结果保留。
- `/tasks.json`：GET 返回当前会话的任务 JSON；浏览器直接访问 `/tasks/save` 应返回 405，并带有 `Allow: POST`。
- `/lab/template/entry`、`/lab/language/entry`：模板组合正常，语言切换与动态称呼显示正确。
- `/missing/main/entry`：返回 HTTP 404 和可返回首页的错误页。

使用浏览器开发者工具检查响应状态及响应头，并在独立会话中执行任务操作。
