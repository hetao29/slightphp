# Tpl 模板、上下文转义与模块组合手册（6.7.0）

运行 samples 后访问 `/lab/template/entry`，或从 `/site/main/examples` 的「模板与模块组合」进入。工作台侧栏也提供同名入口。该页面实际执行跨目录 include、跨模块 part 和嵌套调用，无需数据库或 Redis。

## 选择 include 或 part

| 调用 | 执行内容 | 模板变量 | 输出 |
| --- | --- | --- | --- |
| `{include "widgets/template-shared.tpl"}` | 加载模板文件 | 使用当前 Tpl 实例，读写共享变量 | 在当前位置直接输出子模板 |
| `{include $include_child}` | 从模板变量读取文件名 | 同上，可继续嵌套 include | 同上 |
| `{part "/widgets/template/card/demo"}` | 调用控制器入口方法 | 由控制器使用的 Tpl 实例决定；新实例有独立变量 | 模块直接输出到当前位置，再输出字符串返回值 |
| `{part $module_path}` | 从模板变量读取控制器路径 | 同上 | 同上 |

本文与 samples 的模板和代码示例统一使用默认 `{` / `}`，无需自定义配置；`Tpl::setDelimter()` 可恢复默认值。

## 初始化与变量生命周期

```php
use SlightPHP\Tpl;

Tpl::setTemplateDir(__DIR__.'/templates');
Tpl::setCompileDir(__DIR__.'/var/templates_c');
Tpl::setDelimter();

$tpl = new Tpl();
$tpl->assign('scope', 'PARENT');
$tpl->render('workspace/template.tpl', [
    'include_child' => 'widgets/template-shared.tpl',
]);
```

`assign()`、`render()`、`display()` 和 `fetch()` 都使用当前实例的数据。`render()` 参数覆盖同名变量，未覆盖的数据保留；循环和子模板赋值也保留在该实例。常驻服务按请求创建新实例。模板目录、编译目录、分隔符及强制编译等选项仍由所有实例共享。

`render()`、`display()`、`fetch()` 直接输出，返回值为 null。`fetch()` 要求调用前已设置目录，未指定分隔符时使用默认值；`render()` 可初始化缺省配置。6.3.x 的静态调用及变量表访问方式需按 [6.4 迁移指南](migration-6.4.md)调整。

## 上下文自动转义

6.7.0 统一启用上下文自动转义，不再提供兼容模式。`setContextMode()`、`getContextMode()` 已移除，旧调用会报错；请删除调用并按 [6.7 迁移指南](migration-6.7.md)迁移数据与模板。直接创建实例即可：

```php
\SlightPHP\Tpl::setDelimter();
$tpl = new \SlightPHP\Tpl();
$tpl->render('page.tpl', [
    'name' => '<访客>',
    'url' => 'https://example.com/?a=1&b=2',
    'query' => '中文 & 字符',
    'color' => '#369',
]);
```

