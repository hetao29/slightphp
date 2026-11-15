<section class="page-intro container"><div class="eyebrow">PLUGIN TOOLBOX / 插件生态</div><h1>按需选择，<br>让应用具备合适的能力。</h1><p>通过 Composer 加载所需插件，并准备对应的扩展、配置和服务。Route 提供自定义 URL 映射和 Controller 请求方法注解，下方可直接查看代码与运行示例。</p></section><section class="container plugin-section"><div class="plugin-toolbar"><div class="case-filters" role="group" aria-label="插件分类"><button type="button" data-plugin-filter="all" aria-pressed="true">全部插件</button><button type="button" data-plugin-filter="base" aria-pressed="false">基础能力</button><button type="button" data-plugin-filter="data" aria-pressed="false">数据与缓存</button><button type="button" data-plugin-filter="extra" aria-pressed="false">扩展工具</button></div><label class="plugin-search">搜索插件<input type="search" id="plugin-search" placeholder="例如 Tpl、JSON、数据库"></label></div><p id="plugin-status" role="status" class="result-count">插件清单 · 按需加载</p><div class="service-grid">{%foreach ($plugins as $plugin)%}<article class="plugin-card" data-plugin-type="{%htmlspecialchars($plugin['type'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}"><div class="plugin-card-heading"><h2>{%htmlspecialchars($plugin['name'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</h2><span class="pill">{%htmlspecialchars($plugin['group'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</span></div><p>{%htmlspecialchars($plugin['text'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</p><div class="plugin-dependency"><span>运行条件</span>{%htmlspecialchars($plugin['need'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</div>{%if ($plugin['name'] === 'Route')%}<p><a class="text-link" href="#route-custom">自定义 URL 映射 →</a> · <a class="text-link" href="#route-methods">HTTP 方法注解 →</a></p>{%/if%}</article>{%/foreach%}</div><p id="plugin-empty" class="plugin-empty" hidden>没有找到匹配的插件。试试其他关键词或分类。</p><noscript><p>当前显示全部插件；启用 JavaScript 后可搜索与筛选。</p></noscript></section><section class="section container code-pair"><div><div class="eyebrow">JSON RESPONSE</div><h2>Json：返回结构化数据</h2><div class="code-block"><button type="button" data-copy="json-code">复制</button><pre><code id="json-code">{%htmlspecialchars($code['json'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</code></pre></div></div><div><div class="eyebrow">TEMPLATE RENDERING</div><h2>Tpl：组织页面模板</h2><div class="code-block"><button type="button" data-copy="tpl-code">复制</button><pre><code id="tpl-code">{%htmlspecialchars($code['tpl'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</code></pre></div></div></section><section class="section container" id="route-custom" aria-labelledby="route-custom-title">
  <div class="eyebrow">ROUTE / URL MAPPING</div>
  <h2 id="route-custom-title">Route：自定义 URL 映射</h2>
  <p>将 /r/hello 映射到 Lab\RouteController::Entry()。PHP 与 INI 规则按注册顺序匹配，查询串不参与匹配，参数保留在 $request->get 中。</p>
  <div class="code-pair">
    <div><h3>PHP：samples/config/routes.php</h3><div class="code-block"><button type="button" data-copy="route-php-code">复制</button><pre><code id="route-php-code">{%htmlspecialchars($code['route_php'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</code></pre></div></div>
    <div><h3>INI：samples/config/routes.ini</h3><div class="code-block"><button type="button" data-copy="route-ini-code">复制</button><pre><code id="route-ini-code">{%htmlspecialchars($code['route_ini'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</code></pre></div></div>
  </div>
  <p>在 run() 前加载配置；入口已规范化 URI 时，用 Route::parse($requestPath) 显式匹配原始路径。未命中规则时继续默认命名空间调度。</p>
  <div class="doc-note"><strong>运行本站真实规则</strong><p><a class="text-link" href="/r/hello?source=plugins">PHP 单参数 →</a> · <a class="text-link" href="/r-ini/hello?source=plugins">INI 单参数 →</a> · <a class="text-link" href="/r-pair/2026/11">INI 多参数 →</a> · <a class="text-link" href="/tasks">固定短地址 →</a></p><p>路由实验室显示原始 URL、映射后的完整路径、查询参数和实际配置。月份 13 不符合规则，访问 /r-pair/2026/13 会返回 404。</p></div>
</section>
<section class="section container" id="route-methods" aria-labelledby="route-methods-title">
  <div class="eyebrow">ROUTE / HTTP METHODS</div>
  <h2 id="route-methods-title">Route：Controller 请求方法注解</h2>
  <div class="code-pair">
    <div><div class="code-block"><button type="button" data-copy="route-methods-code">复制</button><pre><code id="route-methods-code">{%htmlspecialchars($code['route_methods'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</code></pre></div></div>
    <div>
      <h3>单方法与多方法</h3>
      <p>method 限制单个方法，methods 允许多个方法。同时设置时取并集并去重；未声明限制时保持原有行为。HEAD 和 OPTIONS 需要显式加入列表。</p>
      <p>不匹配时在构造 Controller 前抛出 SlightPHP\Exception，入口根据 METHOD_NOT_ALLOWED 生成 405，并使用 allowedMethods 设置 Allow 响应头。自定义 URL 映射同样执行这些限制。</p>
      <h3>点击验证实际响应</h3>
      <p>本站 /tasks.json 映射到只允许 GET 的 Api；/r/hello 的 Entry 同时允许 GET 与 POST。以下 POST 示例只验证请求方法，不修改任务数据。</p>
      <p><a class="button" href="/tasks.json">GET 接口：200 →</a></p>
      <form method="post" action="/tasks.json"><button class="button" type="submit">POST 接口：405 / Allow: GET →</button></form>
      <p><a class="button" href="/r/hello?source=plugins">GET 路由：200 →</a></p>
      <form method="post" action="/r/hello?source=plugins"><button class="button" type="submit">POST 路由：200 →</button></form>
      <p>方法限制不替代权限或 CSRF 校验。完整约定见仓库 docs/routes.md 和 docs/framework.md。</p>
    </div>
  </div>
</section>
<section class="container doc-panel"><h2>使用前，先确认依赖。</h2><p>Composer 包声明 PHP ≥ 8.0、PDO、Redis 扩展、Guzzle 和 Gregwar Captcha。独立核心与本仓库示例可以直接加载使用；数据库、Redis、图形等插件仍需对应扩展和服务。历史插件需逐项验证当前环境兼容性。</p><p>Tpl 当前实现直接输出模板，不返回 HTML 字符串；Config 支持 JSON/JSONC，默认仅保留进程内缓存。具体行为请查看仓库文档。</p><a class="text-link" href="/workspace/main/plugins">运行配置解析与环境检测 →</a></section><div class="section"></div>
