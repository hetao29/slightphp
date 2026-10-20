<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
SlightPHP::setModes(['NAMESPACE']);
STpl::setTemplateDir(dirname(__DIR__) . '/app/templates');
STpl::setCompileDir(dirname(__DIR__) . '/app/templates_c');
STpl::setDelimter('{%', '%}');
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
