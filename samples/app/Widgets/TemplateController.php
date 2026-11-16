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
