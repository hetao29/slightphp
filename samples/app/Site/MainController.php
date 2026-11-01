<?php
namespace Site;

use STpl;

class MainController
{
    public function Entry($inPath) { $this->render('home'); }
    public function Features($inPath) { $this->render('features'); }
    public function Plugins($inPath) { $this->render('plugins'); }
    public function Guide($inPath) { $this->render('guide'); }
    public function Examples($inPath) { $this->render('examples'); }
    public function About($inPath) { $this->render('about'); }
    public function Error($inPath)
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
        $tpl = new STpl();
        $tpl->render('head.tpl', $data);
        $tpl->render('site/' . $view . '.tpl', $data);
        $tpl->render('footer.tpl', $data);
    }
}
