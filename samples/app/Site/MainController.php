<?php
namespace Site;

use SlightPHP\Tpl;

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
    public function Features(\SlightPHP\Request $request, \SlightPHP\Response $response) { $this->render('features'); }
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
    /**
     * @param \SlightPHP\Request $request 当前请求。
     * @param \SlightPHP\Response $response 当前响应。
     */
    public function Error(\SlightPHP\Request $request, \SlightPHP\Response $response)
    {
        $this->render('error', ['message' => http_response_code() === 404
            ? '这个页面暂时找不到了，您可以返回首页或查看使用指南。'
            : '暂时无法完成请求，请稍后重试。']);
    }

    private function render(string $view, array $data = []): void
    {
        $titles = ['home' => '轻量核心，自由构建', 'features' => '功能特性', 'plugins' => '插件生态', 'guide' => '使用指南', 'examples' => '交互示例', 'about' => '关于框架', 'error' => '页面提示'];
        $data['title'] = $titles[$view];
        $data['view'] = $view;
        $data['nav'] = ['home' => ['首页', '/site/main/entry'], 'features' => ['功能特性', '/site/main/features'], 'plugins' => ['插件', '/site/main/plugins'], 'guide' => ['使用指南', '/site/main/guide'], 'examples' => ['示例', '/site/main/examples']];
        if (in_array($view, ['home', 'features'], true)) $data['features'] = require dirname(__DIR__) . '/data/site/features.php';
        if ($view === 'plugins') $data['plugins'] = require dirname(__DIR__) . '/data/site/plugins.php';
        if (in_array($view, ['home', 'plugins', 'guide'], true)) $data['code'] = require dirname(__DIR__) . '/data/site/code.php';
        $tpl = new Tpl();
        $tpl->render('head.tpl', $data);
        $tpl->render('site/' . $view . '.tpl', $data);
        $tpl->render('footer.tpl', $data);
    }
}
