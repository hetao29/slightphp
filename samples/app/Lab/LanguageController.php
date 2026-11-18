<?php
namespace Lab;

use SampleI18n;
use SlightPHP\Language;
use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Route;
use SlightPHP\Tpl;

class LanguageController
{
    /** Run translations from a dedicated JSON zone using the locale chosen by the entry. */
    #[Route(method: 'GET')]
    public function Entry(Request $request, Response $response): void
    {
        $name = $request->get['name'] ?? 'SlightPHP';
        $invalid = !is_string($name) || preg_match('/^.{1,80}$/us', trim($name)) !== 1;
        $name = $invalid ? 'SlightPHP' : trim($name);
        if ($invalid) $response->status(400);
        $locale = SampleI18n::locale();
        $code = <<<'CODE'
use SlightPHP\Language;

Language::setLanguageDir(SlightPHP::getAppDir() . '/languages');
Language::setLocale(__LOCALE__);

$message = strtr(Language::tr('greeting', 'demo'), ['{name}' => __NAME__]);
echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
echo Language::tr('action', 'demo');
echo Language::tr('missing_demo_key', 'demo');
CODE;
        $data = [
            'view' => 'language',
            'navigation' => [
                'tasks' => ['/workspace/main/entry', '01', SampleI18n::t('task_workspace')],
                'route' => ['/lab/route/entry', '02', SampleI18n::t('route_lab')],
                'plugins' => ['/workspace/main/plugins', '03', SampleI18n::t('plugins_and_environment')],
                'template' => ['/lab/template/entry', '04', SampleI18n::t('templates_and_modules')],
                'language' => ['/lab/language/entry', '05', SampleI18n::t('language_example')],
                'csrf' => ['/lab/csrf/entry', '06', SampleI18n::t('csrf_demo_title')],
            ],
            'name' => $name,
            'invalid' => $invalid,
            'selected_locale' => $locale,
            'greeting' => strtr(Language::tr('greeting', 'demo'), ['{name}' => $name]),
            'action' => Language::tr('action', 'demo'),
            'missing' => Language::tr('missing_demo_key', 'demo'),
            'catalog_path' => 'app/languages/' . $locale . '/demo.json',
            'catalog' => file_get_contents(dirname(__DIR__) . '/languages/' . $locale . '/demo.json'),
            'code' => strtr($code, ['__LOCALE__' => var_export($locale, true), '__NAME__' => var_export($name, true)]),
        ];
        (new Tpl())->render('workspace/language.tpl', $data);
    }
}
