# 核心异常与迁移

从 6.5.0 起，`SlightPHP` 核心失败不再返回 `false`，统一抛出 `SlightPHP\Exception`。Route 方法不匹配也只抛异常，不由核心发送响应。这是不兼容变更，应用入口、CLI 任务与内部调用都需要调整；完整升级步骤见 [6.5 迁移指南](migration-6.5.md)。

异常继承 `RuntimeException`，通过 Composer 自动加载：

| 接口 | 内容 |
| --- | --- |
| `getCode()` | 本类常量定义的原因码，不是 HTTP 状态码 |
| `getMessage()` | 诊断消息，可能涉及内部实现，不建议直接展示给客户端 |
| `getContext()` | 路径、控制器、方法等诊断数据；按具体失败提供 |
| `getStatusCode()` | 建议的 HTTP 状态码，不会发送响应；应用可自行映射 |
| `getPrevious()` | 可选的原始异常，例如候选文件校验失败 |

## 失败原因

以下常量均属于 `SlightPHP\Exception`：

| 原因码 | 触发条件 | 建议 HTTP 状态 |
| --- | --- | --- |
| `PATH_NOT_SET` | 显式路径、pathInfo、PATH_INFO、REQUEST_URI 均未提供 | 400 |
| `INVALID_PATH` | `parse_url` 无法解析路径 | 400 |
| `PATH_TOO_DEEP` | 路径分段超过 maxDepth | 400 |
| `RECURSIVE_DISPATCH` | 内部调用指向当前顶层入口 | 500 |
| `APP_DIR_INVALID` | 应用目录不存在或不是目录 | 500 |
| `FILE_NOT_FOUND` | 最终找不到控制器普通文件 | 404 |
| `FILE_NOT_ALLOWED` | 规范文件路径位于 appDir 之外 | 404 |
| `CLASS_NOT_FOUND` | 文件加载后没有声明预期的控制器类 | 500 |
| `METHOD_NOT_FOUND` | 控制器没有目标入口方法 | 404 |
| `METHOD_NOT_CALLABLE` | 入口存在但不可公开调用 | 404 |
| `METHOD_NOT_ALLOWED` | HTTP 方法不符合 Route 注解，或候选方法名的已配置类型前缀与 Request-Type 不匹配 | 405 |
| `ALIAS_NOT_FOUND` | getZoneAlias / getPageAlias 查询未设置的别名 | 500 |

路径超限在加载控制器前抛出。Route 拒绝请求在构造控制器前抛出；其上下文包含 `requestMethod`、`allowedMethods`、`controller`、`method` 和 `path`。

从 6.5.2 起，请求类型前缀校验紧接候选方法名赋值，先于方法存在性、Route 注解和控制器构造。类型不匹配时，上下文另含 `requestType`（规范化后的当前头，缺失为空字符串）及 `requiredRequestType`（方法前缀要求的类型），`allowedMethods` 为空数组，因为此时尚未解析 Route 注解。`allowedMethods` 仅用于 HTTP 方法限制，不能将 `ajax`、`part` 等类型写入 HTTP `Allow` 头；应用仅在该数组非空时输出 `Allow`。

应用目录在设置或首次使用时按目录路径规范化并缓存；调度和候选解析不再重复调用 `is_dir()`。不存在的路径或普通文件仍判为 `APP_DIR_INVALID`，候选控制器仍需通过普通文件和应用目录边界校验。

框架仍从深到浅查找候选控制器。通过请求类型校验后，中途缺失文件、类或方法时会继续查找，找到合法入口仍可执行；最终未匹配才抛出诊断异常。已经找到控制器的具体失败原因不会被后续的缺失文件覆盖。请求类型或 HTTP 方法不匹配时立即抛出，不尝试浅层入口。

普通候选文件缺失或不是普通文件时，使用普通分支继续查找；类或入口不可用时也只记录最终诊断，避免成功回退时创建无用异常。最终文件缺失抛出 `FILE_NOT_FOUND`，不再为普通缺失构造中间异常链；文件越界等校验异常仍保留原始原因。控制器文件、构造函数和入口自身抛出的异常不参与候选回退。

## 应用入口

将原来的 `$result === false` 调度失败判断改为捕获异常。由应用设置状态码、响应头和正文；需要统一 JSON 错误时，也可在此处编码输出：

```php
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

`run()`、异常构造及 `getStatusCode()` 均不会因调度失败直接生成错误正文、设置响应头或修改状态码。显式开启调度 debug 时仍按 Log 配置记录诊断日志。调用方可记录异常消息和上下文，但不要把包含文件路径等内部细节的诊断数据直接回传。

控制器主动返回的 `false` 是正常业务返回值，仍原样返回；不能再用它判断调度失败。通过 Response 结束的成功调用仍返回 `null`。控制器文件、构造函数、入口或注解构造自身抛出的异常原样传播，不会被包装或误认为候选查找失败。`setMaxDepth()` 的非法配置和 Route 注解的非法参数仍沿用 `InvalidArgumentException`。

## 内部调用和别名

`SlightPHP::run('/...')` 和模板 `part` 同样传播调度异常。空 part 路径仍不执行；找不到入口和指向当前顶层入口的调用不再静默跳过。可选模块需要由调用方明确捕获相应原因，并决定是否忽略；已有片段输出和缓冲规则保持不变。

`getZoneAlias()`、`getPageAlias()` 未设置别名时改为抛出 `ALIAS_NOT_FOUND`，不再返回 `false`。成功查询仍返回原字符串，包括空字符串。异常不修改当前顶层 `zone/page/entry`，捕获后可继续进行其他调度。

本次变更针对 `SlightPHP` 核心及 Route 调度，不改变 Request、Response、Db、Captcha 等其他插件各自的布尔返回约定。
