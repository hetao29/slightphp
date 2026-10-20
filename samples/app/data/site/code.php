<?php
// Module content, loaded only for the pages that use it.
return [
            'install' => 'composer require hetao29/slightphp',
            'entry' => "<?php\nrequire dirname(__DIR__) . '/vendor/autoload.php';\n\nSlightPHP::setAppDir(dirname(__DIR__) . '/app');\nSlightPHP::setModes(['NAMESPACE']);\nSlightPHP::setDefaultZone('home');\nSlightPHP::setDefaultPage('main');\nSlightPHP::setDefaultEntry('entry');\n\n// Web server forwards the original URI to this entry.\nSlightPHP::setPathInfo(parse_url(\$_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');\n\$result = SlightPHP::run();\nif (\$result === false) {\n    http_response_code(404);\n    echo '页面不存在';\n} elseif (is_string(\$result)) {\n    echo \$result;\n}",
            'page' => "<?php\n// app/home/main.page.php\nclass home_main\n{\n    public function pageEntry(\$inPath)\n    {\n        return 'Hello, SlightPHP!';\n    }\n}",
            'namespace' => "<?php\n// app/Home/MainController.php\nnamespace Home;\n\nclass MainController\n{\n    public function Entry(\$inPath)\n    {\n        return 'Hello, SlightPHP!';\n    }\n}",
            'preview' => "GET /home/main/entry\n\napp/Home/MainController.php\n  → Home\\MainController::Entry(\$inPath)\n  → Hello, SlightPHP!",
            'serve' => 'php samples/tests/routing.php',
            'config' => "SlightPHP::setModes(['NAMESPACE']);\nSlightPHP::setControllerSuffix('Controller');\nSlightPHP::setSplitFlag('/');",
            'json' => "header('Content-Type: application/json; charset=utf-8');\necho SJson::encode(\n    ['message' => '你好，SlightPHP'],\n    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR\n);",
            'tpl' => "STpl::setTemplateDir(__DIR__ . '/templates');\nSTpl::setCompileDir(__DIR__ . '/var/templates_c');\n\$tpl = new STpl();\n\$tpl->render('index.tpl', ['title' => 'SlightPHP']);",
        ];
