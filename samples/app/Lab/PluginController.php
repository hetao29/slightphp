<?php
namespace Lab;

use SampleI18n;
use SlightPHP\{Captcha, Config, Csrf, Db, Error, Http, Log, Redis, Request, Response, Route, Tpl};

/** 可运行的插件实验；外部服务地址只从管理员的私有配置读取。 */
class PluginController
{
    private const PLUGINS = ['config', 'log', 'db', 'redis', 'http', 'error', 'captcha', 'request', 'response'];
    private const CAPTCHA_KEY = 'slightphp_sample_captcha';

    #[Route(method: 'GET')]
    public function Entry(Request $request, Response $response): void
    {
        $id = $this->id($request);
        $result = $_SESSION['plugin_lab_result'][$id] ?? null;
        unset($_SESSION['plugin_lab_result'][$id]);
        $ready = $this->ready($id);
        if ($id === 'config') {
            $format = $request->get['format'] ?? 'jsonc';
            $zone = $request->get['zone'] ?? 'application';
            if (!in_array($format, ['json', 'jsonc'], true) || !in_array($zone, ['application', 'default', 'missing', 'whole'], true)) {
                $response->status(400);
                $format = 'jsonc';
                $zone = 'application';
            }
            $path = dirname(__DIR__, 2) . '/config/example.' . $format;
            $value = Config::getConfig($path, $zone === 'whole' ? null : $zone);
            $result = ['ok' => true, 'data' => ['file' => 'config/example.' . $format, 'zone' => $zone, 'type' => get_debug_type($value), 'value' => $value, 'source' => file_get_contents($path)]];
        }
        if ($id === 'request' && $result === null) {
            try {
                $result = ['ok' => true, 'data' => $this->requestData($request, 'query')];
            } catch (\InvalidArgumentException $e) {
                $response->status(400);
                $result = ['ok' => false, 'error' => SampleI18n::t('lab_invalid')];
            }
        }
        if ($id === 'response' && $result === null) {
            $result = ['ok' => true, 'data' => ['sample_cookie' => $request->cookie['slightphp_sample'] ?? null, 'defaults' => Response::getCookieDefaults()]];
        }
        $codes = require dirname(__DIR__) . '/data/plugin-code.php';
        (new Tpl())->render('workspace/plugin.tpl', [
            'view' => 'pluginlab', 'id' => $id, 'name' => ucfirst($id), 'ready' => $ready,
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', SampleI18n::t('task_workspace')],
                'route' => ['/lab/route/entry', '02', SampleI18n::t('route_lab')],
                'template' => ['/lab/template/entry', '03', SampleI18n::t('templates_and_modules')],
                'language' => ['/lab/language/entry', '04', SampleI18n::t('language_example')],
                'csrf' => ['/lab/csrf/entry', '05', SampleI18n::t('csrf_demo_title')],
                'pluginlab' => ['/site/main/plugins', '06', SampleI18n::t('lab_catalog')],
            ],
            'labs' => self::PLUGINS, 'token' => Csrf::token(),
            'format' => $format ?? '', 'zone' => $zone ?? '',
            'result' => $result === null ? '' : self::encode($result),
            'code' => $codes[$id], 'manual' => $id === 'http' ? 'http-client' : $id,
        ]);
    }

    /** 所有产生写入或外部请求的实验通过 CSRF 保护的 POST 执行。 */
    #[Route(method: 'POST', csrf: true)]
    public function Run(Request $request, Response $response): void
    {
        $id = $this->id($request);
        try {
            if (!$this->ready($id)) throw new \RuntimeException(SampleI18n::t('lab_not_ready'));
            $message = $this->text($request->post, 'message', 'Hello SlightPHP');
            $data = match ($id) {
                'log' => $this->log($request, $message),
                'error' => $this->error($request, $message),
                'db' => $this->database($message),
                'redis' => $this->redis($message),
                'http' => $this->http($request, $message),
                'captcha' => $this->captcha($request),
                'request' => $this->requestData($request, 'form'),
                'response' => $this->cookie($request, $response),
                default => throw new \InvalidArgumentException(SampleI18n::t('lab_invalid')),
            };
            $_SESSION['plugin_lab_result'][$id] = ['ok' => true, 'data' => $data];
        } catch (\InvalidArgumentException $e) {
            $response->status(400);
            $response->header('Content-Type', 'text/plain; charset=utf-8');
            $response->end(SampleI18n::t('lab_invalid'));
            return;
        } catch (\Throwable $e) {
            // 驱动异常可能包含凭据、文件路径或上游正文，不直接回显。
            $_SESSION['plugin_lab_result'][$id] = ['ok' => false, 'error' => SampleI18n::t('lab_failed'), 'exception' => get_class($e)];
        }
        $response->redirect('/lab/plugin/entry/' . $id, 303);
    }

    /** JSON 正文通过 getContent() 获取，框架不会自动填充 post。 */
    #[Route(method: 'POST', csrf: true)]
    public function Decode(Request $request, Response $response): void
    {
        $response->header('Content-Type', 'application/json; charset=utf-8');
        try {
            if (strlen($request->getContent() ?: '') > 4096) throw new \InvalidArgumentException();
            $decoded = json_decode($request->getContent(), true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)) throw new \InvalidArgumentException();
            $message = $this->text($decoded, 'message');
            $response->end(self::encode(['method' => $request->getMethod(), 'post' => $request->post,
                'raw' => $request->rawContent(), 'message' => $message,
                'content-type' => $request->header['content-type'] ?? null,
                'x-sample' => $request->header['x-sample'] ?? null]));
        } catch (\JsonException | \InvalidArgumentException $e) {
            $response->status(400);
            $response->end(self::encode(['error' => SampleI18n::t('lab_invalid')]));
        }
    }

    /** 独立响应让浏览器观察真实的状态、响应头和正文。 */
    #[Route(method: 'GET')]
    public function Preview(Request $request, Response $response): void
    {
        $kind = $request->get['kind'] ?? 'json';
        if (!in_array($kind, ['json', 'text', 'ended', 'redirect'], true)) {
            $response->status(400);
            $response->end(SampleI18n::t('lab_invalid'));
            return;
        }
        if ($kind === 'redirect') {
            $response->redirect('/lab/plugin/entry/response?redirected=1', 303);
            return;
        }
        $response->header('Cache-Control', 'no-store');
        $response->header('X-Sample', 'SlightPHP');
        if ($kind === 'json') {
            $response->status(201);
            $response->header('Content-Type', 'application/json; charset=utf-8');
            $response->end(self::encode(['ok' => true, 'status' => 201]));
            return;
        }
        $response->header('Content-Type', 'text/plain; charset=utf-8');
        $response->status($kind === 'text' ? 418 : 200);
        $response->write("write: first\n");
        $response->end("end: last\n");
        // end() 不 exit，但同一实例后续 write() 返回 false，不会输出此内容。
        if ($kind === 'ended') $response->write('This must not appear');
    }

    #[Route(method: 'GET')]
    public function Image(Request $request, Response $response): void
    {
        $response->header('Cache-Control', 'no-store');
        if (!$this->ready('captcha')) {
            $response->status(503);
            $response->end(SampleI18n::t('lab_not_ready'));
            return;
        }
        $prefix = Captcha::$session_prefix;
        try {
            Captcha::$session_prefix = self::CAPTCHA_KEY;
            (new Captcha())->CreateImage(); // 返回值是答案，绝不能追加到图片或页面。
        } finally {
            Captcha::$session_prefix = $prefix;
        }
    }

    /** @return string 已验证的实验标识。 */
    private function id(Request $request): string
    {
        $id = $request->route[3] ?? 'config';
        if (!in_array($id, self::PLUGINS, true)) throw new \SlightPHP\Exception('Unknown plugin lab', \SlightPHP\Exception::METHOD_NOT_FOUND);
        return $id;
    }

    /** @return array<string,mixed> 仅供服务端使用，绝不输出私有连接配置。 */
    private function services(): array
    {
        $path = getenv('SLIGHTPHP_SAMPLE_SERVICES') ?: dirname(__DIR__, 2) . '/config/services.local.json';
        if (!is_file($path)) return [];
        $data = json_decode(file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new \RuntimeException('Invalid sample services configuration');
        return $data;
    }

    private function ready(string $id): bool
    {
        try {
            return match ($id) {
                'db' => extension_loaded('pdo_mysql') && !empty($this->services()['db']['enabled']),
                'redis' => extension_loaded('redis') && !empty($this->services()['redis']['enabled']),
                'http' => class_exists('GuzzleHttp\\Client'),
                'captcha' => extension_loaded('gd') && extension_loaded('mbstring') && extension_loaded('fileinfo') && class_exists('Gregwar\\Captcha\\CaptchaBuilder'),
                default => true,
            };
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @param array<string,mixed> $values @return string 最多 200 字节的表单文本。 */
    private function text(array $values, string $key, string $default = ''): string
    {
        $value = $values[$key] ?? $default;
        if (!is_string($value) || strlen($value) > 200 || !preg_match('//u', $value)) throw new \InvalidArgumentException();
        return $value;
    }

    private function choice(Request $request, string $key, array $allowed): string
    {
        $value = $request->post[$key] ?? $allowed[0];
        if (!in_array($value, $allowed, true)) throw new \InvalidArgumentException();
        return $value;
    }

    private static function encode(mixed $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    }

    private function log(Request $request, string $message): array
    {
        $format = $this->choice($request, 'format', ['json', 'text']);
        $level = $this->choice($request, 'level', ['notice', 'warning', 'error', 'debug']);
        $file = tempnam(sys_get_temp_dir(), 'slightphp-log-');
        if ($file === false) throw new \RuntimeException('Cannot create log');
        $previous = [Log::$LOGFILE, Log::$LOGTYPE, Log::$CONSOLE];
        try {
            Log::$LOGFILE = $file;
            Log::$LOGTYPE = $format;
            Log::$CONSOLE = false;
            Log::$level($message, ['example' => true]);
            return ['format' => $format, 'level' => $level, 'output' => file_get_contents($file)];
        } finally {
            [Log::$LOGFILE, Log::$LOGTYPE, Log::$CONSOLE] = $previous;
            unlink($file);
        }
    }

    private function error(Request $request, string $message): array
    {
        $format = $this->choice($request, 'format', ['json', 'text', 'html']);
        $kind = $this->choice($request, 'kind', ['notice', 'exception']);
        $loaded = class_exists(Error::class, false);
        class_exists(Error::class); // 首次加载会注册处理器；本实验只显式调用处理器。
        if (!$loaded) {
            restore_error_handler();
            restore_exception_handler();
        }
        $file = tempnam(sys_get_temp_dir(), 'slightphp-error-');
        if ($file === false) throw new \RuntimeException('Cannot create log');
        $previous = [Error::$LOGFILE, Error::$LOGTYPE, Error::$CONSOLE, Error::$LOG, Error::$LOGARGS];
        try {
            Error::$LOGFILE = $file;
            Error::$LOGTYPE = $format;
            Error::$CONSOLE = false;
            Error::$LOG = true;
            Error::$LOGARGS = false;
            if ($kind === 'notice') Error::error_handler(E_USER_NOTICE, $message, 'plugin-example.php', 1);
            else Error::exception_handler(new \RuntimeException($message));
            $output = str_replace(dirname(__DIR__, 3), '[project]', file_get_contents($file));
            return ['format' => $format, 'kind' => $kind, 'output' => $output];
        } finally {
            [Error::$LOGFILE, Error::$LOGTYPE, Error::$CONSOLE, Error::$LOG, Error::$LOGARGS] = $previous;
            if (!$loaded) Error::$LOG = false; // 本实验注册的 shutdown 回调不写入真实错误日志。
            unlink($file);
        }
    }

    private function database(string $message): array
    {
        $db = new Db();
        $params = $this->services()['db'];
        unset($params['enabled']);
        return $this->withWarnings(function () use ($db, $params, $message): array {
            if (!$db->init($params) || !$db->begin()) throw new \RuntimeException('Database unavailable');
            try {
                $rows = $db->query('SELECT ? AS example_value, 1 + 1 AS calculation', [$message]);
                if ($rows === false) throw new \RuntimeException('Query failed');
                return ['sql' => 'SELECT ? AS example_value, 1 + 1 AS calculation', 'parameters' => [$message], 'rows' => $rows, 'transaction' => 'rollback'];
            } finally {
                $db->rollback();
            }
        });
    }

    private function redis(string $message): array
    {
        $config = $this->services()['redis'];
        return $this->withWarnings(function () use ($config, $message): array {
            $client = new Redis($config['hosts'], $config['options'] ?? [], $config['engine'] ?? '');
            $key = 'slightphp:sample:' . bin2hex(random_bytes(12));
            try {
                if (!$client->setex($key, 30, $message)) throw new \RuntimeException('Redis write failed');
                $value = $client->get($key);
                $ttl = $client->ttl($key);
                $deleted = $client->del($key);
                return ['key' => $key, 'value' => $value, 'ttl' => $ttl, 'deleted' => $deleted, 'after_delete' => $client->get($key)];
            } finally {
                $client->del($key);
            }
        });
    }

    private function http(Request $request, string $message): array
    {
        $method = $this->choice($request, 'method', ['GET', 'POST', '404']);
        $base = $this->services()['http']['base_url'] ?? 'https://httpbin.org';
        $url = rtrim($base, '/') . ($method === '404' ? '/status/404' : '/anything');
        try {
            $result = $method === 'POST'
                ? Http::post($url, ['message' => $message], ['sample' => 'demo'], true, 5, ['X-Sample' => 'SlightPHP'])
                : Http::get($url, ['message' => $message], ['sample' => 'demo'], true, 5, ['X-Sample' => 'SlightPHP']);
            $result['result'] = substr($result['result'], 0, 12000);
            return $result;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Guzzle 8 仅响应异常提供 getResponse()；兼容 Guzzle 7 的 nullable 返回值。
            $upstream = method_exists($e, 'getResponse') ? $e->getResponse() : null;
            if ($upstream !== null) return ['exception' => get_class($e), 'upstream_status' => $upstream->getStatusCode()];
            throw $e;
        }
    }

    private function captcha(Request $request): array
    {
        $code = $this->text($request->post, 'captcha');
        $prefix = Captcha::$session_prefix;
        try {
            Captcha::$session_prefix = self::CAPTCHA_KEY;
            return ['matched' => Captcha::check($code), 'one_time' => true];
        } finally {
            Captcha::$session_prefix = $prefix;
        }
    }

    private function requestData(Request $request, string $source): array
    {
        $values = $source === 'form' ? $request->post : $request->get;
        $message = $this->text($values, 'message', 'Hello SlightPHP');
        $file = $request->files['attachment'] ?? [];
        return ['method' => $request->getMethod(), 'route' => $request->route,
            $source => ['message' => $message],
            'content-type' => $request->header['content-type'] ?? null,
            'sample_cookie' => $request->cookie['slightphp_sample'] ?? null,
            'upload' => array_intersect_key($file, array_flip(['name', 'size', 'error']))];
    }

    private function cookie(Request $request, Response $response): array
    {
        $action = $this->choice($request, 'action', ['set', 'delete']);
        $ok = $action === 'set'
            ? $response->cookie('slightphp_sample', 'Hello SlightPHP', time() + 600)
            : $response->cookie('slightphp_sample', '', time() - 3600);
        return ['action' => $action, 'sent' => $ok, 'before' => $request->cookie['slightphp_sample'] ?? null,
            'defaults' => Response::getCookieDefaults()];
    }

    /** 将驱动警告转为可捕获异常，完成后恢复原处理器。 */
    private function withWarnings(callable $operation): array
    {
        set_error_handler(static function (int $code, string $message): void { throw new \ErrorException($message, 0, $code); });
        try { return $operation(); }
        finally { restore_error_handler(); }
    }
}
