# Changelog

版本更新、不兼容变更及升级迁移说明统一收录在此。按版本查看更新记录，再阅读对应的迁移指南。

迁移指南：[6.7](#67-迁移指南) · [6.6](#66-迁移指南) · [6.5](#65-迁移指南) · [6.4](#64-迁移指南) · [6.3](#63-迁移指南) · [6.2](#62-迁移指南) · [6.0](#60-迁移指南)。

## 6.7.1 — 2026-11-18

- 新增 `SlightPHP\Csrf`：提供 `token()`、`tokenInput(?string $fieldName = null)`、`validate(Request $request, ?string $fieldName = null)`、`rotate()` 和 `clear()`。公共静态变量 `$fieldName` / `$sessionKey` 默认分别为 `_csrf` / `_slightphp_csrf`，可由应用入口统一配置。
- Route 新增 `csrf` 参数：true 使用默认字段和 `X-CSRF-Token`，字符串同时指定表单字段与同名请求头，省略或 false 不增加检查。在 HTTP 方法校验通过后、构造控制器前校验；无效 token 抛出 `CSRF_INVALID`（403），Session 未启动抛出 `CSRF_SESSION_NOT_STARTED`（500），由应用生成响应。
- 整理核心入口检查：方法缺失、非公开分别判断，元数据始终返回固定结构；只缓存可见性和 Route 策略，每次请求重新校验。Route 属性统一置于方法前，同一入口允许不同类型注解共存，重复 Route 明确抛出 `InvalidArgumentException`。
- samples 工作台改用 Route 的 CSRF 策略，新增 `/lab/csrf/entry` 三语言实验与 [CSRF 使用手册](docs/csrf.md)，展示手写字段、预先传入 hidden HTML、模板直接调用 `{\SlightPHP\Csrf::tokenInput()|raw}`，以及 JSON 请求、默认/自定义名称、403 拒绝和 token 更新。已验证首次编译、缓存复用及浏览器实际提交。
- 补齐 `Tpl.php` 与 `Tpl/*` 方法缺失的参数、返回值及关键局部变量类型注释，明确编译计划和上下文数据的数组结构。

## 6.7.0 — 2026-11-18

- 移除 Tpl 的 `$safe_mode`、`setSafeMode()` 及本地 `_compile()`；源码分词和编译统一由 `Tpl\Compiler::compileSource()` 完成。静态 PHP 标签不再替换为注释，按字面量输出；缓存选项去掉安全模式，旧缓存自动重建。Tpl 属性集中在类顶部，公开方法与内部渲染方法在后。
- Tpl 统一默认启用上下文自动转义，移除 `setContextMode()`、`getContextMode()` 及兼容编译分支。所有实例和子模板均按实际 HTML、属性、URL、JS、CSS 位置编码；旧调用需删除，依赖原样输出的模板需迁移。步骤与不兼容事项见 [6.7 迁移指南](#67-迁移指南)。
- 编译格式升级为 v10，统一使用 `.context.php` 缓存；旧上下文缓存自动重建，旧兼容编译文件不再执行，保留编译期 HTML/JS 计划及运行时守卫。
- 官网与 samples 全部迁移到默认自动转义：模板传原始数据和翻译原文，可信文档 HTML 使用末尾 raw；JSON 数据块直接接收数组，动态属性名改为条件输出静态属性。三种语言、安装示例和 API 参考同步更新。
- 框架仓库忽略并停止跟踪 `composer.lock`，保留本地锁文件；应用项目仍可自行管理锁文件。

### 6.7 迁移指南

6.7.0 将 Tpl 上下文自动转义设为统一行为，删除兼容编译分支及 `setContextMode()`、`getContextMode()`。这是不兼容变更，即使 6.6 中从未调用模式接口，也需要迁移依赖原样输出的模板。完整记录见 [Changelog](#changelog)；从 6.5.x 或更早版本升级，还需完成 [6.6 迁移](#66-迁移指南)。

#### 6.7.1 按需启用 CSRF

6.7.1 新增 `SlightPHP\Csrf`，入口通过 `#[Route(method: 'POST', csrf: true)]` 启用；字符串形式（如 `csrf: '--csrf--token'`）可同时指定表单字段与请求头名称。省略或设置 false 的入口保持原有行为。

应用先启动 Session，再通过 `Csrf::token()` 手写隐藏字段，或用 `Csrf::tokenInput()` 生成 HTML。Tpl 支持预先传入 `{$csrf_input|raw}`，也支持直接调用 `{\SlightPHP\Csrf::tokenInput()|raw}`。公共静态变量 `$fieldName` / `$sessionKey` 默认分别为 `_csrf` / `_slightphp_csrf`。无效 token 抛出 `CSRF_INVALID`（403），Session 未启动抛出 `CSRF_SESSION_NOT_STARTED`（500），由入口生成响应。

完整接入、Ajax、异常处理及 samples 实验见 [CSRF 使用手册](docs/csrf.md)。6.7.0 的模板迁移仍按本文后续章节执行；本次还补齐 Tpl 方法类型注释，并整理入口可见性、方法限制与 CSRF 策略的读取逻辑。

#### 6.7 安装与接口移除

```bash
composer require hetao29/slightphp:^6.7.1
```

删除所有模式设置和读取调用，包括 `setContextMode(true)`、`setContextMode(false)` 和 `getContextMode()`；这两个方法不提供兼容别名。直接创建模板实例：

```php
$tpl = new \SlightPHP\Tpl();
$tpl->render('page.tpl', ['name' => '<访客>', 'query' => '中文 & 字符']);
```

```html
<p>{$name}</p>
<input value="{$name}">
<a href="/search?q={$query}">搜索</a>
```

`assign()`、`render()`、`fetch()`、`display()`、默认 `{` / `}`、include 共享变量和 part 的独立实例继续可用。所有实例均按实际输出上下文编码，不能通过模式开关恢复旧的原样输出。

#### 6.7 安全模式与编译入口移除

删除 `Tpl::$safe_mode` 和 `Tpl::setSafeMode()` 的访问或调用；不再提供安全模式开关，也不再将原生 PHP 标签替换成注释。静态的 `<?php ... ?>` / `<?= ... ?>` 保留为模板字面量，不作为 PHP 代码执行；动态模板逻辑继续使用 `{...}` 表达式及控制标签。模板是可信代码，上下文编码不将其变成沙箱。

`Tpl::_compile()` 已移出入口类，源码编译统一由 `Tpl\Compiler::compileSource($source, $left, $right)` 完成分词和代码生成；应用仍通过 render/fetch 使用编译缓存。缓存文件头不再包含安全模式配置，旧配置生成的缓存自动失效并重建。

#### 6.7 手工编码与原始数据

模板应接收原始数据。仅用于 HTML 输出防护的 `htmlspecialchars()`、`htmlentities()`，以及独立 URL 组成部分的 `urlencode()`、`rawurlencode()` 通常可以移除：

| 旧写法 | 6.7 推荐写法 |
| --- | --- |
| `{htmlspecialchars($address['address'] ?? '')}` | `{$address['address'] ?? ''}` |
| `<a href="/search?q={urlencode($q)}">` | `<a href="/search?q={$q}">` |

普通函数返回值没有编码意图标记，保留预编码可能再次编码。例如原始值 `A&B <b>` 直接插值生成 `A&amp;B &lt;b&gt;`；先 `htmlspecialchars()` 再插值则会生成 `A&amp;amp;B &amp;lt;b&amp;gt;`。已编码的存量数据不会自动还原，应在明确其来源后迁移。

全部内置 Modifier 仍可用。末尾 HTML `|escape` 有专门的编码意图处理，可以保留；它与普通 `htmlspecialchars()` 的返回值不同。URL escape 的去重只适用于已有前缀的 HTML URL 属性组成部分，不能推广到任意上下文。修饰链先转换数据，再按实际位置编码。

完整 URL 仍需由业务代码正确构造，例如 `http_build_query()`、路径编码和签名编码不能批量删除。`<a href="{$url}">` 将值视为完整 URL，检查协议并执行 HTML 属性编码，不会替其中每个参数重新编码。JS 字符串中的 URL 仍需保留 URL 构造，再由模板执行 JS 字符串编码。URL 组成部分的空格采用 `%20`，与 `urlencode()` 的 `+` 不同，精确字节、缓存键与签名需核对。

#### 6.7 可信 HTML、属性与输出函数

只有已确认可信且适合当前位置的原文使用末尾 `|raw`：

```html
<article>{$document['html']|raw}</article>
<p {if $hidden}hidden{/if}>{$message}</p>
```

文档示例中的 `document['html']` 必须来自应用自己的安全渲染器，不能直接传用户输入。raw 会跳过最终编码，并继续影响后续的真实上下文；不要用它批量绕过迁移。

自动模式拒绝动态标签名、属性名及动态标记。不要写 `<p {$hidden ? 'hidden' : ''}>`，应像上例那样用条件输出完整静态属性。动态属性值仍可直接插值。

普通表达式、函数及 Stringable/JSON 转换回调必须返回数据，直接 echo、改变输出缓冲或嵌套渲染改变已选上下文会报错。需要输出模块 HTML 时使用模板 include/part；part 只能位于 HTML 文本中，并临时收集可信片段。模块异常时丢弃该失败片段的缓冲，之前已提交的页面内容保留；业务副作用不回滚。PHP 直接调用 `Tpl\Func::part()` 仍直接输出，不管理调用方缓冲。

#### 6.7 JSON 数据块与翻译

JSON 数据块直接传数组/对象，不要先 json_encode 后再把 JSON 字符串当作数据输出：

```html
<script type="application/json">{$messages}</script>
<script>const messages = {$messages};</script>
```

第一种输出标准 JSON，第二种输出带表达式括号的 JS 值。将已有 JSON 字符串直接插入数据块会得到 JSON 字符串，而不是原来的对象；优先在业务层保留结构化数据。

模板翻译应返回原文，让 Tpl 执行最终编码。samples 的模板已从 `SampleI18n::e()` 改为 `SampleI18n::t()`；PHP 直接拼接 HTML 的位置仍保留显式转义。`SampleI18n::url()` 构造完整 URL，`clientMessages()` 返回数组，由模板分别完成属性编码和 JSON 序列化。

#### 6.7 编译缓存与升级验证

编译格式升级为 v10，统一保留 `.context.php` 文件后缀。6.6 的上下文编译文件会自动重建；旧兼容文件不再执行。编译目录必须可写，常驻进程需重启，并按发布流程刷新 OPcache。缓存不保存变量或最终页面输出，完整计划命中后仍无需 JS 词法扫描。

大模板首次编译仍有分析成本，建议预热模板及所需上下文变体。上下文编码及校验仍有运行成本，不能将重复值编码复用的收益推广到所有模板；上线前分别评估首次编译与缓存命中情况。

升级时检查普通文本、input 值、实际链接、JSON/JS/CSS、可信 HTML、条件属性、include/part 及异常处理。samples 的官网、工作台、文档浏览器和独立模块均已迁移到默认自动转义，模板实验入口为 `/lab/template/entry`，新迁移指南可在 `/site/main/docs?doc=CHANGELOG.md#67-迁移指南` 阅读。

应用项目仍可管理自己的锁文件；框架仓库的 `composer.lock` 已加入 `.gitignore` 并停止跟踪，本地文件保留。完整输出上下文和支持边界见 [Tpl 手册](docs/templates.md#上下文自动转义)。

## 6.6.0 — 2026-11-18

本版新增可选的模板上下文自动转义，并调整 Cookie 默认策略、Db 内部类名及模板辅助函数。升级步骤与不兼容事项见 [6.6 迁移指南](#66-迁移指南)。

- 6.7 变更预告：Tpl 的 contextMode 将默认开启，并移除 `setContextMode()`、`getContextMode()`，不再支持调用这两个方法。6.6.0 仍默认使用兼容模式并保留这两个方法；升级前应在 6.6 中验证自动转义，迁移预编码数据与模板，升级到 6.7 时删除模式设置和读取调用。
- Tpl 自动转义迁移说明（仅对显式启用 `setContextMode(true)` 的模板生效）：HTML 输出可移除手工 `htmlspecialchars()`、`htmlentities()` 及末尾 HTML `|escape`，模板中独立 URL 组成部分可移除 `urlencode()`、`rawurlencode()` 或 `|escape:"url"`，统一传原始数据并按输出上下文编码。普通函数没有编码意图标记，保留预编码可能重复转义；末尾内置 `escape` 仍受支持，可供两种模式共用。默认兼容模式继续需要手工编码；完整 URL 构造、业务/签名编码不能盲删，URL 组件中的空格自动编码为 `%20`，与 `urlencode()` 的 `+` 不同。替换示例及边界见 [Tpl 自动转义不兼容说明](#66-tpl-自动转义迁移启用时不兼容)。
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

### 6.6 迁移指南

本文保留 6.6.0 的行为与迁移步骤；当前版本为 6.7.1，Tpl 已默认自动转义并移除模式接口，升级需继续阅读 [6.7 迁移指南](#67-迁移指南)。以下显式启用及兼容模式说明仅适用于 6.6。

6.6.0 新增可选的 Tpl 上下文自动转义与编译执行计划，默认继续使用兼容模式；同时迁移 Db 内部类及模板辅助函数，调整 Cookie 安全默认值，修复 Language 切换缓存并实现 Redis Sentinel。完整变更见 [Changelog](#changelog)。

**从 6.5.3 升级也需要检查旧类名、全局模板函数、自定义扩展和 Cookie 配置。** 自动转义需要逐实例显式启用；类名及 Cookie 默认策略的变化则不取决于是否启用自动模式。从更早版本升级，还需完成 [6.5 迁移](#65-迁移指南)及对应的历史迁移。

**后续 6.7 变更（现已生效）：Tpl 的 contextMode 默认开启，并移除 `setContextMode()`、`getContextMode()`，不再支持调用这两个方法。** 6.6.0 仍默认关闭自动转义，两个接口继续可用。建议先在 6.6 中开启自动模式，按本文迁移原始数据与模板；升级到 6.7 时删除所有模式设置和读取调用，包括原先的 `setContextMode(false)`，并将依赖兼容模式的模板迁移到自动转义规则。

#### 6.6 安装与部署

```bash
composer require hetao29/slightphp:^6.6.0
```

核心仍要求 PHP 8.0+；插件依赖继续[按需安装](docs/plugins.md#按需安装依赖)，Sentinel 模式需要 phpredis 6.0+。应用更新后重新生成 Composer 自动加载文件。部署时确保模板编译目录可写，旧编译文件会按格式标记自动重建；常驻进程需重启，OPcache 按部署流程刷新。

自动模式将静态分析及 JS 词法分析尽可能移至编译阶段，完整计划命中后不再执行 JS 词法扫描。大模板首次编译仍有明显成本，建议提前预热实际模板及所需上下文变体；编译缓存不保存模板变量或最终页面输出。

缓存与 OPcache 命中不意味着自动模式在所有场景都比原版快。重复的纯变量可在同一静态块内复用读取和编码；不同值、循环、复杂表达式和 JS/CSS 上下文仍有额外成本。默认兼容模式的缓存路径接近原版；对象及动态上下文保留受守卫的回退路径，不能套用原生数组/标量的性能结论。

#### 6.6 内部类与模板辅助函数迁移

数据库结果类与适配器分别从 `SlightPHP\DbData`、`SlightPHP\DbPDO` 迁移为 `SlightPHP\Db\Data`、`SlightPHP\Db\PDO`，文件位于 `plugins/Db/`。直接使用旧类名或文件路径的应用需更新；Db 查询接口及读取 COUNT 第一行的行为保留。

模板辅助函数由全局函数改为 `SlightPHP\Tpl\Func`、`SlightPHP\Tpl\Modifier` 的静态方法：

```php
\SlightPHP\Tpl\Func::include('child.tpl', $tpl);
\SlightPHP\Tpl\Func::part('/widgets/template/card/demo');
$safeHtml = \SlightPHP\Tpl\Modifier::escape($value);
```

分别替代 `tpl_function_include()`、`tpl_function_part()`、`tpl_modifier_escape()`；其余内置辅助函数同样去掉前缀后作为静态方法调用。删除旧文件的手动 require，使用 Composer PSR-4 自动加载，并重新生成自动加载文件。旧全局函数和旧 Db 类名不提供别名。已有编译模板会按文件头自动重建，部署用户需有重写权限。

不再通过 `tpl_function_*` / `tpl_modifier_*` 前缀查找自定义扩展。模板中的内置标签和修饰器写法继续可用；自定义扩展需改为同名普通 PHP 函数，并在应用中显式加载。例如 `{myhelper($value)}` / `{$value|myhelper}` 对应 `myhelper()`，不再隐式查找旧的前缀函数。自动模式的函数与修饰器应返回数据，最终编码及限制见[上下文自动转义](docs/templates.md#上下文自动转义)。

Tpl 默认继续使用兼容模式。启用 `setContextMode(true)` 后，Modifier 中的 raw、escape、default、tr、tostring、version 仍可用；普通修饰器先转换数据，再由输出位置自动编码，末尾 raw 表示最终可信原文。末尾 HTML escape 按实际上下文重新编码，URL escape 在已有前缀的 HTML URL 属性中避免组件重复编码；其它位置保留其转换结果。没有 json 内置别名。JS 位置及状态计划在编译阶段生成，动态新入口首次编译上下文变体，完整命中后不再词法扫描。Lexer 识别模板语法并保留普通 JS/CSS 花括号；语法完全相同的内容用 literal 或不同分隔符消歧。模板错误仍原样抛出，支持边界见[模板手册](docs/templates.md#上下文自动转义)。

自动模式现在会在首次编译时生成带状态守卫的静态块/编码计划，命中后按实际入口使用或回退；不缓存变量和最终输出。升级后需允许重写旧编译文件，常驻进程重启并按部署策略刷新 OPcache。不要跨模板插值拼写未完成的 HTML 实体或 JS 模板字符串插值语法，这些前缀会按字面量保护；正常数据的呈现含义保留。详见 [编译缓存与执行计划](docs/templates.md#编译缓存与执行计划)。

#### 6.6 Tpl 自动转义迁移（启用时不兼容）

**以下规则仅适用于显式开启上下文自动转义的实例。默认兼容模式不会自动编码，不能直接删除现有模板的手工转义。**

```php
$tpl = new \SlightPHP\Tpl();
$tpl->setContextMode(true);
```

自动模式应接收原始数据，由插值所在的 HTML 文本、属性、URL、JS、CSS 等受支持位置决定最终编码。原来仅用于输出防护的 `htmlspecialchars()`、`htmlentities()`、`urlencode()`、`rawurlencode()`、`|escape` 等通常不再需要，按位置迁移：

| 位置 | 旧写法 | 自动模式推荐写法 |
| --- | --- | --- |
| HTML 文本或带引号的普通属性 | `{htmlspecialchars($address['address'] ?? '')}` | `{$address['address'] ?? ''}` |
| URL 查询参数 | `<a href="/search?q={urlencode($q)}">` | `<a href="/search?q={$q}">` |
| URL 查询参数 | `<a href="/search?q={rawurlencode($q)}">` | `<a href="/search?q={$q}">` |

内置修饰器的对应替换：

```html
<!-- 迁移前 -->
<p>{$name|escape}</p>
<a href="/search?q={$q|escape:"url"}">搜索</a>

<!-- 显式开启自动模式后 -->
<p>{$name}</p>
<a href="/search?q={$q}">搜索</a>
```

普通 PHP 函数返回的是普通字符串，没有“已经编码”的意图标记。HTML 文本中，地址为 `A&B <b>` 时，保留 `htmlspecialchars()` 会得到源码 `A&amp;amp;B &amp;lt;b&amp;gt;`；改为直接插值后为 `A&amp;B &lt;b&gt;`。URL 参数中的预编码同样可能再次编码 `%` 或 `+`：`q = 'a b&'` 时，保留 `urlencode()` 会得到 `a%2Bb%2526`，直接插值则为 `a%20b%26`。业务层或数据库已经编码的数据也不会自动还原，迁移时应区分原始数据与预编码结果。

内置末尾 `|escape` 有专门的编码意图处理，仍可保留，适合同一份 HTML 文本/普通属性模板同时运行于兼容/自动两种模式；它没有被移除。URL 分支的去重限于已有前缀的 HTML URL 属性，不能据此认为任意上下文的 `|escape:"url"` 都可删除，或任何修饰链都不会重复编码。单独的 PHP `htmlspecialchars()`、`urlencode()` 等函数不享有这项特殊处理。

##### 6.6 完整 URL 与业务编码仍需保留构造逻辑

`<a href="{$url}">` 将变量视为完整 URL，只检查协议并做 HTML 属性编码，**不会重新编码其中每个路径或查询参数**。在业务层通过 `http_build_query()`、路径编码或拼接产生完整 URL 时，保留这些构造步骤；不要仅删除拼接表达式中的 `urlencode()` 后继续把结果当成完整 URL 输出。将原始查询参数独立放到模板中的 `/search?q={$q}`，才能由模板识别为 URL 组成部分。

自动判定依据插值所在的语法位置，不推断字符串的业务含义。例如 `<script>const target="/search?q={urlencode($q)}";</script>` 中的变量位于 JS 字符串，模板不会再把它识别为 URL 参数；这里仍需保留 URL 构造编码，之后由自动模式负责 JS 字符串编码，或者在业务层构造完整 URL 后传入模板。

用于显示编码文本、API 参数、签名或精确 URL 字节的转换也不属于输出防护，不能批量删除。自动 URL 组成部分采用 `rawurlencode()` 规则，空格为 `%20`；`urlencode()` 的空格为 `+`，切换可能改变签名、缓存键或依赖精确字节的断言。完整 URL 内已有的 `%xx` 不会自动解码。

不要用末尾 `|raw` 普遍绕过迁移：它会跳过当前上下文的最终编码，仅适用于已确认可信且适合当前位置的原文。建议先为相应实例开启自动模式，再按输出位置迁移模板，检查显示、表单值、实际链接、JS/CSS 及既有 URL 签名；保留兼容模式的模板继续使用原来的手工编码。完整支持边界见[上下文自动转义](docs/templates.md#上下文自动转义)。

#### 6.6 Cookie 默认策略与会话

`Response::cookie()` 省略安全选项时，现在采用 `Secure=true`、`HttpOnly=true`、`SameSite=Lax`。纯 HTTP 开发站点必须明确关闭 Secure，否则浏览器不会通过普通 HTTP 回传 Cookie：

```php
// HTTPS 生产环境。
\SlightPHP\Response::setCookieDefaults();

// 仅用于纯 HTTP 开发环境。
// \SlightPHP\Response::setCookieDefaults(secure: false);

// 启动会话前复用 Cookie 策略。
session_set_cookie_params(
    ['lifetime' => 0, 'path' => '/', 'domain' => '']
    + \SlightPHP\Response::getCookieDefaults()
);
session_start();
```

单次 `cookie()` 调用可显式覆盖默认值；需要浏览器脚本读取的特定 Cookie 应显式设置 `httponly: false`。跨站 Cookie 的 `SameSite=None` 必须与 `Secure=true` 同时使用，不合法组合抛出 `ValueError`。会话不会因设置 Response 默认值而自动更新，需在 `session_start()` 前配置；HTTPS 与可信反向代理的完整示例见 [生产部署](docs/deployment.md#cookie与会话安全)。不要直接信任客户端传来的 `Forwarded` / `X-Forwarded-Proto`。

#### 6.6 Language 语言切换与请求重置

`Language::setLocale()` 会清除候选语言缓存，首次翻译之后切换语言也立即生效；`setLanguageDir()` 会清除已加载词条，避免跨目录复用。新增 `resetRequest()`，清除强制语言及候选列表，保留应用配置与词典缓存：

```php
\SlightPHP\Language::resetRequest();
try {
    \SlightPHP\Language::setLocale('zh-cn');
    echo \SlightPHP\Language::tr('welcome');
} finally {
    \SlightPHP\Language::resetRequest();
}
```

串行常驻请求应在每次请求开始、设置本次语言前重置。该接口只重置 Language，不重置路由、会话或其他插件，也不提供同进程并发请求隔离。Config 及词典仍有进程内缓存，更新资源后按部署流程重启；完整边界见 [Language 插件](docs/plugins.md#language)。

#### 6.6 Redis Sentinel

`engine: "sentinel"` 现在使用 RedisSentinel 按监控服务名发现主节点，不再创建 RedisArray。原来使用该模式名但把数据节点地址当作 `host` 的配置必须迁移：`host` 应为 Sentinel 地址列表，`options.service` 是 Sentinel 监控的服务名；认证分别使用 `sentinel_auth` 和 `auth`。完整 JSON 配置见 [Redis 插件](docs/plugins.md#redis)。

连接后会验证主节点身份；断线或 READONLY 后，后续调用重新发现当前主节点，不重放失败命令。应用仍需判断失败并处理结果不确定的写入，不能依赖框架自动重试写命令。

#### 6.6 控制器构造与示例验证

核心现在在实例化控制器前确认入口存在且为 public。缺失或非公开入口不再执行构造函数，不能依赖构造函数为这些请求生成响应或产生副作用；正常入口、候选回退、请求类型及 Route HTTP 方法校验继续生效。

示例站点的页头、页脚及安装命令已更新为 6.6.0，模板及展示代码统一使用默认 `{` / `}`。`/lab/template/entry` 与 `/widgets/template/context` 展示两种模式、全部内置修饰器、HTML/URL/JS/CSS 编码、literal、缓存变量更新及错误处理；`/lab/language/entry` 展示三种语言切换。文档浏览器可直接访问 `/site/main/docs?doc=CHANGELOG.md#66-迁移指南`。生产使用仍需完成相应的 Cookie、会话和反向代理配置，见[部署说明](docs/deployment.md)。

## 6.5.3 — 2026-11-16

- Language 语言文件由 INI 改为 JSON/JSONC，与 Route 一样使用 `Config::parse()`；按 `locale/zone.json`（优先）或 `locale/zone.jsonc` 加载，顶层须为对象且所有词条值须为字符串。保留语言选择、词条回退及目录边界校验，格式和结构错误抛出异常。旧 INI 文件需转换内容，不能仅改扩展名；samples 的简体、繁体、英文词条已同步转换。见 [Language 说明](docs/plugins.md#language)。
- 官网与交互工作台支持简体、繁体和英文，界面词条与客户端提示共用 Language 资源；语言选择记入 Cookie。主题增加默认跟随系统的「自动」模式，并保留手动深浅色偏好。
- 导航新增完整 docs 阅读与 ChangeLog 页面，直接读取仓库文档和生成的 API 参考；「使用指南」更名为「入门」，位于首页之后。
- samples 增加 Language 多语言实验，展示三种语言的 JSON 词条、动态称呼、缺失词条回退和可复制 PHP 代码；入口加入示例索引、插件卡片与工作台侧栏。功能特性、调度流程和应用职责合并到首页，旧功能页跳转至首页区域。

### 6.5 迁移指南

升级到当前版本还需完成 [6.7 迁移](#67-迁移指南)，Tpl 现在统一自动转义并移除模式接口。

本文记录 6.5.x 的历史变更；当前版本为 6.7.1，升级时还需阅读 [6.6 迁移指南](#66-迁移指南)，处理类名、模板辅助函数及 Cookie 默认策略变化。

6.5.3 将 Language 语言文件由 INI 改为 JSON/JSONC；从 6.5.2 升级也需要转换现有语言文件，详见 [Language 迁移](#65-language-语言文件改为-jsonjsonc653)。官网与 samples 同步支持三种语言、默认自动的主题和完整文档阅读。

6.5.2 新增请求类型前缀校验。从 6.5.1 升级也需检查带类型前缀的入口、内部调用及 405 响应处理，具体见[请求类型前缀校验](#65-请求类型前缀校验652)。

6.5.1 移除 `SlightPHP\Json` 插件，Route 文件配置改为 JSON/JSONC 数组，并将插件依赖改为 Composer `suggest`，由应用按需安装。从 6.5.0 升级也需要替换 JSON 插件调用、转换 INI 路由配置并显式声明所用插件的依赖。

6.5.0 新增 Route 方法注解，核心失败统一抛出结构化异常，完善 PHP / INI 自定义路由，并优化候选查找、应用目录校验及请求内元数据缓存。官网为 [https://slightphp.com/](https://slightphp.com/)。完整记录见 [Changelog](#changelog)。

**从 6.4.x 升级也需要修改调度失败处理。** 旧的 `^6.4`、`^6.3`、`^6.2` 约束均允许解析到 6.5.0，不能据此认为原有的 `false` 失败判断仍然适用。从 6.3.x 或更早版本升级时，还需完成 [6.4 模板迁移](#64-迁移指南)；旧控制器签名的迁移见 [6.3 迁移指南](#63-迁移指南)。

#### 6.5 安装

当前安装仅强制要求 PHP 8.0+；PDO、Redis 扩展、Guzzle 和 Gregwar Captcha 已改为按需安装。使用相关插件的应用应先按[插件指南](docs/plugins.md#按需安装依赖)将所需依赖加入自己的 `require`，再更新框架：

```bash
composer require hetao29/slightphp:^6.5.3
```

仓库自身使用 `composer install` 安装依赖；6.7 起不再跟踪框架仓库的 `composer.lock`，应用项目仍可管理自己的锁文件。此版本不新增 APCu 等扩展要求。

#### 6.5 Language 语言文件改为 JSON/JSONC（6.5.3）

6.5.3 的 `Language::tr()` 与 Route 一样使用 `Config::parse()`，不再读取 `locale/zone.ini`。将原 INI 词条转换成 JSON 对象，保存到相同语言目录中的 `locale/zone.json` 或 `locale/zone.jsonc`；不能仅修改文件扩展名。例如，`languages/en/main.json`：

```json
{
  "welcome": "Welcome to SlightPHP",
  "任务已添加": "Task added"
}
```

顶层必须为对象，所有词条值必须为字符串；原有数字、布尔值等内容如需作为翻译，应显式写为字符串。空对象和空字符串有效。`.json` 使用严格 JSON，`.jsonc` 允许行注释和块注释；都不支持尾随逗号。同名文件优先使用 `.json`，不与 `.jsonc` 合并。

`setLanguageDir()`、`setLocale()`、`tr($source, $zone)` 的调用方式保持不变。语言列表、主语言回退、缺失词条返回原文及目录边界校验保持不变；首次翻译前应设置语言目录和 locale。6.6.0 进一步修复 setter 的缓存失效行为并新增 `resetRequest()`，见 [6.6 迁移指南](#66-language-语言切换与请求重置)。JSON 格式错误抛出 `JsonException`，非对象顶层或非字符串词条抛出 `InvalidArgumentException`，无法读取已有文件时抛出 `RuntimeException`，应修复资源文件，不能依赖解析失败后返回原文。

Language 的已加载词条仍保留在进程内；首次加载文件也受 `Config::$CACHE` 影响。发布时同步部署所有语言文件，常驻进程按发布流程重启。完整格式见 [Language 插件](docs/plugins.md#language)，可运行示例见 `/lab/language/entry`。

samples 已将简体、繁体、英文资源转换为 JSON。官网的功能特性合入首页，旧 `/site/main/features` 跳转至首页功能区域；主题默认自动跟随系统，文档和 ChangeLog 直接读取仓库内容。应用自行使用框架时，只需迁移所使用的 Language 资源，无需采用 samples 的界面与导航。

#### 6.5 请求类型前缀校验（6.5.2）

候选方法名以 `setAllowRequestTypes()` 配置的类型开头时，当前 `Request-Type` 必须匹配。缺失或不匹配会立即抛出 `SlightPHP\Exception::METHOD_NOT_ALLOWED`（405），先于方法存在性、Route 注解与控制器构造，也不会继续尝试浅层候选。普通 HTTP 请求、显式 URL、`run('/...')` 与模板 `part` 均执行此校验。

例如，原来不带请求头直接访问 `/user/order/ajaxSave` 的调用会被拒绝；应使用 `/user/order/save` 并发送 `Request-Type: ajax` 来调用 `ajaxSave`。内部调用和 CLI 不会自动补充请求类型，模板 `part` 沿用当前请求头，升级时也需核对。

类型列表统一转为小写并过滤空字符串；请求头先去除首尾空白再转为小写。前缀按配置顺序首匹配，不要求单词边界，因此也需检查名称恰好以 `page`、`json` 等已配置类型开头的普通入口。自定义类型重叠时将较长类型排在前面；从列表移除某类型也会移除对应的前缀校验。该校验不代替 HTTP 方法、登录、对象权限或 CSRF 校验。

请求类型不匹配时，异常上下文包含 `requestType`、`requiredRequestType`，而 `allowedMethods` 为空数组；入口应仅在 `allowedMethods` 非空时输出 HTTP `Allow` 头，不要把 `ajax`、`part` 等类型写入该头。下面的入口示例已包含判断；详细规则见[方法前缀](docs/framework.md#方法前缀与-http-方法)与[异常说明](docs/exceptions.md)。

#### 6.5 JSON 插件移除

6.5.1 删除 `plugins/Json.php` 和 `SlightPHP\Json` 类，不保留兼容别名。移除 `use SlightPHP\Json;` 和手动引入该文件的代码，改为直接调用 PHP 原生函数：

| 原调用 | 原生替代 |
| --- | --- |
| `Json::encode($value, $flags, $depth)` | `json_encode($value, $flags, $depth)` |
| `Json::decode($json, $assoc, $depth, $flags)` | `json_decode($json, $assoc, $depth, $flags)` |
| `Json::error()` | `json_last_error()` |
| `Json::errorMsg()` | `json_last_error_msg()` |

原插件只是原生函数的封装，替换时保留现有参数、flags、depth 和错误处理即可。原 `decode()` 的 `$assoc` 默认值为 `false`；需要保留对象解码行为时可显式传入 `false`，尤其是同时使用 `JSON_OBJECT_AS_ARRAY` 时。

```php
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => '你好'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
$data = json_decode('{"ok":true}', true, 512, JSON_THROW_ON_ERROR);
```

`JSON_THROW_ON_ERROR` 模式下编解码错误抛出 `\JsonException`，由应用捕获处理；未启用时使用 `json_last_error()` / `json_last_error_msg()` 查询错误。升级时同时检查控制器、模板修饰器、CLI 任务与测试中的旧类引用。

#### 6.5 路由配置改为 JSON/JSONC

6.5.1 的 `Route::setConfigFile()` 不再解析 INI，改为调用 `Config::parse()`；PHP 的 `Route::set()` 和方法注解 API 保持不变。

将原文件的每个 INI 节转换为一个 JSON 对象，再按原顺序放入顶层数组，并将加载路径改为 `.json` 或 `.jsonc`：

```json
[
  {
    "pattern": "^\\/r-json\\/:slug$",
    ":slug": "[A-Za-z0-9_]{1,40}",
    "zone": "lab",
    "page": "route",
    "entry": "entry"
  }
]
```

```php
Route::setConfigFile(__DIR__ . '/../config/routes.json');
```

保留 `pattern`、`:name`、`zone`、`page`、`entry` 字段，JSON 字符串中的反斜杠需要双写。顶层对象（包括命名分组或 `{ "routes": [...] }`）和数组中的非对象元素均不接受。多文件仍按加载顺序追加，首条匹配规则优先，所有规则校验通过后才追加。

INI 的常量或环境变量插值不会被 JSON 自动执行；这类动态规则可以继续使用 PHP 的 `Route::set()`。JSON 语法错误现在抛出 `JsonException`，不再统一为 INI 解析的 `RuntimeException`；`.ini` 扩展名会被拒绝。Config 默认缓存且不检查文件修改时间，常驻进程应只注册一次并在配置发布后重启。完整规则与缓存说明见[自定义路由文档](docs/routes.md#jsonjsonc-规则)。

#### 6.5 核心失败改为异常

将 `$result === false` 的调度失败分支改为捕获 `SlightPHP\Exception`。应用入口负责状态码、响应头和错误正文，核心不会自动生成 404/405/500 页面：

```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
SlightPHP::setDefaultZone('home');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
SlightPHP::setPathInfo(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

try {
    $result = SlightPHP::run();
    if (is_string($result)) {
        echo $result;
    }
} catch (\SlightPHP\Exception $error) {
    http_response_code($error->getStatusCode());
    if ($error->getCode() === \SlightPHP\Exception::METHOD_NOT_ALLOWED && !empty($error->getContext()['allowedMethods'])) {
        header('Allow: ' . implode(', ', $error->getContext()['allowedMethods']));
    }
    echo $error->getStatusCode() === 404 ? '页面不存在' : '请求失败';
}
```

`getCode()` 是原因码，`getStatusCode()` 是建议的 HTTP 状态码；路径、控制器和允许方法等诊断信息通过 `getContext()` 读取。不要直接向客户端展示包含文件路径的异常消息。完整原因码见 [核心异常与处理](docs/exceptions.md)。

需要检查的调用点包括：

- 主入口、CLI 任务以及 `run('/...')` 内部调用。
- 模板 `part`：未匹配入口和重复内部调用现在抛出异常，可选模块需由调用方按原因码处理；空路径仍不调用入口。
- `getZoneAlias()`、`getPageAlias()`：别名未设置时抛出 `ALIAS_NOT_FOUND`，有效别名仍返回原字符串。
- 超出路径深度、应用目录无效及控制器文件/类/方法不可用等情况。

业务入口主动返回的 `false` 仍是正常业务返回值；响应通过 `end()` / `redirect()` 结束后，调度返回 `null`。这两种结果均不能作为调度失败标志。控制器文件、构造函数、入口及注解构造函数自身的异常原样传播。

#### 6.5 Route 方法注解

在真实入口上声明允许的 HTTP 方法：

```php
namespace Home;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

class MainController
{
    #[Route(method: 'POST')]
    public function Save(Request $request, Response $response)
    {
        return $response->end('saved');
    }
}
```

也可使用 `#[Route(methods: ['GET', 'POST'])]`；`method` 与 `methods` 同时设置时取并集，统一为大写并去重。未声明注解或未指定方法列表时不限制 HTTP 方法。GET 不自动允许 HEAD，CLI 未提供 HTTP 方法时会被有限制的入口拒绝。

限制在构造控制器之前检查，不匹配时抛出 `METHOD_NOT_ALLOWED`，由入口返回 405 和 Allow。继承方法使用实际声明上的注解，子类覆盖按覆盖方法处理；Request-Type 前缀使用实际调用方法上的注解。CSRF、权限和业务校验仍由应用负责。

#### 6.5 自定义 URL 路由

静态 `Route::set()` 和 `setConfigFile()` 继续可用，与方法注解共用 `Route` 类。规则只匹配路径，忽略查询串；重复前导斜杠不会被当作主机名。占位符按完整名称替换；6.5.1 的 JSON/JSONC 数组按加载顺序追加，不覆盖先前规则。

新增公开的 `Route::parse(?string $path = null)`。入口若已剥离 `index.php` 或站点前缀，应在注册规则后调用 `Route::parse($path)`，匹配规范化后的实际路径。配置不可读、JSON 解析失败、规则或正则无效时抛出明确异常；检查已有配置，尤其是空 pattern 和占位符约束类型。完整示例见 [自定义 URL 路由](docs/routes.md)。

#### 6.5 调度优化与缓存边界

普通候选文件缺失使用分支继续查找，类或入口不可用时只记录诊断，最终失败才构造异常。普通文件缺失不再产生中间异常链，文件越界等校验仍保留原因。候选回退不会吞掉业务异常。

应用目录在设置或首次使用时按目录路径规范化并缓存，调度不再重复执行 `is_dir()`；候选仍需通过普通文件与目录边界检查。方法元数据先按实际控制器和入口查缓存，未命中才反射，无注解和非公开方法也缓存；HTTP 方法是否允许仍逐次判断，无效注解不会被缓存为无限制。

**普通 PHP-FPM 的元数据缓存只在当前请求内复用，不会跨请求保留。** 本版本没有引入持久化注解缓存。常驻进程应按发布流程重启以加载新代码，OPcache 刷新也由发布流程管理。

#### 6.5 升级验证

- 检查正常入口、缺失入口、路径超限、重复内部调用和缺失别名的处理。
- 验证 GET/POST/HEAD、继承与覆盖，以及 Route HTTP 方法不匹配时 405 的 Allow 响应。
- 验证匹配、缺失和不匹配的 Request-Type，覆盖显式 URL、内部调用、CLI 与模板 part；确认类型拒绝时不构造控制器、不回退且不输出空 Allow 头。
- 验证包含查询串的 PHP / JSON/JSONC 路由、重复斜杠和多文件规则顺序。
- 检查可选模板模块的异常处理，以及原始业务异常的传播。

部署仓库示例后，通过浏览器验证真实入口、自动加载、模板与 URI 解析；具体步骤见[部署文档](docs/deployment.md#验证)。

#### 6.5 内部类与模板辅助函数迁移

此项变更归入 6.6.0，详见 [6.6 类名与辅助函数迁移](#66-内部类与模板辅助函数迁移)。

#### 6.5 Tpl 自动转义迁移

上下文自动转义从 6.6.0 开始提供，默认关闭；启用后的手工编码替换规则见 [6.6 自动转义迁移说明](#66-tpl-自动转义迁移启用时不兼容)。

## 6.5.2 — 2026-11-16

- 核心在候选方法名赋值后立即核对已配置的请求类型前缀与 `HTTP_REQUEST_TYPE`，缺失或不匹配时在方法存在性、Route 注解及控制器构造前抛出 `METHOD_NOT_ALLOWED`（405），不继续候选回退。显式 URL 和内部调度均执行校验；`setAllowRequestTypes()` 统一保存小写类型并过滤空字符串，前缀按配置顺序首匹配，命中后立即校验并退出循环。应用须为带类型前缀的方法发送匹配请求头，仍保留 HTTP 方法、登录、对象权限及 CSRF 校验。
- 请求类型错误的诊断上下文包含 `requestType`、`requiredRequestType` 和空的 `allowedMethods`；示例入口仅在 Route HTTP 方法列表非空时输出 `Allow`，避免生成空头。见[方法前缀](docs/framework.md#方法前缀与-http-方法)和[异常处理](docs/exceptions.md)。

## 6.5.1 — 2026-11-16

- `Route::setConfigFile()` 改为通过 `Config::parse()` 加载 JSON/JSONC 顶层数组，每个对象为一条规则，按数组顺序追加并匹配；不再支持 INI。保留 PHP 规则、占位符、方法注解和首条匹配优先的行为，示例改为 `routes.json`，旧配置需按[路由迁移说明](#65-路由配置改为-jsonjsonc)转换。
- 移除 `SlightPHP\Json` 插件及 `plugins/Json.php`，JSON 编解码与错误查询改用 PHP 原生 `json_encode()`、`json_decode()`、`json_last_error()` 和 `json_last_error_msg()`。不保留兼容类，已有调用需迁移；示例、官网插件清单与 API 参考同步更新，见 [6.5 迁移指南](#65-json-插件移除)。
- Composer 强制依赖仅保留 PHP >=8.0；数据库、Redis、HTTP 和验证码插件依赖改为 `suggest`，默认安装不再拉取 Guzzle、Gregwar Captcha 及其传递依赖。使用这些插件的应用需显式声明所需依赖，见[按需安装依赖](docs/plugins.md#按需安装依赖)。

## 6.5.0 — 2026-11-15

- 官网地址统一为 [https://slightphp.com/](https://slightphp.com/)，更新安装示例、API 参考与 [6.5 迁移指南](#65-迁移指南)。
- Route 方法元数据先按控制器和入口查缓存，未命中才反射；无注解和非公开方法也缓存，继承/覆盖按实际控制器隔离，HTTP 方法匹配仍逐请求判断。
- 移除调度与候选解析中的 `is_dir()` 查询；应用目录规范化时通过目录路径解析校验类型，候选仍检查普通文件及应用目录边界。
- 优化候选控制器查找：普通文件缺失使用分支继续回退，类或入口不可用时只记录诊断，最终调度失败才构造并抛出异常；保留文件越界校验、HTTP 方法限制和业务原始异常传播。
- samples 错误页渲染时临时禁用 Request-Type 方法前缀并在结束后恢复，避免携带 ajax 等请求头的失败请求在 404/500 处理中再次抛出入口不存在异常。
- 修复 `Route::parse()` 将 `//articles/42` 的第一段误当作主机名、导致匹配丢失或误命中 `/42` 的问题；提取请求路径时保留重复前导斜杠，继续排除查询串。
- 自定义 URL 路由只匹配路径、不再受查询串影响；修复前缀相同的占位符相互替换和跨 INI 文件同名节覆盖已有规则的问题。公开 `Route::parse($path)` 支持显式匹配规范化路径；无效配置提供明确异常。samples 增加 PHP / INI 短地址、多参数和方法限制示例，见 [自定义 URL 路由](docs/routes.md)。
- 不兼容变更：核心调度失败统一抛出 `SlightPHP\Exception`，提供原因码、诊断上下文和建议 HTTP 状态；路径缺失/超深、应用目录无效、控制器文件/类/方法不可用、文件越界、重复内部调用和缺失别名不再返回 `false`。业务入口主动返回的 `false` 仍保留；候选控制器查找和原始应用异常传播不变。迁移见 [核心异常与处理](docs/exceptions.md)。
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

模板调用 `Tpl::assign()`、`Tpl::fetch()` 需改为复用同一个实例，不再支持从外部访问 `Tpl::$_tpl_vars`。直接调用 `tpl_function_include()` 时需增加第二个实例参数；旧编译缓存需可重写，常驻进程需重启。详见[模板迁移说明](#64-模板变量与实例)。

PHP 代码通过 `tpl_function_part()` 得到 HTML 字符串；需要控制器原始非字符串结果时改用 `SlightPHP::run()`。片段的响应状态和响应头由外层页面统一管理。

### 6.4 迁移指南

升级到当前版本还需完成 [6.7 迁移](#67-迁移指南)，Tpl 现在统一自动转义并移除模式接口。

本文记录 6.4.0 的模板迁移；升级到当前 6.7.1 还需按 [6.5 迁移指南](#65-迁移指南)处理调度异常与方法注解，并按 [6.6 迁移指南](#66-迁移指南)调整类名、辅助函数和 Cookie 默认策略。6.4.0 将 Tpl 模板变量改为实例属性，`assign()`、`fetch()` 改为实例方法，不同对象之间隔离模板数据。这是不兼容变更，已有 6.3.x 应用也需检查静态调用、直接访问变量表的代码及自定义模板函数。完整版本记录见 [Changelog](#changelog)，接口说明见[模板插件](docs/plugins.md#tpl)。

控制器继续沿用 6.3 的 Request / Response 参数。从 6.2.x 或更早版本升级时，还需完成 [6.3 迁移指南](#63-迁移指南)中的控制器签名和数据库迁移；更早版本的基础变更见 [6.2 升级说明](#62-迁移指南)和 [6.0 迁移指南](#60-迁移指南)。

#### 6.4 安装

PHP 和依赖要求保持为 PHP 8.0+、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha。在应用项目中更新依赖：

```bash
composer require hetao29/slightphp:^6.4
```

仓库自身使用 `composer install` 安装依赖；6.7 起不再跟踪框架仓库的 `composer.lock`，应用项目仍可管理自己的锁文件。旧的 `^6.3`、`^6.2` 约束也允许解析到 6.4.0，更新依赖时应一并完成下面的模板迁移。

#### 6.4 模板变量与实例

`Tpl::$_tpl_vars` 改为受保护的实例属性，`assign()` 和 `fetch()` 改为实例方法。将原有静态赋值及加载调用改为同一个对象：

```php
// 升级前
\SlightPHP\Tpl::assign('title', '示例');
\SlightPHP\Tpl::fetch('index.tpl');

// 升级后，模板目录、编译目录及分隔符仍通过静态配置设置。
$tpl = new \SlightPHP\Tpl();
$tpl->assign('title', '示例');
$tpl->fetch('index.tpl');
// 也可由 render 初始化默认配置并分配变量。
$tpl->render('index.tpl', ['title' => '示例']);
```

直接写入公开静态变量表的代码需改用 `$tpl->assign()`；不再提供从外部读取变量表的接口。检查自定义子类覆盖的 `assign()`、`fetch()` 是否仍声明为 static，以及是否直接使用 `self::$_tpl_vars`。依赖不同对象共享变量的业务需改为复用同一个实例或显式传递数据。

模板中的 `{include "child.tpl"}` 和 `{include $child}` 写法保持不变，编译器自动传入当前实例，多层子模板共享父模板数据。PHP 代码直接调用辅助函数时改为 `\SlightPHP\Tpl\Func::include('child.tpl', $tpl)`；自定义模板函数应显式接收或持有需要的实例。同一个实例在连续调用 `assign()`、`render()`、`display()`、`fetch()` 时保留变量，包括模板循环产生的赋值；常驻进程按请求创建新实例。

旧编译缓存缺少新格式标记，首次使用时会自动重新编译，无需逐个修改缓存文件。目录布局及模板修改时间检查保持不变。目录、分隔符、安全模式和强制编译选项仍为所有实例共享的静态配置。

#### 6.4 part 的输出与缓冲

从 6.4.1 起，`\SlightPHP\Tpl\Func::part($path)` 直接输出模块内容，再输出控制器返回的字符串，函数自身返回 null。6.4.0 曾收集并返回 HTML 字符串，依赖该返回值的调用点需要调整。part 不再开启或管理输出缓冲；模块自行开启的缓冲应自行配对关闭，包括异常分支。空路径不调用入口；升级到 6.5.0 后，未匹配入口时会抛出调度异常，见[异常迁移](docs/exceptions.md)。布尔值、数字、数组或对象不作为 HTML 输出。需要控制器原始结果时应改用 `SlightPHP::run()`；需要收集完整 HTML 的调用方应自行管理输出缓冲。

模块调用 `Response::end()` 后，片段输出保留，父页面继续渲染。控制器或模板的异常原样抛出，已经输出的部分内容保留；调用方可捕获异常后继续渲染。调用方式、变量作用域及完整 samples 示例见 [Tpl 模板与模块组合手册](docs/templates.md)。

#### 6.4 部署与验证

部署时确保运行用户能够重写旧编译文件，并重启常驻进程以加载新的类定义。启用 OPcache 时，由发布流程管理字节码缓存刷新或 PHP 工作进程重启。

- 搜索并迁移 `Tpl::assign()`、`Tpl::fetch()`、`Tpl::$_tpl_vars`，检查子类覆盖的方法和自定义模板函数。
- 验证不同实例之间变量隔离，同一个实例连续渲染时保留已分配变量。
- 验证静态及动态文件名的多层 include，确认父子模板共享变量。
- 验证模板内渲染另一实例时外层变量保持原值，以及旧编译缓存自动重建。
- 验证 part 的直接输出、字符串返回、混合输出及异常分支；检查自定义代码对原始返回值的依赖。

部署仓库示例后，通过浏览器验证真实入口、自动加载、模板与 URI 解析；具体步骤见[部署文档](docs/deployment.md#验证)。

## 6.3.0 — 2026-11-14

### 新增与不兼容变更

- 新增 `SlightPHP\Request` / `Response`，提供 PHP 请求快照、原始正文、响应头、状态码、Cookie、正文输出、结束及重定向接口；`$request->route` 对应原 `$inPath` 的完整路径数组。
- 调度直接按 `($request, $response)` 的顺序调用入口，参数名不限；不使用反射。构造控制器时传入同一个 Request，构造前已设置完整 `route`；移除构造函数和入口的 `$inPath` 数组传参，原签名须迁移。响应结束后 `run()` 返回 `null`，防止现有入口重复输出。
- Request/Response 统一通过 Composer 的 `SlightPHP\` PSR-4 映射按需加载，移除调度中的手动文件加载。

### 升级注意事项

旧的 `__construct(array $inPath)`、`Entry(array $inPath)` 以及未声明类型但按数组使用 `$inPath` 的控制器都需要调整；手动创建控制器和直接调用入口的代码也需同步。`end()` / `redirect()` 不会退出 PHP，调用后应从控制器返回。完整步骤及前后对照见 [6.3 迁移指南](#63-迁移指南)。

### 6.3 迁移指南

升级到当前版本还需完成 [6.7 迁移](#67-迁移指南)，Tpl 现在统一自动转义并移除模式接口。

6.3.0 将控制器原有的 `$inPath` 数组传参改为 `SlightPHP\Request`，并向入口方法传入 `SlightPHP\Response`。这是不兼容变更，已有 6.2.x 应用也需要检查控制器和直接调用它们的代码。完整版本记录见 [Changelog](#changelog)，接口说明见 [Request 与 Response](docs/http.md)。

从 6.0 / 6.1 升级时，还需完成 [6.2 升级说明](#62-迁移指南)中的配置、路径、模板、验证码和日志迁移；5.x 应用需先完成 [6.0 迁移指南](#60-迁移指南)。从 6.2.0 直接升级时，也需检查本文的数据库变化。

本文记录 6.3.0 的变化。升级到当前 6.7.1 时，还需按 [6.4 迁移指南](#64-迁移指南)调整模板变量及静态调用，并按 [6.5 迁移指南](#65-迁移指南)处理调度异常，并按 [6.6 迁移指南](#66-迁移指南)调整类名、辅助函数和 Cookie 默认策略；下方 `^6.3` 约束也允许安装 6.7。

#### 6.3 安装与自动加载

PHP 和依赖要求保持为 PHP 8.0+、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha；插件附加要求见[插件指南](docs/plugins.md)。在应用项目中更新依赖：

```bash
composer require hetao29/slightphp:^6.3
```

仓库自身使用 `composer install` 安装依赖；6.7 起不再跟踪框架仓库的 `composer.lock`，应用项目仍可管理自己的锁文件。应用入口继续加载 `vendor/autoload.php`；Request / Response 由 Composer 的 `SlightPHP\` PSR-4 映射按需加载，仅手动引入 `SlightPHP.php` 不足以支持新的调度。部署时同步应用代码、依赖和 Composer 自动加载文件，并重启常驻进程。

注意：旧的 `^6.2` 约束允许解析到 6.3.0，不能据此认为控制器签名仍兼容。更新依赖时应一并完成下面的代码迁移。

#### 6.3 控制器构造函数与入口

升级前：

```php
<?php
namespace User;

class ProfileController
{
    private array $route;

    public function __construct(array $inPath)
    {
        $this->route = $inPath;
    }

    public function Edit(array $inPath): string
    {
        return '用户 ID：' . htmlspecialchars($inPath[3] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
```

升级后：

```php
<?php
namespace User;

use SlightPHP\Request;
use SlightPHP\Response;

class ProfileController
{
    private array $route;

    public function __construct(Request $request)
    {
        $this->route = $request->route;
    }

    public function Edit(Request $request, Response $response): string
    {
        return '用户 ID：' . htmlspecialchars($request->route[3] ?? '', ENT_QUOTES, 'UTF-8');
    }
}
```

`/user/profile/edit/123` 的路由仍为 `['user', 'profile', 'edit', '123']`。路径分段规则、默认段补全、深度限制和 URL 无需因本次签名变化而调整；多层控制器的入口及参数下标仍随目录深度变化。

框架通过 `new $classname($request)` 构造控制器，再按 `($request, $response)` 的顺序调用入口。构造前 `route` 已填入，构造函数和入口接收同一个 Request；Response 在调用入口前创建，仅传给入口。参数名不限，框架不根据参数名或类型进行依赖注入。

修改时同时检查父类构造函数、继承的入口、带 Request-Type 前缀的入口（如 `AjaxEdit`）、CLI 任务、内部调用和测试桩。`array $inPath` 会因收到 Request 而报类型错误；仅重命名变量或删除 `array` 类型仍不足以迁移按数组访问的代码。异常会向外抛出，不会自动转换为 `run() === false`。

手动创建控制器或直接调用入口时，也应创建并传入对象，而不是路径数组：

```php
$request = new \SlightPHP\Request();
$request->route = ['user', 'profile', 'edit', '123'];
$controller = new \User\ProfileController($request);
$result = $controller->Edit($request, new \SlightPHP\Response());
```

直接调用方法不会经过 `run()` 的响应结束处理，返回值由调用方自行处理。

#### 6.3 请求数据读取

| 原用法 | 6.3.0 用法 |
| --- | --- |
| `$inPath` / `$inPath[3]` | `$request->route` / `$request->route[3]` |
| `$_GET` / `$_POST` | `$request->get` / `$request->post` |
| `$_REQUEST` | `$request->request`，保留 PHP 配置决定的合并来源、顺序及数组语义 |
| `$_COOKIE` / `$_FILES` | `$request->cookie` / `$request->files` |
| `$_SERVER['REQUEST_METHOD']` | `$request->getMethod()` 或 `$request->server['request_method']` |
| `$_SERVER['HTTP_CONTENT_TYPE']` / `$_SERVER['CONTENT_TYPE']` | `$request->header['content-type'] ?? null` |
| `file_get_contents('php://input')` | `$request->getContent()` 或 `$request->rawContent()` |

超全局变量仍可使用；Request 保存的是构造时的快照，修改对象中的数组不会更新超全局变量，构造后再修改超全局变量也不会更新已有快照。`server` 的键统一为小写，`header` 的键统一为小写并使用连字符。

JSON 正文不会自动进入 `post`，需先检查 `getContent()` 是否为 `false`，再自行解码和校验。路径数组不含查询串且不做 URL 解码。`run('/user/profile/edit/123')` 创建的 Request 仍读取当前 PHP 请求数据，只将 `route` 设置为显式路径，不会为内部调用合成新的 GET、POST 或请求正文。

#### 6.3 响应输出与返回值

返回字符串并由应用入口输出的旧流程可以继续使用，迁移签名后无需同时改写全部响应。使用 Response 输出时，应先设置状态码、响应头与 Cookie，再输出正文。例如入口方法可改为：

```php
public function Edit(\SlightPHP\Request $request, \SlightPHP\Response $response): bool
{
    $response->header('Content-Type', 'application/json; charset=utf-8');
    return $response->end(json_encode([
        'id' => $request->route[3] ?? null,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}
```

同步检查入口原有的返回类型：例如从返回字符串改为 `return $response->end(...)` 时，返回类型应相应调整为 `bool` 或移除旧声明。

| 控制器行为 | `SlightPHP::run()` 的返回值 |
| --- | --- |
| 未结束 Response，包括仅调用 `write()` | 控制器原始返回值 |
| 成功通过 `end()` 或 `redirect()` 结束 Response | `null` |
| 找不到可调用入口，或路径超出深度限制 | `false` |

升级到 6.5.0 后，应用入口应捕获 `SlightPHP\Exception` 处理调度失败，使用 `is_string($result)` 判断是否输出返回正文。控制器返回的 `false` 属于正常业务结果，已经结束响应时返回 `null`；两者均不表示调度失败。完整入口示例见[快速开始](docs/getting-started.md)及[异常迁移](docs/exceptions.md)。

`end()` 和成功的 `redirect()` 只标记本实例结束，不调用 `exit`，也不停止后续 PHP 语句；使用 `return $response->end(...)` 或 `return $response->redirect('/login')` 结束控制器执行。`write()` 不标记结束，混用 `write()` 和返回同一正文会导致重复输出。响应开始输出非空正文后，不能再通过该实例修改响应头、状态码或 Cookie；响应操作失败会返回 `false`，应按业务需要处理。

#### 6.3 数据库调用

以下变化分别在 6.2.1 和 6.2.2 引入，从这些版本升级也应确认应用已适配。

##### 6.3 6.2.1：移除自动重试

`Db::query()` 现在只接受 `$sql, $params = []`。移除调用中的第三个 `$retry` 参数，包括位置参数和命名参数 `retry:`；自定义子类也应检查覆盖的方法签名。

```php
// 升级前
$rows = $db->query('SELECT * FROM t_user WHERE pk_user = ?', [$id], 1);
// 升级后
$rows = $db->query('SELECT * FROM t_user WHERE pk_user = ?', [$id]);
```

查询失败会保留错误信息、触发 `E_USER_WARNING` 并返回 `false`，不会自动重连或重试；`select()`、`selectOne()` 及增删改操作同样不再依靠自动恢复。应用需明确检查失败结果，并按任务语义决定是否重新建立连接、恢复会话设置或重试完整事务；连接重建不能恢复已有事务和锁，写入结果不确定时应先核实实际结果。

直接使用数据库适配器（现为 `SlightPHP\Db\PDO`，原 DbPDO）的代码需移除对 `$connectionError` 的依赖；该公开标记已删除，改为检查操作返回值，并通过 `errno()` / `error()` 读取错误。Db 的错误协议保持不变，成功查询不保证清空以前的错误，应优先依据本次返回值判断是否成功。

##### 6.3 6.2.2：查询锁参数

`select()` 和 `selectOne()` 当前参数顺序均为 `table, condition, item, groupby, orderby, leftjoin, hits, for`。`select()` 新增末尾 `$for`；`selectOne()` 新增末尾 `$hits` 和 `$for`，已有位置参数调用保持兼容。覆盖这些方法的自定义子类应同步可选参数签名。

```php
// $db 已初始化，并在同一连接上成功开启事务。
$row = $db->selectOne('t_invoice', ['pk_invoice' => $id], for: 'UPDATE');
// 检查查询结果，执行所需更新后提交；失败路径回滚。
```

仅精确的大写 `'UPDATE'` / `'SHARE'` 追加 `FOR UPDATE` / `FOR SHARE`，传入 `'FOR UPDATE'`、小写或其他值均不会追加。锁子句位于主查询 LIMIT 之后，COUNT 查询不加锁；`selectOne()` 继续限制一条并关闭计数。调用方负责使用同一连接管理事务，并确认数据库支持所选语法。详情见[数据库说明](docs/plugins.md#数据库失败处理)。

#### 6.3 升级验证

- 搜索并修改控制器及父类中的 `$inPath`、数组类型的请求参数、直接调用入口的代码，检查返回类型。
- 覆盖默认路由、多层控制器、路径参数、Request-Type 前缀、CLI 和内部调用；确认构造函数与入口读取到相同的 Request 和完整路由。
- 验证查询参数、表单、JSON、上传、请求头与 Cookie；确认大小写键名及快照行为符合应用预期。
- 验证字符串返回、JSON 输出、重定向、响应结束、404 和异常处理，确认没有重复正文或响应结束后的业务操作。
- 验证数据库失败分支；使用锁查询时检查事务提交、回滚和数据库语法支持。

部署仓库示例后，通过浏览器验证真实入口、自动加载、模板与 URI 解析；具体步骤见[部署文档](docs/deployment.md#验证)。

## 6.2.2 — 2026-11-11

- `Db::select()` 新增末尾 `$for` 参数；仅精确传入大写 `UPDATE` / `SHARE` 时，在主查询 LIMIT 后追加 `FOR UPDATE` / `FOR SHARE`，COUNT 查询不加锁。
- `Db::selectOne()` 新增末尾 `$hits`、`$for` 参数并传给 `select()`，原有位置参数调用保持兼容。事务及连接由调用方管理，数据库须支持对应锁语法。

## 6.2.1 — 2026-11-10

- `Db::query()` 移除第三个 `$retry` 参数和连接失败后的自动重连、重试逻辑；查询失败保留错误信息、触发 `E_USER_WARNING` 并返回 `false`。
- `DbPDO` 移除公开的 `$connectionError` 标记，`errno()` 从 `error()` 返回的错误信息读取 SQLSTATE。依赖旧参数、标记或自动恢复行为的应用需要迁移，见 [6.3 迁移指南](#63-数据库调用)。

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

旧 `.conf` 文件、含注释的 `.json` 文件、超过默认深度的路由、旧验证码会话记录及依赖旧 JSON 日志格式的解析程序需要调整。详见 [6.2 升级说明](#62-迁移指南)。

### 6.2 迁移指南

升级到当前版本还需完成 [6.7 迁移](#67-迁移指南)，Tpl 现在统一自动转义并移除模式接口。

6.2.0 收紧配置、路径、验证码和日志处理规则。已有 6.0 / 6.1 应用应检查以下行为变化；5.x 应用还需先按 [6.0 迁移指南](#60-迁移指南)调整控制器和插件类名。完整更新记录见 [Changelog](#changelog)。

本文记录 6.2.0 的变化。升级到当前 6.7.1 时，还需按 [6.3 迁移指南](#63-迁移指南)修改控制器签名并检查 6.2.1 / 6.2.2 的数据库变化，再按 [6.4 迁移指南](#64-迁移指南)调整模板调用，并按 [6.5 迁移指南](#65-迁移指南)处理调度异常，再按 [6.6 迁移指南](#66-迁移指南)调整类名、辅助函数和 Cookie 默认策略；下方 `^6.2` 约束也允许安装 6.7，因此不能据此保留旧接口。

#### 6.2 安装

PHP 和依赖要求保持为 PHP 8.0+、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha；插件的附加运行条件见[插件指南](docs/plugins.md)。将应用的 Composer 约束调整为 `^6.2`：

```bash
composer require hetao29/slightphp:^6.2
```

#### 6.2 配置文件

Config 不再解析 `.conf` 等旧格式。将配置转换为 JSON 对象或数组，并更新 `Config::getConfig()`、`Db::setConfigFile()`、`Redis::setConfigFile()` 等调用的路径。`.json` 现在必须是严格 JSON；含注释的文件需改用 `.jsonc`。两种格式均不允许尾随逗号。

不再读写或反序列化临时文件缓存，旧 `0SConfig_Cache_*` 文件不再使用。仅保留进程内缓存；修改配置后需重启常驻进程，或保持 `Config::$CACHE = false`。关闭缓存不会清空已有结果。异常类型和返回值见 [Config 说明](docs/plugins.md#config)。

#### 6.2 请求路径

路径默认最多 10 段，包含区域、page、entry 和路径参数；从 6.5.0 起，超过上限时 `run()` 抛出 `SlightPHP\Exception::PATH_TOO_DEEP`，不加载控制器。正常请求、CLI 和内部调用均受限制。已有路由需要更多路径段时，在初始化阶段按实际需要调整：

```php
SlightPHP::setMaxDepth(15); // 必须大于等于 1
```

未超限的路径参数保持原样，默认区域、页面和入口的补全不额外计数。详情见[核心调度文档](docs/framework.md)。

#### 6.2 模板与语言文件

模板源文件及编译文件必须分别位于模板目录及编译目录内，语言文件必须位于语言根目录内；检查是否依赖跨目录路径或指向目录外的符号链接。重新生成模板编译缓存，并确认运行用户可写编译目录。新建编译目录默认 `0700`，新建或重新写入的编译文件设为 `0600`；已有目录和命中的缓存文件不会自动修改权限。

模板 `escape` 修饰器现在明确转义单双引号并替换非法 UTF-8。Language 忽略非法语言标签、非法 q 值及 `q=0`，限制请求头为最多 8192 字节、前 32 项；仍按出现顺序查找，不按 q 值排序。自定义 locale 和 zone 的规则见 [Language 说明](docs/plugins.md#language)。

#### 6.2 验证码

每个会话前缀只保留最新验证码，刷新会替换旧记录；默认有效期为 300 秒，可在生成前设置 `Captcha::$ttl` 为正整数。`check()` 成功后立即移除验证码，同一业务流程只校验一次。生成及校验时需保持活动会话。

旧的“前缀 + 验证码”会话记录不再接受，升级后应提示用户重新获取验证码。不同用途可使用不同前缀隔离。并发一次性校验依赖会话后端的互斥锁配置，详见 [Captcha 说明](docs/plugins.md#captcha)。

#### 6.2 错误与日志

`Error::$LOGARGS` 默认为 `false`，错误日志和控制台输出不再包含调用栈参数；如需恢复，可显式设为 `true`，但字段名脱敏不能覆盖所有敏感值。错误和异常消息本身的数据不受此开关影响。HTML 错误输出及 Log 的网页控制台注释会转义 HTML；JSON 和纯文本接口应由应用设置正确的 `Content-Type`。

`Log::$LOGTYPE = 'json'` 现在输出 JSON Lines，每条记录包含 `time`、`level` 和始终为参数数组的 `message`。升级日志采集及解析程序，为新格式使用独立日志文件或先轮转旧文件。默认 `text` 模式保留多行文本。格式示例见 [Log 说明](docs/plugins.md#log)。

#### 6.2 验证

升级后覆盖实际应用的路由、配置加载、模板渲染、语言选择、验证码刷新与重复提交、错误输出及日志采集。部署仓库示例后，通过浏览器验证入口、自动加载、模板与 URI 解析；具体步骤见[部署文档](docs/deployment.md#验证)。

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
- 更新框架文档、API 参考及可运行示例。5.x 应用升级请阅读 [6.0 迁移指南](#60-迁移指南)。

### 6.0 迁移指南

6.0 是不兼容的主版本。先在测试环境升级依赖和应用代码，覆盖控制器、插件及部署路径，再发布到生产环境。

当前版本为 6.7.1。本页保留从 5.x 迁移到 6.x 的基础说明；升级到当前版本时，还需阅读 [6.2 升级说明](#62-迁移指南)、[6.3 迁移指南](#63-迁移指南)、[6.4 迁移指南](#64-迁移指南)、[6.5 迁移指南](#65-迁移指南)及 [6.6 迁移指南](#66-迁移指南)及 [6.7 迁移指南](#67-迁移指南)。

#### 6.0 安装与加载

将依赖约束改为 `hetao29/slightphp:^6.7.1`，并在入口加载 `vendor/autoload.php`。当前包仅强制要求 PHP 8.0+；使用数据库、Redis、HTTP 或验证码插件时，先按[插件指南](docs/plugins.md#按需安装依赖)将所需依赖加入应用的 `require`，再执行 `composer update hetao29/slightphp`。验证码还需 GD、mbstring、fileinfo 和可用的会话；数据库和 Redis 功能分别需要对应驱动和服务。

旧版手动引用 `plugins/S*.php` 的路径和 `SLIGHTPHP_PLUGINS_DIR` 常量已移除。模板辅助函数仍由 Composer 自动加载。切换部署版本后清理并重新生成模板编译缓存。

#### 6.0 控制器和路由

框架只支持命名空间控制器。将 `app/user/profile.page.php` 中的 `user_profile::pageUpdate()` 改为 `app/User/ProfileController.php` 中的 `User\ProfileController::Update()`；`/user/profile/update` 的 URL 可保持不变。多级目录同样要按命名空间首字母大写规则迁移。可用 `setControllerSuffix()` 和 `setAppNamespace()` 配置后缀及根命名空间；后者也影响磁盘路径。

删除 `setModes()`、`getModes()`、`setDefaultType()`、`getDefaultType()` 和 `$defaultType` 的调用。无 `Request-Type` 请求头时，入口方法不再默认添加 `page` 前缀；有该头且值在允许列表中时，仍会添加对应前缀。注意检查 AJAX、内部调用、CLI 任务及自定义错误页面。框架仍不根据 GET/POST 自动限制方法。

请求路径现在默认最多 10 段，包含区域、page、entry 和路径参数；从 6.5.0 起，超过上限时 `run()` 抛出 `SlightPHP\Exception::PATH_TOO_DEEP`，不会加载控制器。原有路由或业务参数超过 10 段时，可在初始化阶段通过 `SlightPHP::setMaxDepth($maxDepth)` 调整（必须大于等于 1），通过 `getMaxDepth()` 读取。未超限的路径分段保持原样，默认路径补全不额外计数。该限制同时适用于正常请求及内部调用，详见[核心调度文档](docs/framework.md)。

6.3.0 不再传入 `$inPath` 数组：构造函数接收 `Request $request`，入口接收 `Request $request, Response $response`，路径从 `$request->route` 读取。签名和响应结束行为的迁移示例见 [6.3 迁移指南](#63-迁移指南)。

#### 6.0 插件类名

旧的全局插件类名及兼容别名均已删除，必须使用命名空间类，或在调用处使用 `use` 导入：

| 5.x | 6.0 |
| --- | --- |
| `SCaptcha` | `SlightPHP\Captcha` |
| `SConfig` | `SlightPHP\Config` |
| `SDb` | `SlightPHP\Db` |
| `SError` | `SlightPHP\Error` |
| `SHttp` | `SlightPHP\Http` |
| `SJson` | PHP 原生 JSON 函数（6.5.1 起移除 `SlightPHP\Json`），见 [JSON 迁移](#65-json-插件移除) |
| `SLanguage` | `SlightPHP\Language` |
| `SLog` | `SlightPHP\Log` |
| `SRedis` | `SlightPHP\Redis` |
| `SRoute` | `SlightPHP\Route` |
| `STpl` | `SlightPHP\Tpl` |

同时替换 `new`、静态调用、继承、类型声明、`instanceof` 和配置中存储的类名。数据库的表记录、查询及事务操作现在都在 `SlightPHP\Db`；`SlightPHP\Internal\Db` 不再存在。

`SCache`、`SLunar`、`SMail`、`SSphinx`、`SUtil` 和 `SThumbnail` 已移除，没有内置替代别名。请根据应用功能另选维护中的依赖并迁移接口和配置。

验证码实现改用 `Gregwar\Captcha\CaptchaBuilder`；旧 `SimpleCaptcha` 的专有属性和方法不再可用，检查自定义验证码生成代码。保留的 `Captcha::check()` 和 `del()` 使用当前会话。确认图片扩展、字体和输出流程后重新测试。

#### 6.0 Config 配置迁移

`SlightPHP\Config` 仅支持 `.json` 和 `.jsonc`，不再解析 `.conf` 等旧格式。将分组转换为 JSON 对象、重复键转换为数组，并更新 `Db::setConfigFile()`、`Redis::setConfigFile()` 及 `Config::getConfig()` 等调用中的路径。`.json` 使用严格 JSON；需要注释时使用 `.jsonc`。

Config 已取消序列化文件缓存，只保留由 `Config::$CACHE` 控制的进程内缓存；旧的 `0SConfig_Cache_*` 文件不会再读取。关闭缓存不会清空已有内存结果，常驻进程更新配置后应保持缓存关闭或重启进程。

#### 6.0 升级检查

逐项测试默认路由、多级控制器、请求类型前缀、404 和反向代理路径；检查配置、模板、数据库、Redis、日志及验证码；重新生成 Composer 自动加载文件并在实际 PHP 运行环境运行测试。原 PAGE 文件不会被调度，旧类名不会自动加载，不能依靠旧缓存掩盖迁移问题。
