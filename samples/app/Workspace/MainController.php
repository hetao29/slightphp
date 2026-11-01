<?php
namespace Workspace;

use STpl;
use SConfig;
use SJson;

class MainController
{
    public function Entry($inPath)
    {
        $this->render('tasks', ['tasks' => $this->tasks()]);
    }
    public function Save($inPath)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            $this->render('error', ['message' => '此操作需要通过表单提交。']);
            return;
        }
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($this->token(), $_POST['csrf'])) {
            http_response_code(403);
            $this->render('error', ['message' => '表单已失效，请返回工作台刷新后重试。']);
            return;
        }
        $tasks = $this->tasks();
        $action = $_POST['action'] ?? '';
        $message = '操作完成';
        if ($action === 'add') {
            $title = is_string($_POST['title'] ?? null) ? trim($_POST['title']) : '';
            if ($title === '' || preg_match('/^.{1,80}$/us', $title) !== 1) {
                $message = '请输入 1–80 个字符的任务名称。';
            } elseif (count($tasks) >= 50) {
                $message = '最多保存 50 项任务，请先删除不需要的任务。';
            } else {
                $tasks[] = ['id' => bin2hex(random_bytes(8)), 'title' => $title, 'done' => false];
                $message = '任务已添加';
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
            $message = $found ? ($action === 'delete' ? '任务已删除' : '任务状态已更新') : '任务不存在，请刷新后重试。';
        } else {
            $message = '无法识别此操作。';
        }
        $_SESSION['tasks'] = array_values($tasks);
        $_SESSION['flash'] = $message;
        header('Location: /workspace/main/entry', true, 303);
    }
    public function Api($inPath)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            $this->json(['error' => '仅支持 GET']);
            return;
        }
        $this->json(['data' => $this->tasks(), 'meta' => ['storage' => 'session', 'count' => count($this->tasks())]]);
    }
    public function Plugins($inPath)
    {
        SConfig::$CACHE = false;
        $this->render('plugins', ['config' => SConfig::getConfig(dirname(__DIR__, 2) . '/config/example.conf')]);
    }

    private function render(string $view, array $data = []): void
    {
        $data['view'] = $view;
        $data['csrf'] = $this->token();
        $data['navigation'] = [
            'tasks' => ['/workspace/main/entry', '01', '任务工作台'],
            'route' => ['/lab/route/entry', '02', '路由实验室'],
            'plugins' => ['/workspace/main/plugins', '03', '插件与环境'],
        ];
        $data['done'] = count(array_filter($data['tasks'] ?? [], fn($task) => $task['done']));
        $data['flash'] = $_SESSION['flash'] ?? '';
        unset($_SESSION['flash']);
        $data['json'] = SJson::encode($data['config'] ?? $data['inPath'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $tpl = new STpl();
        $tpl->render('workspace-head.tpl', $data);
        $tpl->render('workspace/' . ($view === 'tasks' ? 'index' : $view) . '.tpl', $data);
        $tpl->render('workspace-footer.tpl', $data);
    }
    private function tasks(): array
    {
        if (!isset($_SESSION['tasks'])) {
            $_SESSION['tasks'] = [
                ['id' => 'welcome', 'title' => '运行第一个 SlightPHP 示例', 'done' => true],
                ['id' => 'route', 'title' => '在路由实验室观察控制器映射', 'done' => false],
                ['id' => 'create', 'title' => '添加一项自己的任务', 'done' => false],
            ];
        }
        return $_SESSION['tasks'];
    }
    private function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(24));
    }
    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo SJson::encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
