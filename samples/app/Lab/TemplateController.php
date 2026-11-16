<?php
namespace Lab;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Tpl;
use SampleI18n;

class TemplateController
{
    /** 展示共享变量的 include，以及跨模块调度的 part。 */
    public function Entry(Request $request, Response $response): void
    {
        $tpl = new Tpl();
        $tpl->render('workspace/template.tpl', [
            'view' => 'template',
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', SampleI18n::t('task_workspace')],
                'route' => ['/lab/route/entry', '02', SampleI18n::t('route_lab')],
                'plugins' => ['/workspace/main/plugins', '03', SampleI18n::t('plugins_and_environment')],
                'template' => ['/lab/template/entry', '04', SampleI18n::t('templates_and_modules')],
                'language' => ['/lab/language/entry', '05', SampleI18n::t('language_example')],
            ],
            'scope' => 'PARENT',
            'include_child' => 'widgets/template-shared.tpl',
        ]);
    }
}
