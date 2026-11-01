<?php
namespace Lab;

class RouteController
{
    public function Entry($inPath)
    {
        $data = [
            'view' => 'route',
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', '任务工作台'],
                'route' => ['/lab/route/entry', '02', '路由实验室'],
                'plugins' => ['/workspace/main/plugins', '03', '插件与环境'],
            ],
            'json' => \SJson::encode($inPath, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
        $tpl = new \STpl();
        $tpl->render('workspace-head.tpl', $data);
        $tpl->render('workspace/route.tpl', $data);
        $tpl->render('workspace-footer.tpl', $data);
    }
}
