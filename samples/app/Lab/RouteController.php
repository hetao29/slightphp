<?php
namespace Lab;

use SlightPHP\Route;
use SampleI18n;

class RouteController
{
    /** @var \SlightPHP\Request 构造函数与入口共用的请求。 */
    private \SlightPHP\Request $request;

    /**
     * 保存当前请求供入口使用。
     * @param \SlightPHP\Request $request 当前请求及完整路由。
     */
    public function __construct(\SlightPHP\Request $request) {
        $this->request = $request;
    }

    /**
     * 展示本次路由的完整分段。
     * @param \SlightPHP\Request $request 当前请求及路由。
     * @param \SlightPHP\Response $response 当前响应。
     */
    #[Route(methods: ['GET', 'POST'])]
    public function Entry(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        $data = [
            'view' => 'route',
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', SampleI18n::t('task_workspace')],
                'route' => ['/lab/route/entry', '02', SampleI18n::t('route_lab')],
                'plugins' => ['/workspace/main/plugins', '03', SampleI18n::t('plugins_and_environment')],
                'template' => ['/lab/template/entry', '04', SampleI18n::t('templates_and_modules')],
                'language' => ['/lab/language/entry', '05', SampleI18n::t('language_example')],
                'csrf' => ['/lab/csrf/entry', '06', SampleI18n::t('csrf_demo_title')],
            ],
            'json' => json_encode($this->request->route, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'original_uri' => $request->server['request_uri'] ?? ($request->server['path_info'] ?? ''),
            'query_json' => json_encode($request->get, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'routes_php' => file_get_contents(dirname(__DIR__, 2) . '/config/routes.php'),
            'routes_json' => file_get_contents(dirname(__DIR__, 2) . '/config/routes.json'),
        ];
        $tpl = new \SlightPHP\Tpl();
        $tpl->render('workspace-head.tpl', $data);
        $tpl->render('workspace/route.tpl', $data);
        $tpl->render('workspace-footer.tpl', $data);
    }
}