模板应传入原始数据，让模板按输出位置编码。仅用于 HTML 输出防护的 `htmlspecialchars()`、`htmlentities()` 和末尾 `|escape` 通常可省略；URL 组成部分的手工 `urlencode()` / `rawurlencode()` 也通常可省略。完整 URL 的构造及业务/签名编码不能批量删除。普通函数没有编码意图标记，可能被再次编码；末尾内置 `escape` 的编码意图处理保留。替换示例及边界见 [自动转义迁移与不兼容说明](migration-6.7.md#手工编码与原始数据)。

以下模板使用默认 `{` / `}`，与 samples 的入口配置相同：

```html
<p>{$name}</p>
<input value="{$name}">
<a href="{$url}">完整 URL</a>
<a href="/search?q={$query}">查询参数</a>
<script>
const data = {$name};
const message = "Hello {$name}";
function show() { return {name: data}; }
</script>
<style>
.card { color: {$color}; }
</style>
```

| 输出位置 | 上下文处理 |
| --- | --- |
| HTML 文本、title/textarea 内容 | UTF-8 HTML 转义 |
| 普通属性，如 input 的 value | HTML 属性转义；无引号属性另外编码空白、等号和反引号 |
| href、src、action 等完整 URL 属性 | 拒绝危险协议，再做 HTML 属性转义 |
| URL 属性已有静态前缀，如 `/search?q=` | 将变量作为一个 URL 组成部分执行 rawurlencode，再做属性转义 |
| script 中的 JS 值 | JSON 编码后加圆括号，支持数组、对象；不需要手动加引号，负数不会与相邻运算符合并 |
| JSON script 数据块 | 完整值直接 JSON 编码，双引号内只编码字符串内容，不添加表达式括号 |
| JS 单/双引号字符串、模板字符串、正则内容 | 按所在词法状态编码字符串内容，防止关闭字符串或 script 元素 |
| on* 事件属性 | JS 编码后再做 HTML 属性转义 |
| CSS 字符串、选择器标识符、url()、style 属性 | 按 CSS 词法状态编码；URL 检查协议，属性再做 HTML 转义 |
| CSS 普通值，如 color | 接受数字、颜色、单词等简单值；复杂结构应写在模板静态部分 |

URL 的最终拼接结果也会检查，防止用多个变量或静态后缀拼出危险协议。允许相对地址及 http、https、mailto、tel、ftp 协议，不是域名白名单。srcset/imagesrcset 逐项检查 URL，不接受 data:。CSS 中使用动态数据时还会检查拼接后的 URL 与 expression/binding/behavior 结构。`raw` 代表调用方保证可信，不属于这些自动编码保证。

例如 `<script>const result=10-{$n};</script>` 在 `$n = -2` 时输出 `10-(-2)`。CSS 的 `url({$url})` 会把动态 URL 中的空格编码为 `\20 ` 等 CSS 转义，避免未加引号的 URL 失效；已加引号的 URL 和普通 CSS 字符串仍保留正常空格。

数组展开、对象展开和函数参数展开均支持完整值插入，例如 `[...{$items}]`、`({...{$fields}})` 和 `Math.max(...{$numbers})`。单个点后的成员名仍不能动态输出。

CSS URL 已有前缀时，变量按 URL 组成部分编码，不单独作为完整 URL 检查协议。例如 `url("/image?label={$label}")` 中的 `red:blue` 会编码为 `red%3Ablue` 后再做 CSS 转义。没有前缀的完整 URL 仍检查协议，样式正文、style 属性和 @import 拼接完成后也会复核最终 URL。

JSON 数据块支持 `type="application/json"`、`application/ld+json` 等 `+json` MIME、`text/json`，以及 `importmap`、`speculationrules`。type 可动态输出；按 HTML 规则使用首个同名属性，MIME 类型忽略大小写及参数。例如以下模板接收 PHP 数组，输出可直接交给 `JSON.parse()` 的 JSON；字符串里的 `</script>` 等字符仍会编码，避免提前关闭元素。

```html
<script type="application/json" id="page-data">{$payload}</script>
<script type="application/ld+json">{$structuredData}</script>
<script type="importmap">{$importMap}</script>
```

JSON 数据块也支持在静态 JSON 的值或双引号字符串中插值，不支持单引号字符串、JS 模板字符串或正则等 JS 专属位置。其它 script 类型保留现有 JS 编码路径。

`raw`、`escape`、`default`、`tr`、`tostring` 和 `version` 均位于 `SlightPHP\Tpl\Modifier`，继续支持。自动模式先执行修饰链，再按实际输出位置编码；把 `raw` 放在链末尾可明确跳过最终编码：

```html
<section>{$trustedHtml|raw}</section>
```

其输出会继续进入上下文状态机，后续变量按实际产生的 HTML/JS/CSS 状态处理。仅用于已确认可信的片段。PHP 中也可直接调用 `\SlightPHP\Tpl\Modifier::raw($value)`，该方法只返回原值；模板中的末尾 `|raw` 才标记原文输出意图。

```html
<p>{$name|default:"游客"}</p>
<p>{$label|tr:"main"}</p>
<pre>{$debug|tostring}</pre>
<script src="{$asset|version:"2.0"}"></script>
<input value="{$name|default:"游客"|escape}">
<section>{$trustedHtml|raw}</section>
```

自动模式中，链末尾的 `escape` 交由真实输出位置编码，避免 HTML 二次转义，也避免把 HTML 编码误用到 JS 中。`escape:"url"` 位于 `/search?q=` 等 URL 属性组成部分时只编码一次；普通 HTML 文本中的 URL 转换结果仍保留。后续修饰器若没有改变 escape 的结果（如非空值上的 default），也保留这个编码意图；改变结果后则作为新的数据重新自动编码。建议将 escape 或 raw 放在链末尾。

修饰器名优先解析 Modifier 方法，再尝试同名普通 PHP 函数，例如 `|strtoupper`；不存在 `json` 内置别名，也不再查找 `tpl_modifier_*` 前缀。未知修饰器在自动模式抛出异常。修饰器不能靠 echo 输出；翻译、默认值及版本文本仍受最终上下文的 URL/CSS 等检查。

include 共享父模板的变量及输出状态，因此子模板可以位于属性或 script 内，变量仍按实际位置编码。if/elseif/else、foreach、for 及赋值按真实执行路径更新状态；无需假设所有分支输出相同结构。每次最外层渲染创建新的输出状态，正常结束或异常后均清理。普通 HTML 元素可以跨模板组合，最外层结束时不得停在未闭合的属性、注释、script/style 等词法状态，否则抛出异常。

part 的结果视为控制器负责转义的可信 HTML，只能在普通 HTML 文本位置插入。新模式会收集片段输出，再跟踪其实际结构；控制器创建的新 Tpl 实例同样自动转义。模块必须配对管理自己的输出缓冲。普通表达式和模板函数必须返回值，直接 echo 或改变输出缓冲会报错。

这是一套保留源码顺序的词法状态机，不依赖 DOM，也不要求 ext-dom 或 PHP 8.4。模板是可信代码，不是沙箱。它支持常见 HTML、JS 字符串/值及 CSS 插入位置，不是完整浏览器解析器；动态标签名、属性名、HTML/JS/CSS 注释、SVG/MathML、srcdoc、动态 meta refresh、未完成的反斜杠转义、动态 JS 标识符及其他歧义位置会抛出异常。属性建议始终带引号；空值插入无引号属性的起始位置会报错。JS 字符串中的变量插值请使用模板标签，PHP 表达式内的双引号插值写法需改为字符串连接。上下文处理仍有运行时状态检查和缓冲成本。

在自动模式中，含动态值的事件、style 和 URL 等敏感属性必须使用带分号且可识别的 HTML 实体，例如 `&quot;`、`&#34;`。缺少分号的 `&quot `、`&#34 `、未知实体及原生解码器不支持的引用会抛出异常，防止浏览器和 PHP 对引号或空白的理解不同；引用检查也覆盖最后一个插值之后的静态后缀。普通 URL 查询串的 `&page=1` 可以保留，建议统一写成 `&amp;page=1`。实体只解码一次。

JS 扫描会区分控制条件结束后的正则与普通调用后的除法，保留包括垂直制表符在内的行首空白规则。不确定的斜杠位置（如 `}` 后直接接 `/`、操作数换行后接 `/`、含义不明确的 `await/yield/of` 后接 `/`）会报错；可用分号明确语句边界，或用圆括号明确除法的左操作数。转义/非 ASCII 标识符、嵌套正则字符类暂不支持出现在自动插值的 JS 前缀中，字符串和注释里的中文不受这条标识符限制。不要用 raw 绕过不明确的数据编码。

正则起始位置的空插值会输出空的非捕获组：`const re=/{$needle}/;` 在 needle 为空时生成 `const re=/(?:)/;`，避免两个斜杠合并为行注释。已有正则内容后的空插值以及字符类中的空插值保持为空，例如 `/a{$needle}*/` 仍为 `/a*/`。

## 编译缓存与执行计划

Tpl 只负责变量、缓存和执行；源码入口统一在 `Tpl\Compiler::compileSource()`，由 Lexer 分词后生成 PHP。`Tpl::_compile()`、`$safe_mode` 和 `setSafeMode()` 已移除，不再用正则替换原生 PHP 标签；静态 PHP 标签按字面量输出，模板表达式继续编译为 PHP。模板必须来自可信来源。

编译期优化减少了缓存命中后的重复解析，但不代表自动模式在所有场景都比原版快。重复纯变量可复用读取和编码，不同值、循环和 JS/CSS 等上下文仍有额外检查。大量生成的 PHP 代码也受 OPcache 影响，生产部署应启用编译缓存与 OPcache，并提前预热大模板；首次编译与完整缓存命中的成本需分别评估。

自动模式在首次编译时分析静态片段，生成可整块输出的 HTML 文本块、HTML/属性编码调用，以及 JS 编码和状态推进计划。JS 计划编译方法合并在 `Tpl\Compiler::compileJavascriptPlan()` 中。运行时先完成表达式求值，再检查实际上下文；分支、循环、include、raw 产生的真实结构始终决定编码方式，不使用未经验证的编译期假设。

编译文件保存的是处理计划，不保存变量值或最终 HTML。同一份缓存可以用于不同请求、分支和数据。静态 HTML 除整块输出外，还会编译未完成标签的固定前缀，以及插值后紧邻的引号加 `>` 闭合操作；循环中的相同 HTML 因而无需每行重新逐字符解析。文本入口、实际引号和外来元素状态不匹配时仍回退通用解析。闭合实际标签时保留 URL/事件/style/srcset/meta content 等组合检查；若进入 script、style 或 SVG，后续片段会重新检查入口。

空前缀、带引号的普通属性可直接对标量做 HTML 编码；实际标签前缀必须与编译提示完全相同。敏感属性、URL、meta content 及 Stringable 等对象仍走完整编码和转换守卫。标签仅含普通属性时不再重复提取全部属性做无关检查，script 的 type 判定独立保留。

连续的普通 HTML 片段如果只含纯变量或常量键数组查找，还会编译成直接取值、转义和 `echo` 的 PHP。运行时确认使用基础 Tpl 的原生变量表、数组节点均为原生数组、叶子均为标量/null，且实际入口为 HTML 文本后，便无需逐变量调用状态机或创建求值缓冲；同一只读路径的相同编码可在块内复用。固定 `/` 前缀的单变量 URL 属性使用 URL 组成部分编码，并保留完整拼接 URL 的协议检查。编译器用标记预演确定编码位置，不保存实际请求值。

对象、ArrayAccess、Stringable、缺失键、函数、修饰器、赋值及控制语句本身仍走原路径；JS/CSS 使用各自已有的编码计划，不能证明 HTML 文本入口及出口的片段也保留原路径，保留警告、回调与异常行为。直接输出块仍更新上下文修订号，不能绕过外层转换守卫。编译缓存依然通过 include 执行；缓存保存编码和输出代码，不缓存最终页面数据。

script 正文和 on* 属性的 JS 词法分析在编译计划时执行。主 PHP 编译文件注册可静态确定的计划，包括有限展开的 if/elseif/else 路径；运行时按入口状态和可信源码增量查表，只编码本次数据，不调用 JS 词法扫描器。可执行 JS 中的完整值带圆括号，统一用词法等价的 `(0)` 占位符推进计划，改变类型、数值或 JSON 内容不会把实际数据固化到缓存中。JSON 数据块复用编译计划，但完整值不加括号，其占位符保留字符串、数字、对象等外部 token 形态；首次遇到新形态时可能编译新的上下文变体。JS/JSON 字符串和正则中的空内容仍保留为空，防止跨数据边界组成 `${...}` 或注释时误判。

raw、动态 include、循环和数据形态变化可能产生主模板尚未预见的入口。首次遇到这种入口属于**上下文计划未命中**，会编译并保存变体；以后命中变体同样不做 JS 词法扫描。变体保存在外层模板编译文件旁的 `.js.json` 中，带版本并通过锁合并，最多保留 512 项；主编译文件和变体都命中才是完整缓存命中。raw 按可信源码处理，其 token 边界尾部可能进入变体计划；普通自动编码的数据内容不进入计划。

各 script/事件属性有独立状态，同实例 include 共享计划表和当前输出流。CSS 仍使用增量词法断点；属性值复用当前属性游标。离开属性、关闭元素或开始新元素时重置相应状态，最外层结束或异常后释放 Context。所有输出位置的 Stringable 转换及 JSON 序列化回调均有输出缓冲和上下文修订号检查：直接输出、修改缓冲层级或嵌套渲染改变已选位置时抛出 LogicException，并清理转换期间捕获的输出；转换应只返回数据。

script/style/title/textarea 等正文的结束标签也按增量候选扫描，标签名可跨 literal、raw、include 或值输出分段。普通 `>` 比较符不会触发整段正文重搜；不改变 HTML 状态的连续正文整段追加。script 的 escaped/double-escaped 状态仍按实际输出维护，不能因复用 JS 计划提前接受结束标签。大段静态 JS 前缀在计划命中时只需查表校验，不再逐字节做 JS 词法扫描。

URL 协议、拼接后的属性和 CSS 内容仍在运行时验证；普通表达式和函数的输出缓冲检查仍保留。编码失败仍抛出异常，不能为了命中快路径改为原样输出。首次编译和新上下文编译仍有分析成本；分隔符、编译格式或 JS 计划版本变化会触发相应重编译，新上下文变体需要编译目录可写。

自动模式会将属性值插值前未完成的 HTML 实体前缀按字面量保护，防止静态 `&quo` 与动态 `t;` 被浏览器重新组合成引号。JS 模板字符串中的动态花括号也编码为 Unicode 转义，防止静态 `$` 与动态 `{...}` 形成可执行插值。应在静态模板中完整书写实体和 JS 插值语法；这些编码可能改变 HTML 源码字节，但保留动态数据的字符串含义。

内部职责如下，应用通常只使用 Tpl 和 Modifier 的公开方法：

| 类 | 职责 |
| --- | --- |
| `Tpl\Compiler` | 生成 PHP 模板代码和 JS 状态计划，不另设 JsCompiler |
| `Tpl\JsContext` | 一条 script/事件属性流的状态及待消费源码块，按计划推进 |
| `Tpl\JsPlanCache` | 注册主编译文件里的计划，查找、编译未命中变体并持久保存 |
| `Tpl\CodeContext` | JS 编译期词法分析、CSS 增量分析及上下文编码 |
| `Tpl\Modifier` | raw、escape、default、tr、tostring、version 的实际方法 |
| `Tpl\ModifierValue` | 自动模式修饰链的内部结果，携带值及 raw/escape 意图，交给 Context 输出 |

具体 JS 计划是一条数组记录，保存编码器、断点和 token 边界尾部；`JsContext` 是执行状态，`JsPlanCache` 是缓存对象，命名不采用单复数来区分这两个职责。PHP 直接调用 Modifier 返回普通值；模板自动模式才使用 ModifierValue 传递最终编码意图。

## 默认花括号与 literal

默认分隔符为 `{` / `}`。Lexer 根据标签语法区分模板指令与普通 JS/CSS 花括号，支持表达式中的引号和嵌套括号。HTML 中的 `${$price}` 仍表示美元符号加模板变量；只有 JS 反引号模板字符串内的 `${...}` 保留为原生插值。遇到 `${` 才启用增量源码探测，不执行模板代码；源码中的控制流、include 或 raw 可能产生动态结构，复杂混合场景应使用 literal 或不同分隔符。探测无法理解某段 JS 时，会保留该 JS 作用域原有的美元花括号原文行为，实际输出转义仍由运行时上下文决定。标签语法完全相同的片段（如 JS 的 `{$name}` 对象简写）仍需显式 literal 或更换分隔符。

模板识别 `{true}`、`{false}`、`{null}` 等 PHP 字面量标签（名称不区分大小写），在 HTML 文本中分别输出 `1`、空字符串、空字符串；也支持字面量表达式和修饰链。控制条件中的字符串不会作为原生 JS 块判断，例如 `{if strpos($s, '){') !== false}YES{/if}` 可以正常编译。

候选标签直接在完整源码的字节偏移上匹配，不为每个普通花括号复制剩余模板，避免 JS/CSS 花括号密集时的二次方分词开销。编译缓存命中时不再运行 Lexer。

常量算式保留表达式语义，例如 `{PHP_INT_MAX-1}`、`{PHP_INT_MAX - 1}`、`{PHP_VERSION}`。常量及数字候选使用实际右分隔符，不依赖分隔符的首字符。大写函数的空格调用（如 `{ROUND -1.25 1}`）仍按原方式处理。

```html
{literal}
<script>
function show() { return {$name}; }
</script>
{/literal}
```

literal 块不执行任何模板标签，不能嵌套；需要插值时拆开 literal 块。samples 统一使用默认分隔符，literal 写作 `{literal}...{/literal}`。应用如需定制可向 `setDelimter()` 传入左右字符串，更换配置不会自动开启上下文转义。Else、ElseIf 及结束控制标签保留大小写兼容。

模板编译、表达式执行、include/part、上下文编码发生异常时原样抛给调用方，后续渲染停止；已经输出的内容及业务副作用不会回滚。已撤销严格分隔符与逐标签错误隔离版本，不再静默跳过错误标签或控制块。上下文无法安全判断时抛出异常，不能回退为 raw。

统一使用 `.context.php` 编译文件，文件头校验分隔符与编译器版本。旧缓存自动重建，需保留写权限；不改变现有目录权限约定。

## include：跨目录复用模板

模板路径从 `template_dir` 根目录解析。例如 `workspace/template.tpl` 中的：

```html
{include $include_child}
```

当 `$include_child` 为 `widgets/template-shared.tpl` 时，加载 `templates/widgets/template-shared.tpl`。该文件读取同一个 `$scope`，并继续加载另一目录的模板：

```html
<p>include 读取到：{$scope|escape}</p>
{include "workspace/template-note.tpl"}
```

两层子模板都读取到 `PARENT`。子模板对变量的赋值会影响后续父模板；需要独立变量时，由另一个控制器创建新 Tpl 实例，或在 PHP 代码中显式创建实例。

include 标签名不区分大小写，`include`、`Include` 和 `INCLUDE` 都会传入当前 Tpl 实例。include 加载模板文件，控制器方法通过 part 调用。路径及编译文件仍受目录边界检查，越界或文件缺失会抛出 `RuntimeException`。文件名应由应用的固定值或受控映射提供。

PHP 代码中的对应调用为：

```php
$tpl->fetch('widgets/template-shared.tpl');
// 辅助函数需要显式传入当前实例。
\SlightPHP\Tpl\Func::include('widgets/template-shared.tpl', $tpl);
```

## part：调用其它模块的方法

父模板调用：

```html
{part "/widgets/template/card/demo"}
```

路径对应 `samples/app/Widgets/TemplateController.php` 的 `Widgets\TemplateController::Card()`，`demo` 位于 `$request->route[3]`：

```php
public function Card(\SlightPHP\Request $request, \SlightPHP\Response $response): void
{
    (new \SlightPHP\Tpl())->render('widgets/template-card.tpl', [
        'scope' => 'CHILD',
        'label' => $request->route[3] ?? 'demo',
    ]);
}
```

Card 的模板及其 include 读取到 `CHILD`，调用结束后父模板仍读取到 `PARENT`。part 通过 `SlightPHP::run($path)` 创建控制器与新的 Request / Response；路径数组对应显式路径，请求的 GET、POST、Cookie、请求头等仍来自当前 PHP 请求。URL 路径参数保留原始内容，不自动 URL 解码；路径后的查询串不会合成为新的 GET 参数。6.5.2 起，`Request-Type` 前缀校验也适用于内部调用；part 不会自动补头，目标方法名以已配置类型开头而当前请求类型不匹配时会抛出 405，见[迁移说明](migration-6.5.md#请求类型前缀校验652)。

part 在同一个 PHP 请求中同步执行，session、超全局变量和响应头仍属于整个请求；它保留顶层 `zone/page/entry`。指向当前顶层入口的调用会抛出 `SlightPHP\Exception::RECURSIVE_DISPATCH`，其他循环调用需由应用控制。片段路径使用固定值或白名单；模块方法负责权限、输入校验及输出转义。

PHP 中也可以直接输出片段，函数返回 null：

```php
\SlightPHP\Tpl\Func::part('/widgets/template/card/demo');
```

## 返回值、输出顺序与异常

| 模块方法行为 | part 的输出 |
| --- | --- |
| 只 echo、调用 Tpl 渲染或 `Response::write()` | 已输出的内容 |
| 只返回 HTML 字符串 | 返回的字符串 |
| 先输出，再返回字符串 | 先输出的内容 + 返回字符串，各一次 |
| 返回 true、false、数字、数组或对象 | 只保留已输出的内容，忽略该返回值 |
| 调用 `Response::end('HTML')` | 保留 HTML；父页面继续渲染 |
| 空路径 | 不调用入口、不输出 |
| 找不到入口 | 抛出 `SlightPHP\Exception`，由调用方处理 |
| 控制器或模板抛出异常 | 原样抛出，已提交到外层的内容保留，失败片段的临时缓冲被丢弃；调用方可捕获后继续 |

模板中的 part 临时收集可信 HTML，成功后更新上下文并输出，返回 null；模块抛出异常时丢弃尚未输出的片段缓冲。需要控制器原始结果时，在 PHP 代码中调用 `SlightPHP::run()`。模块负责将动态内容转义为适当的 HTML，part 不会自动转义整个片段。片段渲染不会回滚 session 等业务副作用；响应状态、响应头及重定向由外层页面统一管理。

PHP 直接调用 `Func::part()` 时，不开启、刷新或关闭输出缓冲，嵌套调用也直接输出。模块自行开启的缓冲应由模块配对关闭（包括异常分支），调用方原有缓冲仍由调用方管理。模块遗留的缓冲会继续保留，part 不为此额外抛出异常。

## samples 文件与预期结果

| 文件 | 职责 |
| --- | --- |
| `samples/app/Lab/TemplateController.php` | 父页面控制器，分配 PARENT |
| `samples/app/Widgets/TemplateController.php` | Card、Returned、Mixed、Nested、Ended 和自动模式 Context 模块 |
| `samples/app/templates/workspace/template.tpl` | include 工作台布局及公共模板，执行多个 part |
| `samples/app/templates/widgets/template-shared.tpl` | 跨目录 include，继续 include workspace 下的模板 |
| `samples/app/templates/workspace/template-note.tpl` | 展示嵌套 include 共享 PARENT |
| `samples/app/templates/widgets/template-card.tpl`、`template-meta.tpl` | 独立模块及其 include，共享 CHILD |
| `samples/app/templates/widgets/template-nested.tpl` | 模块内部继续 part，保留 NESTED |
| `samples/app/templates/widgets/template-context.tpl` | 全部修饰器、串联、HTML/URL、JS/事件、CSS 和缓存结果 |
| `samples/app/templates/widgets/template-default.tpl` | 实际使用默认 {}，展示美元符号、JS/CSS 花括号、原生模板字符串和 literal |
| `samples/app/templates/widgets/template-cache.tpl` | 同一模板先后接收 FIRST、SECOND，验证缓存不冻结数据 |
| `samples/app/templates/widgets/template-unsafe-url.tpl`、`template-ambiguous-js.tpl` | 控制器分别捕获并丢弃输出的错误示例 |

页面预期展示：include 两次读取到 PARENT；Card 及其 include 读取到 CHILD；返回字符串包含 demo；混合输出先显示「先直接输出」，再显示「再返回字符串」；嵌套模块保持 NESTED；Ended 输出后父页面继续显示 PARENT。可单独访问 `/widgets/template/card/direct` 查看路径参数 direct。

页面第 5 节通过 part 调用 `/widgets/template/context`，该模块和外层工作台均默认自动转义，各自创建 Tpl 实例。也可单独打开这个模块。用例包括：

- 六种内置修饰器和 `default|escape` 串联；翻译、调试数组、资源版本和可信 HTML 均实际输出。
- 带引号的 input 属性与 URL 查询参数；`escape:"url"` 不出现 `%25` 二次编码。
- JS 对象值、单/双引号字符串、反引号模板字符串、正则、动态 raw 入口，以及按钮 onclick。浏览器执行后显示 JSON；点击按钮将原始示例文本作为 textContent 写入右侧。
- CSS 字符串与 color 简单值；特殊引号和尖括号作为 CSS 数据处理。
- 所有 samples 均使用默认分隔符；`${$price}` 显示 `$12.50`，原生 JS/CSS 花括号保持有效，literal 保留原生对象简写。浏览器计算结果为 `7 / sum:3 / native`。
- 同一缓存模板两次输出 `<FIRST>` 和 `<SECOND>`，不会沿用第一次的变量值。
- 危险 URL、歧义 JS 位置仍抛出 `InvalidArgumentException`；控制器只为展示这些错误而捕获独立渲染，丢弃其未完成输出，Tpl 本身不提供逐标签错误隔离。

这些说明及结果支持简体中文、繁体中文和英文；通过页面语言切换即可比较。

部署示例后，通过浏览器访问 `/lab/template/entry`，对照上述预期检查跨目录 include、嵌套 part、变量隔离与输出顺序。刷新页面检查编译缓存复用，并单独访问模块 URL 检查路径参数与动态文本转义。
