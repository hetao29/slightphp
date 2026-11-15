<?php
namespace Lab;

use SlightPHP\Route;

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
                'tasks' => ['/workspace/main/entry', '01', '任务工作台'],
                'route' => ['/lab/route/entry', '02', '路由实验室'],
                'plugins' => ['/workspace/main/plugins', '03', '插件与环境'],
                'template' => ['/lab/template/entry', '04', '模板与模块组合'],
            ],
            'json' => \SlightPHP\Json::encode($this->request->route, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'original_uri' => $request->server['request_uri'] ?? ($request->server['path_info'] ?? ''),
            'query_json' => \SlightPHP\Json::encode($request->get, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'routes_php' => file_get_contents(dirname(__DIR__, 2) . '/config/routes.php'),
            'routes_ini' => file_get_contents(dirname(__DIR__, 2) . '/config/routes.ini'),
        ];
        $tpl = new \SlightPHP\Tpl();
        $tpl->render('workspace-head.tpl', $data);
        $tpl->render('workspace/route.tpl', $data);
        $tpl->render('workspace-footer.tpl', $data);
    }
}
