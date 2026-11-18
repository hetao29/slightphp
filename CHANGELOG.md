# Changelog

## 6.6.0 — 2026-11-18

本版新增可选的模板上下文自动转义，并调整 Cookie 默认策略、Db 内部类名及模板辅助函数。升级步骤与不兼容事项见 [6.6 迁移指南](docs/migration-6.6.md)。

- 6.7 变更预告：Tpl 的 contextMode 将默认开启，并移除 `setContextMode()`、`getContextMode()`，不再支持调用这两个方法。6.6.0 仍默认使用兼容模式并保留这两个方法；升级前应在 6.6 中验证自动转义，迁移预编码数据与模板，升级到 6.7 时删除模式设置和读取调用。
- Tpl 自动转义迁移说明（仅对显式启用 `setContextMode(true)` 的模板生效）：HTML 输出可移除手工 `htmlspecialchars()`、`htmlentities()` 及末尾 HTML `|escape`，模板中独立 URL 组成部分可移除 `urlencode()`、`rawurlencode()` 或 `|escape:"url"`，统一传原始数据并按输出上下文编码。普通函数没有编码意图标记，保留预编码可能重复转义；末尾内置 `escape` 仍受支持，可供两种模式共用。默认兼容模式继续需要手工编码；完整 URL 构造、业务/签名编码不能盲删，URL 组件中的空格自动编码为 `%20`，与 `urlencode()` 的 `+` 不同。替换示例及边界见 [Tpl 自动转义不兼容说明](docs/migration-6.6.md#tpl-自动转义迁移启用时不兼容)。
- Tpl 将只含纯变量/常量键查找的完整 HTML 块编译为直接取值、上下文转义和 echo，原生数组/标量路径不再逐值创建闭包、输出缓冲或调用状态机；同一只读值的相同编码在块内复用。保留实际 HTML 入口、数据类型守卫、URL 拼接检查及原路径回退，ArrayAccess/Stringable/缺失键仍按原有规则处理；旧编译缓存自动重建。
- Tpl 自动模式将静态 HTML 标签前缀、引号加 `>` 闭合及整块文本输出编译成带实际入口守卫的计划，减少属性密集列表热缓存下的重复逐字符解析；普通带引号属性的标量使用静态提示直接编码，仅有普通属性的标签不再重复提取属性。动态 URL/事件/style/srcset/meta content 的组合检查、script type 判定及转换回调守卫保留；旧编译缓存自动重建。
- Tpl 自动模式识别 JS 展开运算符 `...`，支持数组、对象及函数参数展开后的动态操作数，JS 计划版本递增使旧缓存重建。CSS URL 已有前缀时只对变量做 URL 组成部分编码，修复 `red:blue` 等参数误报；完整 URL 与最终拼接的 URL 仍执行协议检查。
- Tpl 控制标签的原生 JS 块判断跳过字符串和注释，修复条件字符串包含 `){` 时的误判；恢复 true/false/null 字面量及其表达式标签，两种模式与自定义分隔符均支持。自动模式在正则起始处将空插值编码为 `(?:)`，防止形成 JS 注释；保留字符类和已有正则内容后的空插值语义，旧模板及 JS 计划缓存自动重建。
- Tpl 自动模式为 HTML 文本、属性、RCDATA、CSS、JS/JSON 的字符串转换及序列化统一增加输出缓冲与上下文修订号守卫，阻止 Stringable 回调直接输出或嵌套渲染改变编码位置；属性追加快路径也更新修订号。根据 script 的首个 type 属性识别 JSON/JSON-LD、importmap 等数据块，输出标准 JSON，保留可执行 JS 的表达式括号；旧编译及上下文计划缓存自动重建。
- Tpl 自动模式将完整 JS 值用圆括号隔离，修复负数与相邻运算符组合成 `--` 等无效代码；同步状态推进计划并使旧计划缓存失效。CSS 未加引号的 url() 单独转义空格及其他空白，保留已加引号 URL 与字符串的正常空格。
- samples 的全部模板、入口配置及页面展示代码统一使用默认 `{}`，文档示例同步；验证原生 JS/CSS 花括号、美元符号、literal 和三种语言页面的冷/热缓存。
- samples 模板实验新增独立自动模式 Context 模块，实际运行全部 Modifier、串联、HTML/URL、JS/事件、CSS、默认分隔符/literal、缓存变量更新与错误用例；简体中文、繁体中文、英文同步，Tpl 手册与示例索引更新。
- Tpl 将 script/事件属性的 JS 词法结果编译成编码及状态推进计划并写入 PHP 编译文件；静态分支预先分析，动态新入口另编译有界的上下文变体。完整命中时不再执行 JS 词法扫描，保留实际 HTML 状态、空值/数字边界和编码回调守卫。JS 计划编译统一放在 `Tpl\Compiler`，不保留独立 JsCompiler 类；旧编译文件自动重建。
- `Tpl\Modifier` 新增 raw；自动模式适配 raw、escape、default、tr、tostring、version 及修饰链。数据先转换再按实际上下文编码，末尾 escape 避免重复 HTML/URL 编码，末尾 raw 标记最终可信原文；同名普通 PHP 函数回退保留，不恢复 tpl_modifier_* 前缀查找。
- Tpl 恢复常量算式及自定义右分隔符下的常量/数字标签识别；带空格常量算式不再误作函数简写，大写函数调用继续兼容。raw-text 结束标签改为增量候选匹配，普通比较符不再反复搜索整个正文，保留跨段识别及 script escaped/double-escaped 状态；旧编译缓存自动重建。
- Tpl 自动模式拒绝敏感属性中缺少分号、未知或不支持的 HTML 实体引用，避免 PHP 与浏览器解码差异；JS 行首空白包含垂直制表符，正确识别 HTML 风格行注释。默认分隔符恢复 HTML 中 `${$price}` 的美元符号加变量输出，仅在 JS 模板字符串中保留原生插值；自动模式恢复混合大小写 include 的父实例传递。
- Tpl JS 扫描区分控制条件圆括号和普通调用，修复控制条件后正则的误判；无法确定的斜杠位置明确报错。Lexer 在完整源码的偏移上匹配候选，避免为每个花括号复制剩余模板，消除该路径的二次方分词开销。
- Tpl 自动模式生成带入口守卫的静态 HTML 块与 HTML/属性编码计划，缓存命中后减少逐字符输出、标签重复解析；JS 计划和 CSS 增量扫描保留 token 边界。分支、循环、include、raw 使用实际入口，保留 URL/CSS 校验、表达式输出检测及异常传播；旧缓存自动重建。
- Tpl 防止跨属性插值重新组成 HTML 实体，并编码 JS 模板字符串的动态花括号，避免与静态 `$` 组成可执行插值；补充真实 JS 执行、缓存复用及扫描器差分回归。

- Tpl 上下文判断修复后置 `++/--` 后除法与正则混淆，以及 HTML script escaped/double-escaped 状态的结束标签误判；恢复 Else/ElseIf 及结束控制标签的大小写兼容。

- Tpl 新增实例级 `setContextMode(true)`，默认关闭；按实际渲染流处理 HTML 文本、属性、URL、JS 与 CSS 的自动转义，支持 Modifier 修饰链和末尾 `|raw`。默认 `{}` 分隔符使用 Lexer 识别模板语法，保留普通 JS/CSS 花括号及表达式中的引号和嵌套括号；歧义片段可用 `{literal}` 或更换分隔符；模板异常原样传播；兼容模式保留原有修饰器。模式分别缓存，旧编译文件自动重建；限制与示例见 [模板手册](docs/templates.md#上下文自动转义)。
- 模板辅助函数迁移为 `SlightPHP\Tpl\Func`、`SlightPHP\Tpl\Modifier` 的静态方法，文件位于 `plugins/Tpl/`；更新编译器、Composer 自动加载及 PHP 调用示例，模板标签写法保持可用。不再导出内置全局函数，也不再通过 `tpl_function_*` / `tpl_modifier_*` 前缀查找扩展；保留同名普通 PHP 函数回退。

- 数据库内部类迁移为 `SlightPHP\Db\Data` 和 `SlightPHP\Db\PDO`，文件分别位于 `plugins/Db/Data.php`、`plugins/Db/PDO.php`；查询结果、适配器及自动加载引用同步更新，计数行为保持读取第一行。
- 核心调度在实例化控制器前检查入口是否存在且为 public，缺失或非公开入口不再触发构造函数；保留浅层候选回退、原异常原因码、请求类型和 Route HTTP 方法校验，以及有效入口的构造函数异常传播。
- Language 的 `setLocale()` 清空候选语言列表，运行中切换语言即时生效；`setLanguageDir()` 清空 Language 词条缓存，避免不同目录复用旧词条。新增 `resetRequest()` 清除强制语言及候选列表，保留应用配置和词典缓存；明确串行常驻请求的清理方式、Config 缓存及并发隔离边界。
- Response Cookie 默认启用 Secure、HttpOnly 和 SameSite=Lax，新增 `setCookieDefaults()` / `getCookieDefaults()`；单次调用可显式覆盖，SameSite=None 必须同时启用 Secure。示例会话在启动前明确设置安全参数，语言 Cookie 复用统一策略；补充 HTTPS 和可信反向代理部署示例，HTTP 开发环境显式关闭 Secure。
- Redis 的 sentinel 模式改为通过 RedisSentinel 按服务名发现并验证主节点，支持多 Sentinel 回退、独立认证、选库和持久连接；断线后后续调用重新发现，不重放失败命令。此模式要求 phpredis 6.0+，配置及连接边界见 [Redis 说明](docs/plugins.md#redis)。

## 6.5.3 — 2026-11-16

- Language 语言文件由 INI 改为 JSON/JSONC，与 Route 一样使用 `Config::parse()`；按 `locale/zone.json`（优先）或 `locale/zone.jsonc` 加载，顶层须为对象且所有词条值须为字符串。保留语言选择、词条回退及目录边界校验，格式和结构错误抛出异常。旧 INI 文件需转换内容，不能仅改扩展名；samples 的简体、繁体、英文词条已同步转换。见 [Language 说明](docs/plugins.md#language)。
- 官网与交互工作台支持简体、繁体和英文，界面词条与客户端提示共用 Language 资源；语言选择记入 Cookie。主题增加默认跟随系统的「自动」模式，并保留手动深浅色偏好。
- 导航新增完整 docs 阅读与 ChangeLog 页面，直接读取仓库文档和生成的 API 参考；「使用指南」更名为「入门」，位于首页之后。
- samples 增加 Language 多语言实验，展示三种语言的 JSON 词条、动态称呼、缺失词条回退和可复制 PHP 代码；入口加入示例索引、插件卡片与工作台侧栏。功能特性、调度流程和应用职责合并到首页，旧功能页跳转至首页区域。

## 6.5.2 — 2026-11-16

- 核心在候选方法名赋值后立即核对已配置的请求类型前缀与 `HTTP_REQUEST_TYPE`，缺失或不匹配时在方法存在性、Route 注解及控制器构造前抛出 `METHOD_NOT_ALLOWED`（405），不继续候选回退。显式 URL 和内部调度均执行校验；`setAllowRequestTypes()` 统一保存小写类型并过滤空字符串，前缀按配置顺序首匹配，命中后立即校验并退出循环。应用须为带类型前缀的方法发送匹配请求头，仍保留 HTTP 方法、登录、对象权限及 CSRF 校验。
- 请求类型错误的诊断上下文包含 `requestType`、`requiredRequestType` 和空的 `allowedMethods`；示例入口仅在 Route HTTP 方法列表非空时输出 `Allow`，避免生成空头。见[方法前缀](docs/framework.md#方法前缀与-http-方法)和[异常处理](docs/exceptions.md)。

## 6.5.1 — 2026-11-16

- `Route::setConfigFile()` 改为通过 `Config::parse()` 加载 JSON/JSONC 顶层数组，每个对象为一条规则，按数组顺序追加并匹配；不再支持 INI。保留 PHP 规则、占位符、方法注解和首条匹配优先的行为，示例改为 `routes.json`，旧配置需按[路由迁移说明](docs/migration-6.5.md#路由配置改为-jsonjsonc)转换。
- 移除 `SlightPHP\Json` 插件及 `plugins/Json.php`，JSON 编解码与错误查询改用 PHP 原生 `json_encode()`、`json_decode()`、`json_last_error()` 和 `json_last_error_msg()`。不保留兼容类，已有调用需迁移；示例、官网插件清单与 API 参考同步更新，见 [6.5 迁移指南](docs/migration-6.5.md#json-插件移除)。
- Composer 强制依赖仅保留 PHP >=8.0；数据库、Redis、HTTP 和验证码插件依赖改为 `suggest`，默认安装不再拉取 Guzzle、Gregwar Captcha 及其传递依赖。使用这些插件的应用需显式声明所需依赖，见[按需安装依赖](docs/plugins.md#按需安装依赖)。

## 6.5.0 — 2026-11-15

- 官网地址统一为 [https://slightphp.com/](https://slightphp.com/)，更新安装示例、API 参考与 [6.5 迁移指南](docs/migration-6.5.md)。
- Route 方法元数据先按控制器和入口查缓存，未命中才反射；无注解和非公开方法也缓存，继承/覆盖按实际控制器隔离，HTTP 方法匹配仍逐请求判断。
- 移除调度与候选解析中的 `is_dir()` 查询；应用目录规范化时通过目录路径解析校验类型，候选仍检查普通文件及应用目录边界。
- 优化候选控制器查找：普通文件缺失使用分支继续回退，类或入口不可用时只记录诊断，最终调度失败才构造并抛出异常；保留文件越界校验、HTTP 方法限制和业务原始异常传播。
- samples 错误页渲染时临时禁用 Request-Type 方法前缀并在结束后恢复，避免携带 ajax 等请求头的失败请求在 404/500 处理中再次抛出入口不存在异常。
- 修复 `Route::parse()` 将 `//articles/42` 的第一段误当作主机名、导致匹配丢失或误命中 `/42` 的问题；提取请求路径时保留重复前导斜杠，继续排除查询串。
- 自定义 URL 路由只匹配路径、不再受查询串影响；修复前缀相同的占位符相互替换和跨 INI 文件同名节覆盖已有规则的问题。公开 `Route::parse($path)` 支持显式匹配规范化路径；无效配置提供明确异常。samples 增加 PHP / INI 短地址、多参数和方法限制示例，见 [自定义 URL 路由](docs/routes.md)。
- 不兼容变更：核心调度失败统一抛出 `SlightPHP\Exception`，提供原因码、诊断上下文和建议 HTTP 状态；路径缺失/超深、应用目录无效、控制器文件/类/方法不可用、文件越界、重复内部调用和缺失别名不再返回 `false`。业务入口主动返回的 `false` 仍保留；候选控制器查找和原始应用异常传播不变。迁移见 [核心异常与迁移](docs/exceptions.md)。
- `Route` 支持控制器方法注解 `#[Route(method: 'POST')]` 和 `#[Route(methods: ['POST', 'GET'])]`；两者同时设置取并集。HTTP 方法不匹配时在控制器构造前抛出 `METHOD_NOT_ALLOWED`，由应用设置 405 和 Allow，未声明限制的入口保持兼容。原静态 URL 映射 API 保留。

## 6.4.2 — 2026-11-15

- 恢复 `Request::$request` 数组，直接保存 PHP `$_REQUEST` 快照，保留配置决定的合并来源、覆盖顺序与数组语义；与 `get/post` 及超全局变量分别独立。

## 6.4.1 — 2026-11-15

- 简化模板 part：直接输出模块内容及字符串返回值，函数自身返回 null；模块自行管理输出缓冲，异常原样传播。

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
