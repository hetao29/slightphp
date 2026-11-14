# Changelog

## 6.4.0 — 2026-11-14

### 新增与不兼容变更

- Tpl 的 `$_tpl_vars` 改为受保护的实例属性，`assign()`、`fetch()` 改为实例方法，不同对象之间隔离模板数据；模板 include 自动传入当前实例。编译文件增加格式标记，旧静态变量缓存首次使用时自动重建；目录与分隔符等配置继续使用静态接口。
- 修复模板 part 未关闭输出缓冲的问题，收集模块直接输出及字符串返回值，正常、嵌套和异常调用均清理可移除的片段缓冲；非字符串返回值不作为 HTML 输出。新增 `/lab/template/entry` 和 Widgets 模块片段示例，附 [include / part 手册](docs/templates.md)。
- part 遇到不可移除的输出缓冲时停止清理并抛出异常，保留控制器原有异常，避免无限循环；恢复 include 标签名的大小写兼容，并自动重建旧格式编译缓存。

### 升级注意事项

模板调用 `Tpl::assign()`、`Tpl::fetch()` 需改为复用同一个实例，不再支持从外部访问 `Tpl::$_tpl_vars`。直接调用 `tpl_function_include()` 时需增加第二个实例参数；旧编译缓存需可重写，常驻进程需重启。详见[模板迁移说明](docs/migration-6.4.md#模板变量与实例)。

PHP 代码通过 `tpl_function_part()` 得到 HTML 字符串；需要控制器原始非字符串结果时改用 `SlightPHP::run()`。片段的响应状态和响应头由外层页面统一管理。

## 6.3.0 — 2026-11-14

### 新增与不兼容变更

- 新增 `SlightPHP\Request` / `Response`，提供 PHP 请求快照、原始正文、响应头、状态码、Cookie、正文输出、结束及重定向接口；`$request->route` 对应原 `$inPath` 的完整路径数组。
- 调度直接按 `($request, $response)` 的顺序调用入口，参数名不限；不使用反射。构造控制器时传入同一个 Request，构造前已设置完整 `route`；移除构造函数和入口的 `$inPath` 数组传参，原签名须迁移。响应结束后 `run()` 返回 `null`，防止现有入口重复输出。
- Request/Response 统一通过 Composer 的 `SlightPHP\` PSR-4 映射按需加载，移除调度中的手动文件加载。

### 升级注意事项

旧的 `__construct(array $inPath)`、`Entry(array $inPath)` 以及未声明类型但按数组使用 `$inPath` 的控制器都需要调整；手动创建控制器和直接调用入口的代码也需同步。`end()` / `redirect()` 不会退出 PHP，调用后应从控制器返回。完整步骤及前后对照见 [6.3 迁移指南](docs/migration-6.3.md)。

## 6.2.2 — 2026-11-11

- `Db::select()` 新增末尾 `$for` 参数；仅精确传入大写 `UPDATE` / `SHARE` 时，在主查询 LIMIT 后追加 `FOR UPDATE` / `FOR SHARE`，COUNT 查询不加锁。
- `Db::selectOne()` 新增末尾 `$hits`、`$for` 参数并传给 `select()`，原有位置参数调用保持兼容。事务及连接由调用方管理，数据库须支持对应锁语法。

## 6.2.1 — 2026-11-10

- `Db::query()` 移除第三个 `$retry` 参数和连接失败后的自动重连、重试逻辑；查询失败保留错误信息、触发 `E_USER_WARNING` 并返回 `false`。
- `DbPDO` 移除公开的 `$connectionError` 标记，`errno()` 从 `error()` 返回的错误信息读取 SQLSTATE。依赖旧参数、标记或自动恢复行为的应用需要迁移，见 [6.3 迁移指南](docs/migration-6.3.md#数据库调用)。

## 6.2.0 — 2026-11-09

### 新增与安全修复

- 核心调度新增 `setMaxDepth()` / `getMaxDepth()`，请求路径默认最多 10 段，包含区域、page、entry 和路径参数；超限时在加载控制器前返回 `false`，正常请求和内部调用均生效。
- Config 仅接受 `.json` / `.jsonc`，移除旧配置解析器及序列化文件缓存；`.json` 使用严格 JSON，注释仅在 `.jsonc` 中支持，保留进程内缓存。
- Tpl 校验模板源文件、编译文件及子目录的规范路径边界，拒绝指向目录外的符号链接。新建编译目录默认 `0700`，新建或重新写入编译文件设为 `0600`；已有目录及命中的缓存文件权限不自动修改。
- 模板 `escape` 修饰器明确使用 UTF-8 和 `ENT_QUOTES | ENT_SUBSTITUTE`，转义单双引号并替换非法 UTF-8。
- Language 校验语言标签、q 值及 zone，限制请求头长度与条目数，忽略非法项及 `q=0` 项，拒绝读取语言目录外的文件。
- Captcha 每个会话前缀仅保留最新验证码，新增默认 300 秒的 `Captcha::$ttl`；刷新替换旧记录，成功校验后立即移除，过期验证码不可用。
- Error 新增默认关闭的 `Error::$LOGARGS`，默认不采集或输出调用参数；HTML 错误内容及网页中的非纯文本、非 JSON 错误输出进行转义。
- Log 网页控制台输出进行 HTML 转义；`LOGTYPE = 'json'` 改为每行一条完整 JSON 记录，字段为 `time`、`level`、`message`，消息中的控制字符由 JSON 编码。

### 升级注意事项

旧 `.conf` 文件、含注释的 `.json` 文件、超过默认深度的路由、旧验证码会话记录及依赖旧 JSON 日志格式的解析程序需要调整。详见 [6.2 升级说明](docs/migration-6.2.md)。

## 6.1.3 — 2026-11-09

- Error 关闭回调遵循 `error_reporting()`，不再补记被屏蔽的最后一条错误。

## 6.1.2 — 2026-11-04

- DbPDO 保留建连失败时的原始错误信息，`error()` / `errno()` 可返回建连错误；重新连接时重置旧状态。
- Error 对调用参数数组中的密码、密钥和令牌等字段脱敏，对象替换为类名。

## 6.1.1 — 2026-11-03

- Redis 的 RedisArray 分支使用构造函数传入的主机和选项，修复参数未生效的问题。

## 6.0.1–6.1.0 — 2026-11-02

- Config 增加 JSON / JSONC 配置支持。此阶段 `.json` 也接受注释，仍兼容旧配置格式及文件缓存；6.2.0 的收紧规则见上文。

## 6.0.0 — 2026-11-01

- 控制器统一使用命名空间模式，移除 PAGE 模式及默认 `page` 方法前缀。
- 插件统一使用 `SlightPHP\` 命名空间及 Composer 自动加载，移除旧全局类名、兼容别名及部分历史插件。
- Captcha 基于 Gregwar Captcha；数据库操作统一由 `SlightPHP\Db` 提供。
- 更新框架文档、API 参考及可运行示例。5.x 应用升级请阅读 [6.0 迁移指南](docs/migration-6.0.md)。
