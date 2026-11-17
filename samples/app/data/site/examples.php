<?php
// Walk through the repository samples; database snippets describe an optional extension.
return [
    [
        'id' => 'sample-layout', 'number' => '01', 'title' => 'sample_layout_title',
        'intro' => 'sample_layout_intro',
        'blocks' => [
            ['label' => 'samples/', 'code' => <<<'TEXT'
samples/
├── www/
│   ├── index.php
│   └── assets/
├── config/
│   ├── example.json
│   ├── routes.php
│   └── routes.json
└── app/
    ├── Site/MainController.php
    ├── Workspace/MainController.php
    ├── Lab/
    ├── Widgets/
    ├── I18n.php
    ├── languages/{zh-cn,zh-tw,en}/
    ├── data/site/
    └── templates/
TEXT],
        ],
        'notes' => ['sample_layout_public', 'sample_layout_modules'],
        'sources' => ['samples/www/index.php', 'samples/app/Site/MainController.php'],
        'links' => [['/site/main/docs?doc=getting-started.md', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-entry', 'number' => '02', 'title' => 'sample_entry_title',
        'intro' => 'sample_entry_intro',
        'blocks' => [
            ['label' => 'samples/www/index.php · Bootstrap', 'code' => <<<'PHP'
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/app/I18n.php';
SampleI18n::boot();
SlightPHP::setAppDir(dirname(__DIR__) . '/app');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = preg_replace('~^/index\.php(?=/|$)~', '', $uri);
SlightPHP::setPathInfo($uri === '' ? '/' : $uri);
SlightPHP::setDefaultZone('site');
SlightPHP::setDefaultPage('main');
SlightPHP::setDefaultEntry('entry');
PHP],
        ],
        'notes' => ['sample_entry_dispatch', 'sample_entry_errors'],
        'sources' => ['samples/www/index.php', 'samples/app/Site/MainController.php'],
        'links' => [['/site/main/docs?doc=getting-started.md', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-routing', 'number' => '03', 'title' => 'sample_routing_title',
        'intro' => 'sample_routing_intro',
        'blocks' => [
            ['label' => 'samples/config/routes.php', 'code' => file_get_contents(dirname(__DIR__, 3) . '/config/routes.php')],
            ['label' => 'samples/www/index.php · Route', 'code' => <<<'PHP'
$routePath = SlightPHP::$pathInfo !== ''
    ? SlightPHP::$pathInfo
    : ($_SERVER['PATH_INFO'] ?? '/');
require dirname(__DIR__) . '/config/routes.php';
\SlightPHP\Route::parse($routePath);
$result = SlightPHP::run();
if (is_string($result)) echo $result;
PHP],
        ],
        'notes' => ['sample_routing_mapping', 'sample_routing_methods'],
        'sources' => ['samples/www/index.php', 'samples/config/routes.php', 'samples/config/routes.json', 'samples/app/Lab/RouteController.php'],
        'links' => [['/r/hello?source=examples', 'run_the_route_lab'], ['/site/main/docs?doc=routes.md', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-route-methods', 'number' => '04', 'title' => 'sample_route_methods_title',
        'intro' => 'sample_route_methods_intro',
        'blocks' => [
            ['label' => 'Lab\\TypeController · Route', 'code' => <<<'PHP'
use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;

#[Route(method: 'GET')]
public function Entry(Request $request, Response $response): void
{
    $response->header('Content-Type', 'text/plain; charset=utf-8');
    $response->end("Lab\\TypeController::Entry\n");
}

#[Route(methods: ['GET', 'POST'])]
public function Preview(Request $request, Response $response): void
{
    $response->header('Content-Type', 'text/plain; charset=utf-8');
    $response->end("Lab\\TypeController::Preview\n" . $request->getMethod() . "\n");
}
PHP],
        ],
        'notes' => ['sample_route_methods_syntax', 'sample_route_methods_rejection', 'sample_route_methods_dispatch'],
        'sources' => ['samples/app/Lab/TypeController.php', 'samples/app/Lab/RouteController.php', 'samples/app/Workspace/MainController.php'],
        'links' => [['/lab/type/preview', 'sample_route_methods_open'], ['/site/main/docs?doc=framework.md#route-方法注解', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-request-type', 'number' => '05', 'title' => 'sample_request_type_title',
        'intro' => 'sample_request_type_intro',
        'blocks' => [
            ['label' => 'samples/app/Lab/TypeController.php', 'code' => file_get_contents(dirname(__DIR__, 2) . '/Lab/TypeController.php')],
            ['label' => 'fetch · Request-Type', 'code' => <<<'JS'
const response = await fetch('/lab/type/entry', {
    method: 'GET',
    headers: { 'Request-Type': 'ajax' }
});
const text = await response.text();
// HTTP 200: Lab\TypeController::ajaxEntry
JS],
        ],
        'notes' => ['sample_request_type_dispatch', 'sample_request_type_guard'],
        'sources' => ['samples/app/Lab/TypeController.php', 'samples/www/assets/site.js', 'SlightPHP.php'],
        'links' => [['/lab/type/entry', 'sample_request_type_open'], ['/site/main/docs?doc=framework.md#方法前缀与-http-方法', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-language', 'number' => '06', 'title' => 'sample_language_title',
        'intro' => 'sample_language_intro',
        'blocks' => [
            ['label' => 'samples/app/languages/zh-cn/demo.json', 'code' => file_get_contents(dirname(__DIR__, 2) . '/languages/zh-cn/demo.json')],
            ['label' => 'Language · demo', 'code' => <<<'PHP'
use SlightPHP\Language;

Language::setLanguageDir(SlightPHP::getAppDir() . '/languages');
Language::setLocale('zh-cn');

$message = strtr(Language::tr('greeting', 'demo'), [
    '{name}' => 'SlightPHP',
]);
echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
PHP],
        ],
        'notes' => ['sample_language_selection', 'sample_language_catalogs'],
        'sources' => ['samples/app/I18n.php', 'samples/app/Lab/LanguageController.php', 'samples/app/languages/en/demo.json'],
        'links' => [['/lab/language/entry?lang=zh-cn&name=SlightPHP', 'run_language_example'], ['/site/main/docs?doc=plugins.md#language', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-config', 'number' => '07', 'title' => 'sample_config_title',
        'intro' => 'sample_config_intro',
        'blocks' => [
            ['label' => 'samples/config/example.json', 'code' => file_get_contents(dirname(__DIR__, 3) . '/config/example.json')],
            ['label' => 'SlightPHP\\Config', 'code' => <<<'PHP'
use SlightPHP\Config;

// In a controller under samples/app/Workspace/.
Config::$CACHE = false;
$file = dirname(__DIR__, 2) . '/config/example.json';
$config = Config::getConfig($file);
$name = $config->application->name;
$limit = $config->application->limit;

// Read just the application section.
$application = Config::getConfig($file, 'application');
PHP],
        ],
        'notes' => ['sample_config_values', 'sample_templates_config'],
        'sources' => ['samples/config/example.json', 'samples/app/Workspace/MainController.php'],
        'links' => [['/workspace/main/plugins', 'view_plugin_examples'], ['/site/main/docs?doc=plugins.md#config', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-templates', 'number' => '08', 'title' => 'sample_templates_title',
        'intro' => 'sample_templates_intro',
        'blocks' => [
            ['label' => 'samples/www/index.php · Tpl', 'code' => <<<'PHP'
\SlightPHP\Tpl::setTemplateDir(dirname(__DIR__) . '/app/templates');
\SlightPHP\Tpl::setCompileDir(sys_get_temp_dir());
\SlightPHP\Tpl::setDelimter('{%', '%}');
PHP],
            ['label' => 'Workspace\\MainController::render', 'code' => <<<'PHP'
$tpl = new \SlightPHP\Tpl();
$tpl->render('workspace-head.tpl', $data);
$tpl->render('workspace/' . ($view === 'tasks' ? 'index' : $view) . '.tpl', $data);
$tpl->render('workspace-footer.tpl', $data);
PHP],
            ['label' => 'samples/app/templates/workspace/template.tpl', 'code' => <<<'HTML'
{%include $include_child%}
{%part "/widgets/template/card/demo"%}
<p>{%$scope|escape%}</p>
HTML],
        ],
        'notes' => ['sample_templates_composition', 'sample_templates_output'],
        'sources' => ['samples/app/Workspace/MainController.php', 'samples/app/Lab/TemplateController.php', 'samples/app/templates/workspace/template.tpl'],
        'links' => [['/lab/template/entry', 'run_the_template_example'], ['/site/main/docs?doc=templates.md', 'sample_read_guide']],
    ],
    [
        'id' => 'sample-forms', 'number' => '09', 'title' => 'sample_forms_title',
        'intro' => 'sample_forms_intro',
        'blocks' => [
            ['label' => 'samples/app/templates/workspace/index.tpl', 'code' => <<<'HTML'
<form method="post" action="/workspace/main/save">
    <input type="hidden" name="csrf" value="{%$csrf%}">
    <input type="hidden" name="action" value="add">
    <input name="title" maxlength="80" required>
    <button type="submit">Add task</button>
</form>
HTML],
            ['label' => 'Workspace\\MainController::Save', 'code' => <<<'PHP'
#[\SlightPHP\Route(method: 'POST')]
public function Save(\SlightPHP\Request $request, \SlightPHP\Response $response)
{
    if (!is_string($_POST['csrf'] ?? null)
        || !hash_equals($this->token(), $_POST['csrf'])) {
        http_response_code(403);
        $this->render('error', [
            'message' => SampleI18n::t('this_form_has_expired_return_to_the_workspace_refresh'),
        ]);
        return;
    }
    // Validate action and title, then update the tasks.
    // See the complete Save() method in the source file.
    header('Location: /workspace/main/entry', true, 303);
}
PHP],
        ],
        'notes' => ['sample_forms_validation', 'sample_forms_redirect'],
        'sources' => ['samples/app/Workspace/MainController.php', 'samples/app/templates/workspace/index.tpl'],
        'links' => [['/tasks', 'open_the_workspace']],
    ],
    [
        'id' => 'sample-database', 'number' => '10', 'title' => 'sample_database_title',
        'intro' => 'sample_database_intro',
        'blocks' => [
            ['label' => 'MySQL · sample_task', 'code' => <<<'SQL'
CREATE TABLE sample_task (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(80) NOT NULL,
    done TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL],
            ['label' => 'samples/config/db.json', 'code' => <<<'JSON'
{
    "tasks": {
        "main": {
            "engine": "pdo_mysql",
            "host": "127.0.0.1",
            "port": 3306,
            "user": "sample_app",
            "password": "CHANGE_ME",
            "database": "slightphp_sample",
            "charset": "utf8mb4",
            "persistent": false
        }
    }
}
JSON],
            ['label' => 'Db::setConfigFile() + useConfig()', 'code' => <<<'PHP'
use SlightPHP\Db;

// In a controller under samples/app/Workspace/.
Db::setConfigFile(dirname(__DIR__, 2) . '/config/db.json');
$db = new Db();
if ($db->useConfig('tasks') !== true) {
    throw new \RuntimeException('Database connection failed');
}
PHP],
            ['label' => 'Db · CRUD', 'code' => <<<'PHP'
$id = $db->insert('sample_task', ['title' => 'Learn SlightPHP', 'done' => 0]);
if ($id === false) throw new \RuntimeException('Insert failed');

$tasks = $db->select('sample_task', ['done' => 0]);
if ($tasks === false) throw new \RuntimeException('Select failed');
$items = $tasks->items;

$updated = $db->update('sample_task', ['id' => $id], ['done' => 1]);
if ($updated === false) throw new \RuntimeException('Update failed');

$deleted = $db->delete('sample_task', ['id' => $id]);
if ($deleted === false) throw new \RuntimeException('Delete failed');
PHP],
        ],
        'notes' => ['sample_database_setup', 'sample_database_binding', 'sample_database_results', 'sample_database_integration'],
        'sources' => ['plugins/Db.php', 'plugins/DbPDO.php'],
        'links' => [['/site/main/docs?doc=plugins.md#数据库失败处理', 'sample_read_guide'], ['/site/main/docs?doc=index.html#SlightPHP%5CDb', 'sample_database_api']],
    ],
];
