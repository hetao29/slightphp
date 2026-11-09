# 从 5.x 迁移到 6.0.0

6.0 是不兼容的主版本。先在测试环境升级依赖和应用代码，覆盖控制器、插件及部署路径，再发布到生产环境。

当前版本为 6.2.0。本页保留从 5.x 迁移到 6.x 的基础说明；升级到当前版本时，还需阅读 [6.2 升级说明](migration-6.2.md)。

## 安装与加载

将依赖约束改为 `hetao29/slightphp:^6.2`，执行 `composer update hetao29/slightphp`，并在入口加载 `vendor/autoload.php`。包要求 PHP 8.0+、`ext-pdo`、`ext-redis`、Guzzle 和 Gregwar Captcha；验证码还需 GD、mbstring、fileinfo 和可用的会话。数据库和 Redis 功能分别需要对应驱动和服务。

旧版手动引用 `plugins/S*.php` 的路径和 `SLIGHTPHP_PLUGINS_DIR` 常量已移除。模板辅助函数仍由 Composer 自动加载。切换部署版本后清理并重新生成模板编译缓存。

## 控制器和路由

框架只支持命名空间控制器。将 `app/user/profile.page.php` 中的 `user_profile::pageUpdate()` 改为 `app/User/ProfileController.php` 中的 `User\ProfileController::Update()`；`/user/profile/update` 的 URL 可保持不变。多级目录同样要按命名空间首字母大写规则迁移。可用 `setControllerSuffix()` 和 `setAppNamespace()` 配置后缀及根命名空间；后者也影响磁盘路径。

删除 `setModes()`、`getModes()`、`setDefaultType()`、`getDefaultType()` 和 `$defaultType` 的调用。无 `Request-Type` 请求头时，入口方法不再默认添加 `page` 前缀；有该头且值在允许列表中时，仍会添加对应前缀。注意检查 AJAX、内部调用、CLI 任务及自定义错误页面。框架仍不根据 GET/POST 自动限制方法。

请求路径现在默认最多 10 段，包含区域、page、entry 和路径参数；超过上限时 `run()` 直接返回 `false`，不会加载控制器。原有路由或业务参数超过 10 段时，可在初始化阶段通过 `SlightPHP::setMaxDepth($maxDepth)` 调整（必须大于等于 1），通过 `getMaxDepth()` 读取。未超限的 `$inPath` 参数仍保持原样，默认路径补全不额外计数。该限制同时适用于正常请求及内部调用，详见[核心调度文档](framework.md)。

## 插件类名

旧的全局插件类名及兼容别名均已删除，必须使用命名空间类，或在调用处使用 `use` 导入：

| 5.x | 6.0 |
| --- | --- |
| `SCaptcha` | `SlightPHP\Captcha` |
| `SConfig` | `SlightPHP\Config` |
| `SDb` | `SlightPHP\Db` |
| `SError` | `SlightPHP\Error` |
| `SHttp` | `SlightPHP\Http` |
| `SJson` | `SlightPHP\Json` |
| `SLanguage` | `SlightPHP\Language` |
| `SLog` | `SlightPHP\Log` |
| `SRedis` | `SlightPHP\Redis` |
| `SRoute` | `SlightPHP\Route` |
| `STpl` | `SlightPHP\Tpl` |

同时替换 `new`、静态调用、继承、类型声明、`instanceof` 和配置中存储的类名。数据库的表记录、查询及事务操作现在都在 `SlightPHP\Db`；`SlightPHP\Internal\Db` 不再存在。

`SCache`、`SLunar`、`SMail`、`SSphinx`、`SUtil` 和 `SThumbnail` 已移除，没有内置替代别名。请根据应用功能另选维护中的依赖并迁移接口和配置。

验证码实现改用 `Gregwar\Captcha\CaptchaBuilder`；旧 `SimpleCaptcha` 的专有属性和方法不再可用，检查自定义验证码生成代码。保留的 `Captcha::check()` 和 `del()` 使用当前会话。确认图片扩展、字体和输出流程后重新测试。

## Config 配置迁移

`SlightPHP\Config` 仅支持 `.json` 和 `.jsonc`，不再解析 `.conf` 等旧格式。将分组转换为 JSON 对象、重复键转换为数组，并更新 `Db::setConfigFile()`、`Redis::setConfigFile()` 及 `Config::getConfig()` 等调用中的路径。`.json` 使用严格 JSON；需要注释时使用 `.jsonc`。

Config 已取消序列化文件缓存，只保留由 `Config::$CACHE` 控制的进程内缓存；旧的 `0SConfig_Cache_*` 文件不会再读取。关闭缓存不会清空已有内存结果，常驻进程更新配置后应保持缓存关闭或重启进程。

## 升级检查

逐项测试默认路由、多级控制器、请求类型前缀、404 和反向代理路径；检查配置、模板、数据库、Redis、日志及验证码；重新生成 Composer 自动加载文件并在实际 PHP 运行环境运行测试。原 PAGE 文件不会被调度，旧类名不会自动加载，不能依靠旧缓存掩盖迁移问题。
