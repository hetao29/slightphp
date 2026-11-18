# 升级到 SlightPHP 6.6.0

6.6.0 新增可选的 Tpl 上下文自动转义与编译执行计划，默认继续使用兼容模式；同时迁移 Db 内部类及模板辅助函数，调整 Cookie 安全默认值，修复 Language 切换缓存并实现 Redis Sentinel。完整变更见 [Changelog](../CHANGELOG.md)。

**从 6.5.3 升级也需要检查旧类名、全局模板函数、自定义扩展和 Cookie 配置。** 自动转义需要逐实例显式启用；类名及 Cookie 默认策略的变化则不取决于是否启用自动模式。从更早版本升级，还需完成 [6.5 迁移](migration-6.5.md)及对应的历史迁移。

**6.7 变更预告：Tpl 的 contextMode 将默认开启，并移除 `setContextMode()`、`getContextMode()`，不再支持调用这两个方法。** 6.6.0 仍默认关闭自动转义，两个接口继续可用。建议先在 6.6 中开启自动模式，按本文迁移原始数据与模板；升级到 6.7 时删除所有模式设置和读取调用，包括原先的 `setContextMode(false)`，并将依赖兼容模式的模板迁移到自动转义规则。

## 安装与部署

```bash
composer require hetao29/slightphp:^6.6.0
```

