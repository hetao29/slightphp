<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', sys_get_temp_dir());
    session_start();
}
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (is_string($uri) && $uri !== '') {
    $uri = preg_replace('~^/index\.php(?=/|$)~', '', $uri);
    SlightPHP::setPathInfo($uri === '' ? '/' : $uri);
} elseif (empty($_SERVER['PATH_INFO'])) {
    SlightPHP::setPathInfo('/');
}
\SlightPHP\Tpl::setTemplateDir(dirname(__DIR__) . '/app/templates');
\SlightPHP\Tpl::setCompileDir(sys_get_temp_dir());
\SlightPHP\Tpl::setDelimter('{%', '%}');
SlightPHP::setDefaultZone('site');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
$renderError = static function (): void {
    // 错误页使用固定入口，不受客户端 Request-Type 方法前缀影响。
    $requestTypes = SlightPHP::getAllowRequestTypes();
    SlightPHP::setAllowRequestTypes([]);
    try {
        SlightPHP::run('/site/main/error');
    } finally {
        SlightPHP::setAllowRequestTypes($requestTypes);
    }
};
try {
    // 保留入口已去除 index.php 的原始路径，再显式匹配，避免 PATH_INFO=/ 的转发影响短地址。
    $routePath = SlightPHP::$pathInfo !== '' ? SlightPHP::$pathInfo : ($_SERVER['PATH_INFO'] ?? '/');
    require dirname(__DIR__) . '/config/routes.php';
    \SlightPHP\Route::parse($routePath);
    $result = SlightPHP::run();
    if (is_string($result)) {
        echo $result;
    }
} catch (\SlightPHP\Exception $error) {
    http_response_code($error->getStatusCode());
    if ($error->getCode() === \SlightPHP\Exception::METHOD_NOT_ALLOWED) {
        if (!empty($error->getContext()['allowedMethods'])) {
            header('Allow: ' . implode(', ', $error->getContext()['allowedMethods']));
        }
        echo 'Method Not Allowed';
    } else {
        if ($error->getStatusCode() >= 500) error_log((string) $error);
        $renderError();
    }
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    $renderError();
}
