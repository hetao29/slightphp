# Tpl 模板与模块组合手册（6.4.0）

运行 samples 后访问 `/lab/template/entry`，或从 `/site/main/examples` 的「模板与模块组合」进入。工作台侧栏也提供同名入口。该页面实际执行跨目录 include、跨模块 part 和嵌套调用，无需数据库或 Redis。

## 选择 include 或 part

| 调用 | 执行内容 | 模板变量 | 输出 |
| --- | --- | --- | --- |
| `{%include "widgets/template-shared.tpl"%}` | 加载模板文件 | 使用当前 Tpl 实例，读写共享变量 | 在当前位置直接输出子模板 |
| `{%include $include_child%}` | 从模板变量读取文件名 | 同上，可继续嵌套 include | 同上 |
| `{%part "/widgets/template/card/demo"%}` | 调用控制器入口方法 | 由控制器使用的 Tpl 实例决定；新实例有独立变量 | 收集直接输出并追加字符串返回值，插入当前位置 |
| `{%part $module_path%}` | 从模板变量读取控制器路径 | 同上 | 同上 |

以下示例使用 samples 配置的 `{%` / `%}` 分隔符。默认分隔符为 `{` / `}`；通过 `Tpl::setDelimter()` 配置。

## 初始化与变量生命周期

```php
use SlightPHP\Tpl;

Tpl::setTemplateDir(__DIR__.'/templates');
Tpl::setCompileDir(__DIR__.'/var/templates_c');
Tpl::setDelimter('{%', '%}');

$tpl = new Tpl();
$tpl->assign('scope', 'PARENT');
$tpl->render('workspace/template.tpl', [
    'include_child' => 'widgets/template-shared.tpl',
]);
```

`assign()`、`render()`、`display()` 和 `fetch()` 都使用当前实例的数据。`render()` 参数覆盖同名变量，未覆盖的数据保留；循环和子模板赋值也保留在该实例。常驻服务按请求创建新实例。模板目录、编译目录、分隔符及安全模式等选项仍由所有实例共享。

`render()`、`display()`、`fetch()` 直接输出，返回值为 null。`fetch()` 要求调用前已设置目录与分隔符；`render()` 可初始化缺省配置。6.3.x 的静态调用及变量表访问方式需按 [6.4 迁移指南](migration-6.4.md)调整。

## include：跨目录复用模板

模板路径从 `template_dir` 根目录解析。例如 `workspace/template.tpl` 中的：

```html
{%include $include_child%}
```

当 `$include_child` 为 `widgets/template-shared.tpl` 时，加载 `templates/widgets/template-shared.tpl`。该文件读取同一个 `$scope`，并继续加载另一目录的模板：

```html
<p>include 读取到：{%$scope|escape%}</p>
{%include "workspace/template-note.tpl"%}
```

两层子模板都读取到 `PARENT`。子模板对变量的赋值会影响后续父模板；需要独立变量时，由另一个控制器创建新 Tpl 实例，或在 PHP 代码中显式创建实例。

include 标签名不区分大小写，`include`、`Include` 和 `INCLUDE` 都会传入当前 Tpl 实例。include 加载模板文件，控制器方法通过 part 调用。路径及编译文件仍受目录边界检查，越界或文件缺失会抛出 `RuntimeException`。文件名应由应用的固定值或受控映射提供。

PHP 代码中的对应调用为：

```php
$tpl->fetch('widgets/template-shared.tpl');
// 辅助函数需要显式传入当前实例。
tpl_function_include('widgets/template-shared.tpl', $tpl);
```

## part：调用其它模块的方法

父模板调用：

