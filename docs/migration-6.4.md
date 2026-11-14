# 升级到 SlightPHP 6.4.0

6.4.0 将 Tpl 模板变量改为实例属性，`assign()`、`fetch()` 改为实例方法，不同对象之间隔离模板数据。这是不兼容变更，已有 6.3.x 应用也需检查静态调用、直接访问变量表的代码及自定义模板函数。完整版本记录见 [Changelog](../CHANGELOG.md)，接口说明见[模板插件](plugins.md#tpl)。

控制器继续沿用 6.3 的 Request / Response 参数。从 6.2.x 或更早版本升级时，还需完成 [6.3 迁移指南](migration-6.3.md)中的控制器签名和数据库迁移；更早版本的基础变更见 [6.2 升级说明](migration-6.2.md)和 [6.0 迁移指南](migration-6.0.md)。

## 安装

PHP 和依赖要求保持为 PHP 8.0+、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha。在应用项目中更新依赖：

```bash
composer require hetao29/slightphp:^6.4
```

仓库自身使用 `composer install` 安装锁定依赖。旧的 `^6.3`、`^6.2` 约束也允许解析到 6.4.0，更新依赖时应一并完成下面的模板迁移。

## 模板变量与实例

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

模板中的 `{%include "child.tpl"%}` 和 `{%include $child%}` 写法保持不变，编译器自动传入当前实例，多层子模板共享父模板数据。PHP 代码直接调用辅助函数时改为 `tpl_function_include('child.tpl', $tpl)`；自定义模板函数应显式接收或持有需要的实例。同一个实例在连续调用 `assign()`、`render()`、`display()`、`fetch()` 时保留变量，包括模板循环产生的赋值；常驻进程按请求创建新实例。

旧编译缓存缺少新格式标记，首次使用时会自动重新编译，无需逐个修改缓存文件。目录布局及模板修改时间检查保持不变。目录、分隔符、安全模式和强制编译选项仍为所有实例共享的静态配置。

## part 的输出与缓冲

`tpl_function_part($path)` 现在返回收集到的 HTML 字符串，内容为模块直接输出加字符串返回值。修复旧实现未关闭输出缓冲的问题，缓冲可移除时，嵌套调用与异常退出也会恢复调用前的缓冲层数。无法移除模块遗留的缓冲时会抛出 `RuntimeException` 并停止清理，控制器原有异常优先保留。空路径或未匹配入口返回空字符串；布尔值、数字、数组或对象不作为 HTML 输出。依赖 part 返回控制器原始结果的 PHP 代码应改用 `SlightPHP::run()`。

模块调用 `Response::end()` 后，片段输出保留，父页面继续渲染。异常会丢弃该片段的部分输出并继续抛出。调用方式、变量作用域及完整 samples 示例见 [Tpl 模板与模块组合手册](templates.md)。

## 部署与验证

部署时确保运行用户能够重写旧编译文件，并重启常驻进程以加载新的类定义。启用 OPcache 时，由发布流程管理字节码缓存刷新或 PHP 工作进程重启。

- 搜索并迁移 `Tpl::assign()`、`Tpl::fetch()`、`Tpl::$_tpl_vars`，检查子类覆盖的方法和自定义模板函数。
- 验证不同实例之间变量隔离，同一个实例连续渲染时保留已分配变量。
- 验证静态及动态文件名的多层 include，确认父子模板共享变量。
- 验证模板内渲染另一实例时外层变量保持原值，以及旧编译缓存自动重建。
- 验证 part 的直接输出、字符串返回、混合输出及异常分支；检查自定义代码对原始返回值的依赖。

仓库示例可运行 `php samples/tests/routing.php` 检查真实入口、自动加载、模板和 URI 解析；实际 HTTP 验证方式见[部署文档](deployment.md#验证)。
