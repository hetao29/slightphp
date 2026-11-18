<?php
namespace Widgets;

use SlightPHP\Request;
use SlightPHP\Response;
use SlightPHP\Tpl;
use SampleI18n;

/** 可直接访问，也可通过模板 part 嵌入的模块片段。 */
class TemplateController
{
    /** 使用独立 Tpl 实例渲染卡片，并在卡片中 include 公共模板。 */
    public function Card(Request $request, Response $response): void
    {
        (new Tpl())->render('widgets/template-card.tpl', [
            'scope' => 'CHILD',
            'label' => $request->route[3] ?? 'demo',
        ]);
    }

    /** 独立自动模式片段：修饰器、各输出上下文、模板注释、默认分隔符和编译缓存。 */
    public function Context(Request $request, Response $response): void
    {
        $text = '<访客> & "hello" </script> ${notCode}';
        $tpl = new Tpl();
        $cacheTpl = new Tpl();
        $tpl->render('widgets/template-context.tpl', [
            'sample_text' => $text,
            'empty_name' => '',
            'translation_key' => 'tpl_demo_translated_value',
            'debug_value' => ['mode' => 'auto', 'items' => [1, 2], 'text' => $text],
            'asset' => '/assets/demo.js',
            'trusted_html' => '<strong data-template-case="raw">'.SampleI18n::e('tpl_demo_trusted').'</strong>',
            'query' => 'Tpl & CSS / 中文',
            'color' => '#3b82f6',
            'css_text' => 'CSS "text" <safe>',
            'payload' => ['text' => $text, 'items' => [1, 2], 'ok' => true],
            'js_prefix' => 'const viaRaw="',
            'default_output' => $this->defaultDelimiterExample(),
            'comments_output' => $this->capture('widgets/template-comments.tpl', ['comment_value' => 'VISIBLE']),
            'cache_first' => $this->capture('widgets/template-cache.tpl', ['cache_name' => 'first', 'cache_value' => '<FIRST>'], $cacheTpl),
            'cache_second' => $this->capture('widgets/template-cache.tpl', ['cache_name' => 'second', 'cache_value' => '<SECOND>'], $cacheTpl),
            'rejections' => $this->rejectedExamples(),
            'sources' => [
                'comments' => file_get_contents(__DIR__.'/../templates/widgets/template-comments.tpl'),
                'default' => '{$empty_name|default:"Guest"}',
                'escape' => '{$sample_text|escape}',
                'tr' => '{$translation_key|tr}',
                'tostring' => '{$debug_value|tostring}',
                'version' => '{$asset|version:"2.0"}',
                'raw' => '{$trusted_html|raw}',
                'chain' => '{$empty_name|default:"<Guest>"|escape}',
                'url' => '<a href="/lab/template/entry?q={$query|escape:"url"}">...</a>',
                'javascript' => "const data = {\$payload};\nconst single = '{\$sample_text}';\nconst double = \"{\$sample_text}\";\nconst template = `{\$sample_text}`;\nconst regexp = /{\$sample_text}/u;",
                'event' => '<button onclick="this.nextElementSibling.textContent={$sample_text}">...</button>',
                'css' => '<style>.preview::after { content:"{$css_text}"; color:{$color}; }</style>',
                'default_delimiters' => '<span>${$price}</span>' . "\n" . '<script>function f(){return {amount:{$number}};} const native=`sum:${1+2}`;</script>' . "\n" . '{literal}{$nativeName}{/literal}',
                'cache' => <<<'PHP'
$tpl = new \SlightPHP\Tpl();
$tpl->render('widgets/template-cache.tpl', ['cache_name' => 'first', 'cache_value' => '<FIRST>']);
$tpl->render('widgets/template-cache.tpl', ['cache_name' => 'second', 'cache_value' => '<SECOND>']);
PHP,
            ],
        ]);
    }

    /** 捕获独立示例的实际输出，供自动模式父片段以可信 HTML 嵌入。 */
    private function capture(string $file, array $data, ?Tpl $tpl = null): string
    {
        $tpl ??= new Tpl();
        ob_start();
        try { $tpl->render($file, $data); return ob_get_contents(); }
        finally { ob_end_clean(); }
    }

    /** 与其它 samples 一样使用默认 {}，展示原生花括号和 literal 的消歧。 */
    private function defaultDelimiterExample(): string
    {
        return $this->capture('widgets/template-default.tpl', ['price' => '12.50', 'number' => 7]);
    }

    /** 错误用例独立渲染并由控制器捕获，不把未完成的标签/脚本输出到示例页面。 */
    private function rejectedExamples(): array
    {
        $cases = [
            ['tpl_demo_reject_url', 'widgets/template-unsafe-url.tpl', ['unsafe_url' => 'javascript:alert(1)']],
            ['tpl_demo_reject_js', 'widgets/template-ambiguous-js.tpl', ['sample_text' => 'value']],
        ];
        $results = [];
        foreach ($cases as [$label, $file, $data]) {
            try { $this->capture($file, $data); $status = SampleI18n::t('tpl_demo_unexpected'); }
            catch (\InvalidArgumentException $error) { $status = get_class($error); }
            $results[] = ['label' => SampleI18n::t($label), 'status' => $status];
        }
        return $results;
    }

    /** 返回的 HTML 字符串由 part 插入父模板，动态内容在此转义。 */
    public function Returned(Request $request, Response $response): string
    {
        $label = htmlspecialchars($request->route[3] ?? 'demo', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<p data-example="part-return">'.SampleI18n::e('string_return').$label.'</p>';
    }

    /** 先直接输出，再返回字符串，part 按此顺序合并。 */
    public function Mixed(Request $request, Response $response): string
    {
        echo '<span data-example="part-mixed-output">'.SampleI18n::e('direct_output_first').'</span> / ';
        return '<span data-example="part-mixed-return">'.SampleI18n::e('string_return_next').'</span>';
    }

    /** 新模板实例内再次调用其它模块方法。 */
    public function Nested(Request $request, Response $response): void
    {
        (new Tpl())->render('widgets/template-nested.tpl', ['scope' => 'NESTED']);
    }

    /** 结束本次内部调用的 Response，父页面仍会继续渲染。 */
    public function Ended(Request $request, Response $response): bool
    {
        return $response->end('<p data-example="part-ended">'.SampleI18n::e('response_end_output').'</p>');
    }
}