```html
{%part "/widgets/template/card/demo"%}
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

Card 的模板及其 include 读取到 `CHILD`，调用结束后父模板仍读取到 `PARENT`。part 通过 `SlightPHP::run($path)` 创建控制器与新的 Request / Response；路径数组对应显式路径，请求的 GET、POST、Cookie、请求头等仍来自当前 PHP 请求。URL 路径参数保留原始内容，不自动 URL 解码；路径后的查询串不会合成为新的 GET 参数。`Request-Type` 前缀规则也适用于内部调用。

part 在同一个 PHP 请求中同步执行，session、超全局变量和响应头仍属于整个请求；它保留顶层 `zone/page/entry`。指向当前顶层入口的调用会被跳过，其他循环调用需由应用控制。片段路径使用固定值或白名单；模块方法负责权限、输入校验及输出转义。

PHP 中可以直接取得片段字符串：

```php
$html = tpl_function_part('/widgets/template/card/demo');
echo $html;
```

## 返回值、输出顺序与异常

| 模块方法行为 | part 得到的字符串 |
| --- | --- |
| 只 echo、调用 Tpl 渲染或 `Response::write()` | 已输出的内容 |
| 只返回 HTML 字符串 | 返回的字符串 |
| 先输出，再返回字符串 | 先输出的内容 + 返回字符串，各一次 |
| 返回 true、false、数字、数组或对象 | 只保留已输出的内容，忽略该返回值 |
| 调用 `Response::end('HTML')` | 保留 HTML；父页面继续渲染 |
| 空路径或找不到入口 | 空字符串 |
| 控制器或模板抛出异常 | 清理可移除的片段缓冲，丢弃其中的部分输出，继续抛出原异常 |

part 返回可嵌入的 HTML 字符串；需要控制器原始的非字符串结果时，在 PHP 代码中调用 `SlightPHP::run()`。模块负责将动态内容转义为适当的 HTML，part 不会自动转义整个片段。片段渲染不会回滚 session 等业务副作用；响应状态、响应头及重定向由外层页面统一管理。

6.4.0 修复了旧 part 只开启而未关闭输出缓冲的问题。模块缓冲可移除时，正常完成、空路径、未匹配路径及异常退出均恢复调用前的缓冲层数；嵌套 part 会各自收集内容。模块自行开启的可移除缓冲会按顺序并入当前片段。

模块遗留未设置 `PHP_OUTPUT_HANDLER_REMOVABLE` 的缓冲时，part 抛出 `RuntimeException` 并停止清理，避免无限循环；若控制器已抛出异常，则保留该原始异常。PHP 无法移除的缓冲及其下层缓冲仍会保留，此时不能保证恢复层数或丢弃全部片段输出。模块应使用可移除的缓冲，并自行配对关闭。

## samples 文件与预期结果

| 文件 | 职责 |
| --- | --- |
| `samples/app/Lab/TemplateController.php` | 父页面控制器，分配 PARENT |
| `samples/app/Widgets/TemplateController.php` | Card、Returned、Mixed、Nested、Ended 模块方法 |
| `samples/app/templates/workspace/template.tpl` | include 工作台布局及公共模板，执行多个 part |
| `samples/app/templates/widgets/template-shared.tpl` | 跨目录 include，继续 include workspace 下的模板 |
| `samples/app/templates/workspace/template-note.tpl` | 展示嵌套 include 共享 PARENT |
| `samples/app/templates/widgets/template-card.tpl`、`template-meta.tpl` | 独立模块及其 include，共享 CHILD |
| `samples/app/templates/widgets/template-nested.tpl` | 模块内部继续 part，保留 NESTED |

页面预期展示：include 两次读取到 PARENT；Card 及其 include 读取到 CHILD；返回字符串包含 demo；混合输出先显示「先直接输出」，再显示「再返回字符串」；嵌套模块保持 NESTED；Ended 输出后父页面继续显示 PARENT。可单独访问 `/widgets/template/card/direct` 查看路径参数 direct。

运行真实入口检查：

```bash
php samples/tests/tpl-composition.php
```

检查自动使用独立临时编译与会话目录，覆盖冷/热缓存、跨目录 include、嵌套 part、变量隔离、输出顺序、缓冲层数、独立模块 URL 与动态参数转义。插件层测试为 `php tests/TplPartTest.php`，额外覆盖 GET 快照、空路径、缺失入口、各类返回值、模块遗留缓冲、异常清理及顶层自调用。
