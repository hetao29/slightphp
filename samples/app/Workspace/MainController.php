<?php
namespace Workspace;

use SlightPHP\Tpl;
use SlightPHP\Config;
use SlightPHP\Route;
use SampleI18n;

class MainController
{
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Entry(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        $this->render('tasks', ['tasks' => $this->tasks()]);
    }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    #[Route(method: 'POST')]
    public function Save(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($this->token(), $_POST['csrf'])) {
            http_response_code(403);
            $this->render('error', ['message' => SampleI18n::t('this_form_has_expired_return_to_the_workspace_refresh')]);
            return;
        }
        $this->tasks();
        $tasks = $_SESSION['tasks'];
        $action = $_POST['action'] ?? '';
        $message = 'operation_completed';
        if ($action === 'add') {
            $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
            if ($title === '' || preg_match('/^.{1,80}$/us', $title) !== 1) {
                $message = 'enter_a_task_name_with_1_80_characters';
            } elseif (count($tasks) >= 50) {
                $message = 'you_can_save_up_to_50_tasks_delete_unneeded';
            } else {
                $tasks[] = ['id' => bin2hex(random_bytes(8)), 'title' => $title, 'done' => false];
                $message = 'task_added';
            }
        } elseif (in_array($action, ['toggle', 'delete'], true)) {
            $found = false;
            foreach ($tasks as $key => &$task) {
                if ($task['id'] === ($_POST['id'] ?? null)) {
                    $found = true;
                    if ($action === 'delete') unset($tasks[$key]);
                    else $task['done'] = !$task['done'];
                    break;
                }
            }
            unset($task);
            $message = $found ? ($action === 'delete' ? 'task_deleted' : 'task_status_updated') : 'task_not_found_refresh_and_try_again';
        } else {
            $message = 'unknown_action';
        }
        $_SESSION['tasks'] = array_values($tasks);
        $_SESSION['flash'] = $message;
        header('Location: /workspace/main/entry', true, 303);
    }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    #[Route(method: 'GET')]
    public function Api(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        $this->json(['data' => $this->tasks(), 'meta' => ['storage' => 'session', 'count' => count($this->tasks())]]);
    }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Plugins(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        Config::$CACHE = false;
        $this->render('plugins', ['config' => Config::getConfig(dirname(__DIR__, 2) . '/config/example.json')]);
    }

    private function render(string $view, array $data = []): void
    {
        $data['view'] = $view;
        $data['csrf'] = $this->token();
        $data['navigation'] = [
            'tasks' => ['/workspace/main/entry', '01', SampleI18n::t('task_workspace')],
            'route' => ['/lab/route/entry', '02', SampleI18n::t('route_lab')],
            'plugins' => ['/workspace/main/plugins', '03', SampleI18n::t('plugins_and_environment')],
            'template' => ['/lab/template/entry', '04', SampleI18n::t('templates_and_modules')],
            'language' => ['/lab/language/entry', '05', SampleI18n::t('language_example')],
        ];
        $data['done'] = count(array_filter($data['tasks'] ?? [], fn($task) => $task['done']));
        $data['flash'] = isset($_SESSION['flash']) ? SampleI18n::t($_SESSION['flash']) : '';
        unset($_SESSION['flash']);
        $data['json'] = json_encode($data['config'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tpl = new Tpl();
        $tpl->render('workspace-head.tpl', $data);
        $tpl->render('workspace/' . ($view === 'tasks' ? 'index' : $view) . '.tpl', $data);
        $tpl->render('workspace-footer.tpl', $data);
    }
    private function tasks(): array
    {
        if (!isset($_SESSION['tasks'])) {
            $_SESSION['tasks'] = [
                ['id' => 'welcome', 'title' => 'run_your_first_slightphp_example', 'done' => true],
                ['id' => 'route', 'title' => 'inspect_controller_mapping_in_the_route_lab', 'done' => false],
                ['id' => 'create', 'title' => 'add_a_task_of_your_own', 'done' => false],
            ];
        }
        $tasks = $_SESSION['tasks'];
        $seedTitles = [
            'welcome' => 'run_your_first_slightphp_example',
            'route' => 'inspect_controller_mapping_in_the_route_lab',
            'create' => 'add_a_task_of_your_own',
        ];
        foreach ($tasks as &$task) {
            if (isset($seedTitles[$task['id']])) {
                $task['title'] = SampleI18n::t($seedTitles[$task['id']]);
            }
        }
        unset($task);
        return $tasks;
    }
    private function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
    }
    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
