# Db 使用手册与示例

运行示例：`/lab/plugin/entry/db`。源码：[PluginController.php](../samples/app/Lab/PluginController.php)。示例使用真实 MySQL，通过绑定参数执行只读 SELECT，并在事务结束时 rollback。没有服务配置时显示配置提示，不返回模拟查询结果。

## 准备服务

应用声明 `ext-pdo`、`ext-pdo_mysql` 并安装对应 PHP 扩展，准备一个独立的 MySQL 示例库及可连接的账号。当前适配器按 MySQL 构造 DSN，不能只改 engine 名称就连接 SQLite 或 PostgreSQL。

复制 [services.example.json](../samples/config/services.example.json) 为 `samples/config/services.local.json`，填写 db 段并将 enabled 改为 true。文件位于 Web 根目录之外，已被 Git 忽略。也可通过 PHP 服务的 `SLIGHTPHP_SAMPLE_SERVICES` 环境变量指定另一个私有 JSON 文件的绝对路径。不要在可公开访问的文件或提交中存储凭据。

## 连接、绑定与事务

```php
use SlightPHP\Db;

$db = new Db();
$connected = $db->init([
    'engine' => 'pdo_mysql', 'host' => '127.0.0.1', 'port' => 3306,
    'database' => 'slightphp_sample', 'user' => 'sample_reader',
    'password' => $privatePassword, 'charset' => 'utf8mb4', 'persistent' => false,
]);
if (!$connected) throw new RuntimeException('Connection failed');
if (!$db->begin()) throw new RuntimeException('Transaction failed');
try {
    $rows = $db->query('SELECT ? AS example_value, 1 + 1 AS calculation', [$message]);
    if ($rows === false) throw new RuntimeException('Query failed');
} finally {
    $db->rollback();
}
```

业务写入事务通常在所有步骤成功后调用 `commit()`；任一步失败则 rollback。检查返回值，捕获应用异常；底层连接或查询失败也可能产生 PHP 警告，需由应用错误策略记录。不要将 SQL、驱动原始错误或凭据直接输出给访客。

`query()` 对有结果列的查询返回行数组；INSERT / REPLACE 返回最后插入 ID；其他无结果列语句返回受影响行数；失败返回 false。用 `=== false` 区分失败和合法的空数组或 0。

## 表操作

```php
$id = $db->insert('sample_tasks', ['title' => 'read docs', 'done' => 0]);
$row = $db->selectOne('sample_tasks', ['id' => $id]);
$changed = $db->update('sample_tasks', ['id' => $id], ['done' => 1]);
$deleted = $db->delete('sample_tasks', ['id' => $id]);

$db->setPage(1);
$db->setLimit(20);
$db->setCount(true);
$rows = $db->select('sample_tasks', ['done' => 0], '*', '', 'id DESC');
```

上例需要应用自行创建 `sample_tasks` 表，在线实验不执行这些写入。表名、字段名、排序和 SQL 片段只从应用固定列表选择；占位符只能绑定值，不能安全替换标识符。分页及条件构建详情见 [API 参考](index.html#SlightPHP\Db) 和[完整示例](samples.md)。

## 验证结果

输入包含单引号的消息，结果 example_value 应完整保持原文，calculation 为 2；transaction 显示 rollback。未启用服务时按钮禁用；启用但连接失败时显示失败提示，不显示成功数据或私有配置。
