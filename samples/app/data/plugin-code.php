<?php
// 与对应手册一致的最小 API 示例；完整控制器见 Lab/PluginController.php。
return [
    'config' => <<<'PHP'
use SlightPHP\Config;

$all = Config::getConfig('/app/config/settings.jsonc');
$app = Config::getConfig('/app/config/settings.jsonc', 'application');
// 缺失 zone 回退到 default；default 也不存在则为 null。
$fallback = Config::getConfig('/app/config/settings.jsonc', 'missing');
PHP,
    'log' => <<<'PHP'
use SlightPHP\Log;

Log::$CONSOLE = false;
Log::$LOGFILE = '/var/log/myapp/application.log'; // 目录须已存在且可写
Log::$LOGTYPE = 'json';
Log::notice('order.created', ['order_id' => 123]);
Log::error('delivery.failed', ['retry' => true]); // 不终止程序
PHP,
    'db' => <<<'PHP'
use SlightPHP\Db;

$db = new Db();
if (!$db->init($privateConfig)) throw new RuntimeException('Connection failed');
if (!$db->begin()) throw new RuntimeException('Transaction failed');
try {
    $rows = $db->query('SELECT ? AS example_value, 1 + 1 AS calculation', [$message]);
    if ($rows === false) throw new RuntimeException('Query failed');
} finally {
    $db->rollback(); // 本实验没有表写入
}
PHP,
    'redis' => <<<'PHP'
use SlightPHP\Redis;

$redis = new Redis(['127.0.0.1:6379'], ['connect_timeout' => 1, 'read_timeout' => 1]);
$key = 'slightphp:sample:' . bin2hex(random_bytes(12));
try {
    if (!$redis->setex($key, 30, 'Hello SlightPHP')) throw new RuntimeException('SETEX failed');
    $value = $redis->get($key);
    $ttl = $redis->ttl($key);
} finally {
    $redis->del($key);
}
PHP,
    'http' => <<<'PHP'
use SlightPHP\Http;
use GuzzleHttp\Exception\GuzzleException;

try {
    $result = Http::get('https://httpbin.org/anything', ['message' => 'hello'], [], true, 5, ['X-Sample' => 'SlightPHP']);
    // $result['result'] 为正文字符串，$result['header'] 为响应头数组。
    $body = Http::post('https://httpbin.org/anything', ['message' => 'hello']);
    // 数组 / 对象发送为 JSON；不是 form-urlencoded。
} catch (GuzzleException $e) {
    // 在应用中处理超时、连接失败和 HTTP 错误。
}
PHP,
    'error' => <<<'PHP'
use SlightPHP\Error;

// 首次加载即安装 PHP 错误、异常及 shutdown 处理器。
Error::$CONSOLE = false;
Error::$LOG = true;
Error::$LOGARGS = false;
Error::$LOGTYPE = 'json';
Error::$LOGFILE = '/var/log/myapp/error.log';
// 在应用启动阶段配置，而不是在已输出正文后加载。
// 本实验显式调用处理器并读取临时日志，不抛出未捕获异常。
Error::error_handler(E_USER_NOTICE, 'example notice', 'example.php', 1);
PHP,
    'captcha' => <<<'PHP'
use SlightPHP\Captcha;

// Session 先由入口启动；图片入口和校验入口必须使用相同的前缀。
Captcha::$session_prefix = 'sample_captcha';
Captcha::$ttl = 300;
// 图片入口：输出 PNG；不要 echo 返回的答案。
(new Captcha())->CreateImage();

// 独立的 POST 校验入口（同时启用 Route CSRF 防护）：
// $matched = Captcha::check($request->post['captcha'] ?? '');
// 成功即消费，错误保留，过期删除；大小写精确匹配。
PHP,
    'request' => <<<'PHP'
use SlightPHP\{Request, Response, Route};

#[Route(method: 'POST', csrf: true)]
public function Submit(Request $request, Response $response): void
{
    $query = $request->get['message'] ?? null;
    $form = $request->post['message'] ?? null;
    $file = $request->files['attachment'] ?? null;
    $type = $request->header['content-type'] ?? '';
    $method = $request->getMethod();
    $path = $request->route; // zone / controller / entry / 参数
    // JSON 不进入 post；读取后按应用要求解析和校验：
    // $json = json_decode($request->getContent(), true, 16, JSON_THROW_ON_ERROR);
}
PHP,
    'response' => <<<'PHP'
use SlightPHP\{Request, Response};

public function Created(Request $request, Response $response): void
{
    $response->status(201);
    $response->header('Content-Type', 'application/json; charset=utf-8');
    $response->header('X-Sample', 'SlightPHP');
    $response->end(json_encode(['ok' => true], JSON_THROW_ON_ERROR));
    return; // end() 不会 exit，后续应用代码仍会执行。
}

// 在其他入口中，先于正文设置 Cookie 或跳转：
// $response->cookie('slightphp_sample', 'hello', time() + 600);
// $response->redirect('/lab/plugin/entry/response', 303);
PHP,
];
