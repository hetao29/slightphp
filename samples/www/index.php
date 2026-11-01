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
try {
    $result = SlightPHP::run();
    if ($result === false) {
        http_response_code(404);
        SlightPHP::run('/site/main/error');
    } elseif (is_string($result)) {
        echo $result;
    }
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    SlightPHP::run('/site/main/error');
}
