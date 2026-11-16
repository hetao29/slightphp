<?php
namespace Site;

use SlightPHP\Tpl;
use SampleI18n;

class MainController
{
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Entry(\SlightPHP\Request $request, \SlightPHP\Response $response) { $this->render('home'); }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Features(\SlightPHP\Request $request, \SlightPHP\Response $response) { return $response->redirect('/?lang=' . rawurlencode(SampleI18n::locale()) . '#features'); }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Plugins(\SlightPHP\Request $request, \SlightPHP\Response $response) { $this->render('plugins'); }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Guide(\SlightPHP\Request $request, \SlightPHP\Response $response) { $this->render('guide'); }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Examples(\SlightPHP\Request $request, \SlightPHP\Response $response) { $this->render('examples'); }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function About(\SlightPHP\Request $request, \SlightPHP\Response $response) { $this->render('about'); }
    /** Show the same release notes as the repository, without duplicating them. */
    public function Changelog(\SlightPHP\Request $request, \SlightPHP\Response $response): void
    {
        require_once dirname(__DIR__) . '/Documents.php';
        $this->render('changelog', ['changelog' => \SampleDocuments::changelog()]);
    }
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Error(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        $this->render('error', ['message' => http_response_code() === 404
            ? SampleI18n::t('this_page_could_not_be_found_return_home_or')
            : SampleI18n::t('the_request_could_not_be_completed_please_try_again')]);
    }

    /** Browse every Markdown guide and the generated API reference in docs/. */
    public function Docs(\SlightPHP\Request $request, \SlightPHP\Response $response): void
    {
        require_once dirname(__DIR__) . '/Documents.php';
        $name = $request->get['doc'] ?? 'README.md';
        $document = is_string($name) ? \SampleDocuments::page($name) : null;
        if ($document === null) {
            http_response_code(404);
            $this->Error($request, $response);
            return;
        }
        $this->render('docs', ['document' => $document, 'documents' => \SampleDocuments::files()]);
    }

    private function render(string $view, array $data = []): void
    {
        $titles = ['home' => SampleI18n::t('lightweight_core_build_freely'), 'plugins' => SampleI18n::t('plugin_ecosystem'), 'guide' => SampleI18n::t('guide'), 'examples' => SampleI18n::t('interactive_examples'), 'about' => SampleI18n::t('about_the_framework'), 'error' => SampleI18n::t('page_notice')];
        $data['title'] = $view === 'docs' ? $data['document']['title'] : ($view === 'changelog' ? 'ChangeLog' : $titles[$view]);
        $data['view'] = $view;
        $data['nav'] = ['home' => [SampleI18n::t('home'), '/site/main/entry'], 'guide' => [SampleI18n::t('guide'), '/site/main/guide'], 'plugins' => [SampleI18n::t('plugins'), '/site/main/plugins'], 'examples' => [SampleI18n::t('examples'), '/site/main/examples'], 'docs' => [SampleI18n::t('documentation'), '/site/main/docs'], 'changelog' => ['ChangeLog', '/site/main/changelog']];
        if ($view === 'home') $data['features'] = require dirname(__DIR__) . '/data/site/features.php';
        if ($view === 'plugins') $data['plugins'] = require dirname(__DIR__) . '/data/site/plugins.php';
        if (in_array($view, ['home', 'plugins', 'guide'], true)) $data['code'] = require dirname(__DIR__) . '/data/site/code.php';
        $tpl = new Tpl();
        $tpl->render('head.tpl', $data);
        $tpl->render('site/' . $view . '.tpl', $data);
        $tpl->render('footer.tpl', $data);
    }
}