核心仍要求 PHP 8.0+；插件依赖继续[按需安装](plugins.md#按需安装依赖)，Sentinel 模式需要 phpredis 6.0+。应用更新后重新生成 Composer 自动加载文件。部署时确保模板编译目录可写，旧编译文件会按格式标记自动重建；常驻进程需重启，OPcache 按部署流程刷新。

自动模式将静态分析及 JS 词法分析尽可能移至编译阶段，完整计划命中后不再执行 JS 词法扫描。大模板首次编译仍有明显成本，建议提前预热实际模板及所需上下文变体；编译缓存不保存模板变量或最终页面输出。

缓存与 OPcache 命中不意味着自动模式在所有场景都比原版快。重复的纯变量可在同一静态块内复用读取和编码；不同值、循环、复杂表达式和 JS/CSS 上下文仍有额外成本。默认兼容模式的缓存路径接近原版；对象及动态上下文保留受守卫的回退路径，不能套用原生数组/标量的性能结论。

## 内部类与模板辅助函数迁移

数据库结果类与适配器分别从 `SlightPHP\DbData`、`SlightPHP\DbPDO` 迁移为 `SlightPHP\Db\Data`、`SlightPHP\Db\PDO`，文件位于 `plugins/Db/`。直接使用旧类名或文件路径的应用需更新；Db 查询接口及读取 COUNT 第一行的行为保留。

模板辅助函数由全局函数改为 `SlightPHP\Tpl\Func`、`SlightPHP\Tpl\Modifier` 的静态方法：

```php
\SlightPHP\Tpl\Func::include('child.tpl', $tpl);
\SlightPHP\Tpl\Func::part('/widgets/template/card/demo');
$safeHtml = \SlightPHP\Tpl\Modifier::escape($value);
```

分别替代 `tpl_function_include()`、`tpl_function_part()`、`tpl_modifier_escape()`；其余内置辅助函数同样去掉前缀后作为静态方法调用。删除旧文件的手动 require，使用 Composer PSR-4 自动加载，并重新生成自动加载文件。旧全局函数和旧 Db 类名不提供别名。已有编译模板会按文件头自动重建，部署用户需有重写权限。

不再通过 `tpl_function_*` / `tpl_modifier_*` 前缀查找自定义扩展。模板中的内置标签和修饰器写法继续可用；自定义扩展需改为同名普通 PHP 函数，并在应用中显式加载。例如 `{myhelper($value)}` / `{$value|myhelper}` 对应 `myhelper()`，不再隐式查找旧的前缀函数。自动模式的函数与修饰器应返回数据，最终编码及限制见[上下文自动转义](templates.md#上下文自动转义)。

Tpl 默认继续使用兼容模式。启用 `setContextMode(true)` 后，Modifier 中的 raw、escape、default、tr、tostring、version 仍可用；普通修饰器先转换数据，再由输出位置自动编码，末尾 raw 表示最终可信原文。末尾 HTML escape 按实际上下文重新编码，URL escape 在已有前缀的 HTML URL 属性中避免组件重复编码；其它位置保留其转换结果。没有 json 内置别名。JS 位置及状态计划在编译阶段生成，动态新入口首次编译上下文变体，完整命中后不再词法扫描。Lexer 识别模板语法并保留普通 JS/CSS 花括号；语法完全相同的内容用 literal 或不同分隔符消歧。模板错误仍原样抛出，支持边界见[模板手册](templates.md#上下文自动转义)。

自动模式现在会在首次编译时生成带状态守卫的静态块/编码计划，命中后按实际入口使用或回退；不缓存变量和最终输出。升级后需允许重写旧编译文件，常驻进程重启并按部署策略刷新 OPcache。不要跨模板插值拼写未完成的 HTML 实体或 JS 模板字符串插值语法，这些前缀会按字面量保护；正常数据的呈现含义保留。详见 [编译缓存与执行计划](templates.md#编译缓存与执行计划)。

## Tpl 自动转义迁移（启用时不兼容）

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

### 完整 URL 与业务编码仍需保留构造逻辑

`<a href="{$url}">` 将变量视为完整 URL，只检查协议并做 HTML 属性编码，**不会重新编码其中每个路径或查询参数**。在业务层通过 `http_build_query()`、路径编码或拼接产生完整 URL 时，保留这些构造步骤；不要仅删除拼接表达式中的 `urlencode()` 后继续把结果当成完整 URL 输出。将原始查询参数独立放到模板中的 `/search?q={$q}`，才能由模板识别为 URL 组成部分。

自动判定依据插值所在的语法位置，不推断字符串的业务含义。例如 `<script>const target="/search?q={urlencode($q)}";</script>` 中的变量位于 JS 字符串，模板不会再把它识别为 URL 参数；这里仍需保留 URL 构造编码，之后由自动模式负责 JS 字符串编码，或者在业务层构造完整 URL 后传入模板。

用于显示编码文本、API 参数、签名或精确 URL 字节的转换也不属于输出防护，不能批量删除。自动 URL 组成部分采用 `rawurlencode()` 规则，空格为 `%20`；`urlencode()` 的空格为 `+`，切换可能改变签名、缓存键或依赖精确字节的断言。完整 URL 内已有的 `%xx` 不会自动解码。

不要用末尾 `|raw` 普遍绕过迁移：它会跳过当前上下文的最终编码，仅适用于已确认可信且适合当前位置的原文。建议先为相应实例开启自动模式，再按输出位置迁移模板，检查显示、表单值、实际链接、JS/CSS 及既有 URL 签名；保留兼容模式的模板继续使用原来的手工编码。完整支持边界见[上下文自动转义](templates.md#上下文自动转义)。

## Cookie 默认策略与会话

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

单次 `cookie()` 调用可显式覆盖默认值；需要浏览器脚本读取的特定 Cookie 应显式设置 `httponly: false`。跨站 Cookie 的 `SameSite=None` 必须与 `Secure=true` 同时使用，不合法组合抛出 `ValueError`。会话不会因设置 Response 默认值而自动更新，需在 `session_start()` 前配置；HTTPS 与可信反向代理的完整示例见 [生产部署](deployment.md#cookie与会话安全)。不要直接信任客户端传来的 `Forwarded` / `X-Forwarded-Proto`。

## Language 语言切换与请求重置

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

串行常驻请求应在每次请求开始、设置本次语言前重置。该接口只重置 Language，不重置路由、会话或其他插件，也不提供同进程并发请求隔离。Config 及词典仍有进程内缓存，更新资源后按部署流程重启；完整边界见 [Language 插件](plugins.md#language)。

## Redis Sentinel

`engine: "sentinel"` 现在使用 RedisSentinel 按监控服务名发现主节点，不再创建 RedisArray。原来使用该模式名但把数据节点地址当作 `host` 的配置必须迁移：`host` 应为 Sentinel 地址列表，`options.service` 是 Sentinel 监控的服务名；认证分别使用 `sentinel_auth` 和 `auth`。完整 JSON 配置见 [Redis 插件](plugins.md#redis)。

连接后会验证主节点身份；断线或 READONLY 后，后续调用重新发现当前主节点，不重放失败命令。应用仍需判断失败并处理结果不确定的写入，不能依赖框架自动重试写命令。

## 控制器构造与示例验证

核心现在在实例化控制器前确认入口存在且为 public。缺失或非公开入口不再执行构造函数，不能依赖构造函数为这些请求生成响应或产生副作用；正常入口、候选回退、请求类型及 Route HTTP 方法校验继续生效。

示例站点的页头、页脚及安装命令已更新为 6.6.0，模板及展示代码统一使用默认 `{` / `}`。`/lab/template/entry` 与 `/widgets/template/context` 展示两种模式、全部内置修饰器、HTML/URL/JS/CSS 编码、literal、缓存变量更新及错误处理；`/lab/language/entry` 展示三种语言切换。文档浏览器可直接访问 `/site/main/docs?doc=migration-6.6.md`。生产使用仍需完成相应的 Cookie、会话和反向代理配置，见[部署说明](deployment.md)。
