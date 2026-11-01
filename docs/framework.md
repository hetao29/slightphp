# 核心调度与路由

## 请求生命周期

1. 入口加载核心，设置应用目录、默认路径和需要的插件。
2. `run()` 选择路径：非空显式参数 → 非空 `setPathInfo()` → `$_SERVER['PATH_INFO']` → `$_SERVER['REQUEST_URI']`。全部为空时返回 `false`。
3. 去掉开头 `/`，用 `parse_url(..., PHP_URL_PATH)` 排除查询串，按 `/` 与 splitFlag 的每个字符拆分。不会 URL 解码或自动过滤业务参数。
4. 缺少前三段时分别补 defaultZone、defaultPage、defaultEntry。
5. 从最深候选目录开始向浅层尝试，将倒数两段作为 page、entry；每个深度按 modes 顺序寻找文件。
6. 文件必须经 realpath 验证位于 appDir 内且是普通文件，再 `require_once`。
7. 找到类后使用完整路径数组构造实例；检查目标方法存在且可公开调用，再传入同一数组执行。构造函数可能在方法未匹配时就执行，不应在其中执行写操作。
8. 返回方法的原始返回值；未匹配返回 `false`。核心不会自动输出字符串、序列化对象、发送 404 或捕获异常。

## 两种控制器模式

| 模式 | 请求 | 文件（相对 appDir） | 类 / 方法 |
| --- | --- | --- | --- |
| PAGE | `/user/profile/update` | `user/profile.page.php` | `user_profile::pageUpdate($inPath)` |
| NAMESPACE | `/user/profile/update` | `User/ProfileController.php` | `User\ProfileController::Update($inPath)` |
| 多级 PAGE | `/user/info/edit/thumb` | `user/info/edit.page.php` | `user_info_edit::pageThumb($inPath)` |
| 多级 NAMESPACE | `/user/info/edit/thumb` | `User/Info/EditController.php` | `User\Info\EditController::Thumb($inPath)` |

默认顺序 `['PAGE', 'NAMESPACE']`。同一深度找到文件会停止尝试其他模式，即使该文件的目标方法不存在，也不会再尝试该深度的下一种模式。建议一个路径只使用一种模式，避免重名。

PAGE 路径与类名转换为小写；NAMESPACE 将下划线分段首字母大写，并附加 Controller 后缀。在大小写敏感文件系统上必须按此规则命名。

`setAppNamespace('Demo')` 同时影响类名和文件路径：例如上述 NAMESPACE 文件需要放在 `appDir/Demo/User/ProfileController.php`，声明 `namespace Demo\User`。并非仅给类名加前缀。

## 路径参数

`/workspace/main/entry/hello` 的 `$inPath` 是：

```php
['workspace', 'main', 'entry', 'hello']
```

多级路由时 entry 的下标随目录深度变化，不应一律认为第三段是方法、第四段是参数。控制器应根据自己的固定路由结构读取参数并校验。`$_GET` 与 `$_POST` 仍由 PHP 提供，框架不绑定参数。

## 配置 API

| 方法 | 默认值 / 说明 |
| --- | --- |
| `setAppDir($dir)` / `getAppDir()` | `.`，建议使用存在目录的绝对路径 |
| `setModes($modes)` / `getModes()` | `['PAGE', 'NAMESPACE']`，模式名大写 |
| `setAppNamespace($namespace)` / `getAppNamespace()` | 空字符串 |
| `setControllerSuffix($suffix)` / `getControllerSuffix()` | `Controller`，只影响 NAMESPACE |
| `setDefaultZone($zone)` / `getDefaultZone()` | `zone` |
| `setDefaultPage($page)` / `getDefaultPage()` | `page` |
| `setDefaultEntry($entry)` / `getDefaultEntry()` | `entry` |
| `setDefaultType($type)` / `getDefaultType()` | `page`，PAGE 默认方法前缀 |
| `setSplitFlag($flag)` / `getSplitFlag()` | `/`；例如 `-.` 同时按 `-`、`.`、`/` 拆分 |
| `setPathInfo($path)` | 覆盖服务器路径来源 |
| `setAllowRequestTypes($types)` / `getAllowRequestTypes()` | `page, ajax, part, json, xml, html` |
| `setDebug($bool)` / `getDebug()` | 默认关闭；开启后依赖已加载的 `SLog` |
| `setZoneAlias($zone, $alias)` / `getZoneAlias($zone)` | 设置 / 读取别名 |
| `setPageAlias($page, $alias)` / `getPageAlias($page)` | 设置 / 读取别名 |

别名存在实现限制：当前源码用别名计算 `zone/page`，但文件类名仍由原始 zoneParts 构造，zone 别名不能可靠替代目录映射。完整示例不依赖别名；短 URL 建议用 SRoute 映射。

## 方法前缀与 HTTP 方法

`HTTP_REQUEST_TYPE` 对应客户端请求头 `Request-Type`。如果值在允许列表中，会作为方法前缀；不在列表中的值会使用无前缀方法。PAGE 无该头时默认 `page` 前缀，NAMESPACE 默认无前缀。

这与 GET、POST、PUT 等 HTTP 方法无关，不是权限或路由白名单。应用应在控制器检查 `REQUEST_METHOD`，谨慎暴露 public 方法。本工作台清除此请求头，使公开行为固定。

## CLI 和内部调用

```php
require 'SlightPHP.php';
SlightPHP::setAppDir(__DIR__ . '/samples/app');
// 实际 CLI 控制器应避免依赖 HTTP 和会话。
$result = SlightPHP::run('/your/controller/entry');
```

传入非空参数会进入内部调用（part）语义：不更新静态 `zone/page/entry`；若目标与当前顶层路由相同，则直接返回 `false`，防止直接自调用。它不是通用递归保护，循环调用仍需应用控制。常驻进程中的静态配置会保留，应隔离每次请求的环境。
