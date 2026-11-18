# 升级到 SlightPHP 6.7.0

6.7.0 将 Tpl 上下文自动转义设为统一行为，删除兼容编译分支及 `setContextMode()`、`getContextMode()`。这是不兼容变更，即使 6.6 中从未调用模式接口，也需要迁移依赖原样输出的模板。完整记录见 [Changelog](../CHANGELOG.md)；从 6.5.x 或更早版本升级，还需完成 [6.6 迁移](migration-6.6.md)。

## 安装与接口移除

```bash
composer require hetao29/slightphp:^6.7.0
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

## 安全模式与编译入口移除

删除 `Tpl::$safe_mode` 和 `Tpl::setSafeMode()` 的访问或调用；不再提供安全模式开关，也不再将原生 PHP 标签替换成注释。静态的 `<?php ... ?>` / `<?= ... ?>` 保留为模板字面量，不作为 PHP 代码执行；动态模板逻辑继续使用 `{...}` 表达式及控制标签。模板是可信代码，上下文编码不将其变成沙箱。

`Tpl::_compile()` 已移出入口类，源码编译统一由 `Tpl\Compiler::compileSource($source, $left, $right)` 完成分词和代码生成；应用仍通过 render/fetch 使用编译缓存。缓存文件头不再包含安全模式配置，旧配置生成的缓存自动失效并重建。

## 手工编码与原始数据

模板应接收原始数据。仅用于 HTML 输出防护的 `htmlspecialchars()`、`htmlentities()`，以及独立 URL 组成部分的 `urlencode()`、`rawurlencode()` 通常可以移除：

| 旧写法 | 6.7 推荐写法 |
| --- | --- |
| `{htmlspecialchars($address['address'] ?? '')}` | `{$address['address'] ?? ''}` |
| `<a href="/search?q={urlencode($q)}">` | `<a href="/search?q={$q}">` |

普通函数返回值没有编码意图标记，保留预编码可能再次编码。例如原始值 `A&B <b>` 直接插值生成 `A&amp;B &lt;b&gt;`；先 `htmlspecialchars()` 再插值则会生成 `A&amp;amp;B &amp;lt;b&amp;gt;`。已编码的存量数据不会自动还原，应在明确其来源后迁移。

全部内置 Modifier 仍可用。末尾 HTML `|escape` 有专门的编码意图处理，可以保留；它与普通 `htmlspecialchars()` 的返回值不同。URL escape 的去重只适用于已有前缀的 HTML URL 属性组成部分，不能推广到任意上下文。修饰链先转换数据，再按实际位置编码。

完整 URL 仍需由业务代码正确构造，例如 `http_build_query()`、路径编码和签名编码不能批量删除。`<a href="{$url}">` 将值视为完整 URL，检查协议并执行 HTML 属性编码，不会替其中每个参数重新编码。JS 字符串中的 URL 仍需保留 URL 构造，再由模板执行 JS 字符串编码。URL 组成部分的空格采用 `%20`，与 `urlencode()` 的 `+` 不同，精确字节、缓存键与签名需核对。

## 可信 HTML、属性与输出函数

只有已确认可信且适合当前位置的原文使用末尾 `|raw`：

```html
<article>{$document['html']|raw}</article>
<p {if $hidden}hidden{/if}>{$message}</p>
```

文档示例中的 `document['html']` 必须来自应用自己的安全渲染器，不能直接传用户输入。raw 会跳过最终编码，并继续影响后续的真实上下文；不要用它批量绕过迁移。

自动模式拒绝动态标签名、属性名及动态标记。不要写 `<p {$hidden ? 'hidden' : ''}>`，应像上例那样用条件输出完整静态属性。动态属性值仍可直接插值。

普通表达式、函数及 Stringable/JSON 转换回调必须返回数据，直接 echo、改变输出缓冲或嵌套渲染改变已选上下文会报错。需要输出模块 HTML 时使用模板 include/part；part 只能位于 HTML 文本中，并临时收集可信片段。模块异常时丢弃该失败片段的缓冲，之前已提交的页面内容保留；业务副作用不回滚。PHP 直接调用 `Tpl\Func::part()` 仍直接输出，不管理调用方缓冲。

## JSON 数据块与翻译

JSON 数据块直接传数组/对象，不要先 json_encode 后再把 JSON 字符串当作数据输出：

```html
<script type="application/json">{$messages}</script>
<script>const messages = {$messages};</script>
```

第一种输出标准 JSON，第二种输出带表达式括号的 JS 值。将已有 JSON 字符串直接插入数据块会得到 JSON 字符串，而不是原来的对象；优先在业务层保留结构化数据。

模板翻译应返回原文，让 Tpl 执行最终编码。samples 的模板已从 `SampleI18n::e()` 改为 `SampleI18n::t()`；PHP 直接拼接 HTML 的位置仍保留显式转义。`SampleI18n::url()` 构造完整 URL，`clientMessages()` 返回数组，由模板分别完成属性编码和 JSON 序列化。

## 编译缓存与升级验证

编译格式升级为 v10，统一保留 `.context.php` 文件后缀。6.6 的上下文编译文件会自动重建；旧兼容文件不再执行。编译目录必须可写，常驻进程需重启，并按发布流程刷新 OPcache。缓存不保存变量或最终页面输出，完整计划命中后仍无需 JS 词法扫描。

大模板首次编译仍有分析成本，建议预热模板及所需上下文变体。上下文编码及校验仍有运行成本，不能将重复值编码复用的收益推广到所有模板；上线前分别评估首次编译与缓存命中情况。

升级时检查普通文本、input 值、实际链接、JSON/JS/CSS、可信 HTML、条件属性、include/part 及异常处理。samples 的官网、工作台、文档浏览器和独立模块均已迁移到默认自动转义，模板实验入口为 `/lab/template/entry`，新迁移指南可在 `/site/main/docs?doc=migration-6.7.md` 阅读。

应用项目仍可管理自己的锁文件；框架仓库的 `composer.lock` 已加入 `.gitignore` 并停止跟踪，本地文件保留。完整输出上下文和支持边界见 [Tpl 手册](templates.md#上下文自动转义)。
