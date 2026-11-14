<?php
namespace Lab;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Tpl;

class TemplateController
{
    /** 展示共享变量的 include，以及跨模块调度的 part。 */
    public function Entry(Request $request, Response $response): void
    {
        $tpl = new Tpl();
        $tpl->render('workspace/template.tpl', [
            'view' => 'template',
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', '任务工作台'],
                'route' => ['/lab/route/entry', '02', '路由实验室'],
                'plugins' => ['/workspace/main/plugins', '03', '插件与环境'],
                'template' => ['/lab/template/entry', '04', '模板与模块组合'],
            ],
            'scope' => 'PARENT',
            'include_child' => 'widgets/template-shared.tpl',
        ]);
    }
}
