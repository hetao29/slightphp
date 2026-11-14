{%include "workspace-head.tpl"%}
<div class="eyebrow">TPL / INCLUDE + PART</div>
<h1>模板与模块组合</h1>
<p class="lead">include 复用模板和当前变量；part 调用另一个控制器方法。这里的结果都由实际调用生成。</p>
<section class="panel">
    <h2>1. include：共享父模板变量</h2>
    <p>父模板的 scope 为 <code>PARENT</code>。动态文件名的 include 跨目录加载 Widgets 的公共模板，继续使用同一个 Tpl 实例，子模板还会嵌套 include。</p>
    <pre><code>&#123;%include $include_child%&#125;</code></pre>
    {%include $include_child%}
</section>
<section class="panel">
    <h2>2. part：调用 Widgets 模块</h2>
    <p>Card 方法创建自己的 Tpl 实例，将 scope 设置为 <code>CHILD</code>，并从路径参数读取 <code>demo</code>。卡片内部的 include 共享 CHILD。</p>
    <pre><code>&#123;%part "/widgets/template/card/demo"%&#125;</code></pre>
    {%part "/widgets/template/card/demo"%}
    <p data-example="parent-after-card">调用后父模板：<strong>{%$scope|escape%}</strong></p>
    <p><a href="/widgets/template/card/direct">单独打开模块卡片 →</a></p>
</section>
<section class="panel">
    <h2>3. 字符串返回与混合输出</h2>
    <p>part 将模块直接输出的内容与字符串返回值按顺序插入当前位置，各输出一次。</p>
    <pre><code>&#123;%part "/widgets/template/returned/demo"%&#125;
&#123;%part "/widgets/template/mixed"%&#125;</code></pre>
    {%part "/widgets/template/returned/demo"%}
    <p>{%part "/widgets/template/mixed"%}</p>
</section>
<section class="panel">
    <h2>4. 嵌套 part 与响应结束</h2>
    <p>Nested 模块再次调用 Returned 方法；内部 Response::end() 输出片段后，父页面继续渲染。</p>
    <pre><code>&#123;%part "/widgets/template/nested"%&#125;
&#123;%part "/widgets/template/ended"%&#125;</code></pre>
    {%part "/widgets/template/nested"%}
    {%part "/widgets/template/ended"%}
    <p data-example="parent-final">所有模块完成，父模板仍为：<strong>{%$scope|escape%}</strong></p>
</section>
<section class="panel">
    <h2>继续阅读</h2>
    <p>完整调用规则、文件位置和异常处理见仓库 <code>docs/templates.md</code>。模板与模块返回的动态 HTML 都应在输出处转义。</p>
</section>
{%include "workspace-footer.tpl"%}
