# 部署与排错

## 网站入口与 URL

部署示例站点时，将网站根目录设为 `samples/www`，并把不存在的文件路径转发到 `index.php`。部署完成后访问站点根路径 `/`；站内链接以 `/` 开头，不带域名或 `/index.php` 前缀。

入口优先从 `REQUEST_URI` 提取原始路径，剔除查询串后传给 `setPathInfo()`。不要用 `PATH_INFO ?? '/'` 作为唯一路径来源：URI 转发后 PATH_INFO 往往为空，会把内页误判为首页。只有 REQUEST_URI 缺失时，才回退使用 PATH_INFO。

Nginx 配置示意（替换根路径、域名和 FPM 地址）：

```nginx
server {
    listen 80;
    server_name example.com;
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

以上只展示 HTTP 入口转发；实际部署时按站点需要配置 HTTPS。不要公开仓库根目录、配置、应用源码或模板编译目录。

## 会话和模板

示例显式使用 files 会话后端和系统临时目录，避免依赖机器默认 Redis 配置。模板编译目录为 `samples/app/templates_c`，首次渲染自动生成，需可写；缓存不提交版本控制。清理旧模板后不应依赖其编译产物继续运行。

## 常见问题

| 现象 | 排查 |
| --- | --- |
| 任何地址都显示首页 | 保留原始 REQUEST_URI，入口不得用缺失的 PATH_INFO 覆盖为 / |
| URL 包含 index.php | 模板、表单和控制器跳转直接使用以 / 开头的根路径 |
| 静态资源 404 | 网站根目录应为 samples/www，资源地址使用 /assets/... |
| 命名空间控制器 404 | 检查文件大小写、Controller 后缀与 appDir |
| 模板不可写或页面 500 | 检查 templates_c 目录权限及服务端错误日志 |
| 会话数据不保留 | 检查 Cookie 与系统临时目录权限 |
| Nginx 返回 503 | 检查服务端限流、FPM 状态或临时可用性；与应用返回的 404 区分 |

## 验证

部署完成后，访问站点并运行以下命令；HTTP 检查需要传入实际站点地址：

```bash
php samples/tests/routing.php
read -r -p "站点地址（包含 http:// 或 https://）: " SITE_URL
python3 samples/tests/website.py "$SITE_URL"
python3 samples/tests/smoke.py "$SITE_URL"
```

routing 在独立 CLI 进程中验证真实入口、Composer 自动加载、模板与 URI 解析，不启动 HTTP 服务；website 只读验证官网和示例入口；smoke 会在独立会话中添加、切换并删除测试任务，不影响其他会话。
