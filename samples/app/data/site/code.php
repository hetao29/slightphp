<?php
// Module content, loaded only for the pages that use it.
return [
            'route_php' => file_get_contents(dirname(__DIR__, 3) . '/config/routes.php'),
            'route_json' => file_get_contents(dirname(__DIR__, 3) . '/config/routes.json'),
            'route_methods' => <<<'PHP'
<?php
namespace Demo;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

class MainController
{
    #[Route(method: 'GET')]
    public function Info(Request $request, Response $response)
    {
        return $response->end('仅允许 GET');
    }

    #[Route(methods: ['GET', 'POST'])]
    public function Preview(Request $request, Response $response)
    {
        return $response->end('允许 GET 或 POST');
    }
}
PHP,
            'install' => 'composer require hetao29/slightphp:^6.6.0',
            'entry' => <<<'PHP'
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
SlightPHP::setAppDir(dirname(__DIR__) . '/app');
SlightPHP::setDefaultZone('home');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
SlightPHP::setPathInfo(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
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
PHP,

            'namespace' => "<?php\n// app/Home/MainController.php\nnamespace Home;\n\nclass MainController\n{\n    /**\n     * @param \\SlightPHP\\Request \$request 当前请求。\n     * @param \\SlightPHP\\Response \$response 当前响应。\n     */\n    public function Entry(\\SlightPHP\\Request \$request, \\SlightPHP\\Response \$response)\n    {\n        return 'Hello, SlightPHP!';\n    }\n}",
            'preview' => "GET /home/main/entry\n\napp/Home/MainController.php\n  → Home\\MainController::Entry(\\SlightPHP\\Request \$request, \\SlightPHP\\Response \$response)\n  → Hello, SlightPHP!",
            'config' => "SlightPHP::setControllerSuffix('Controller');\nSlightPHP::setSplitFlag('/');",
            'json' => "header('Content-Type: application/json; charset=utf-8');\necho json_encode(\n    ['message' => '你好，SlightPHP'],\n    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR\n);",
            'tpl' => "\\SlightPHP\\Tpl::setTemplateDir(__DIR__ . '/templates');\n\\SlightPHP\\Tpl::setCompileDir(__DIR__ . '/var/templates_c');\n\$tpl = new \\SlightPHP\\Tpl();\n\$tpl->render('index.tpl', ['title' => 'SlightPHP']);",
        ];
