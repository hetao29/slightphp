<div class="eyebrow">UNDER THE HOOD</div>
<h1>让每个请求，有迹可循。</h1>
<p class="lead">通过 PHP 或 INI 规则，把自定义 URL 映射到命名空间控制器。</p>
<section class="panel">
  <h2>路由实验室</h2>
  <p>输入一个参数，通过 PHP 自定义路由 /r/:slug 访问当前页面。</p>
  <form id="route-form" data-base="/r/">
    <label for="segment">附加参数（字母、数字或下划线，最多 40 位）</label>
    <div class="add-form"><input id="segment" name="segment" required pattern="[A-Za-z0-9_]{1,40}" maxlength="40" value="hello"><button class="primary">运行路由 →</button></div>
  </form>
  <noscript><a href="/r/hello">打开 hello 自定义路由示例</a></noscript>
  <h3>本次执行</h3>
  <dl>
    <dt>原始请求</dt><dd>{%htmlspecialchars($original_uri, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</dd>
    <dt>控制器文件</dt><dd>app/Lab/RouteController.php</dd>
    <dt>实际方法</dt><dd>Lab\RouteController::Entry($request, $response)</dd>
  </dl>
  <h3>$request->route（映射后的完整路径）</h3>
  <pre data-route-segments>{%htmlspecialchars($json, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</pre>
  <h3>$request->get（查询参数独立保留）</h3>
  <pre data-route-query>{%htmlspecialchars($query_json, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</pre>
</section>
<section class="panel">
  <h2>可直接运行的自定义路由</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>URL</th><th>配置来源</th><th>映射结果</th></tr></thead>
    <tbody>
      <tr><td><a href="/r/hello?source=php">/r/hello?source=php</a></td><td>routes.php</td><td>/lab/route/entry/hello</td></tr>
      <tr><td><a href="/r-ini/hello?source=ini">/r-ini/hello?source=ini</a></td><td>routes.ini</td><td>/lab/route/entry/hello</td></tr>
      <tr><td><a href="/r-pair/2026/11">/r-pair/2026/11</a></td><td>routes.ini，多参数</td><td>/lab/route/entry/2026/11</td></tr>
      <tr><td><a href="/tasks">/tasks</a></td><td>routes.ini，固定地址</td><td>/workspace/main/entry</td></tr>
      <tr><td><a href="/tasks.json">/tasks.json</a></td><td>routes.ini，GET 接口</td><td>/workspace/main/api</td></tr>
      <tr><td><a href="/lab/route/entry/hello">/lab/route/entry/hello</a></td><td>默认命名空间路径</td><td>Lab\RouteController::Entry</td></tr>
    </tbody>
  </table></div>
  <p>规则按注册顺序取第一条匹配项；查询串不参与匹配。未命中时保留默认命名空间调度。/tasks/save 映射到 Save，仍只允许 POST，并保留 CSRF 校验。</p>
</section>
<section class="panel">
  <h2>PHP 配置：samples/config/routes.php</h2>
  <pre>{%htmlspecialchars($routes_php, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</pre>
  <h2>INI 配置：samples/config/routes.ini</h2>
  <pre>{%htmlspecialchars($routes_ini, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</pre>
  <p>入口在 run() 前加载配置，再用 Route::parse($routePath) 匹配已去除 index.php 的原始路径。pattern 不带外围分隔符，斜杠写成 \/；占位符正则使用 (?:...) 避免额外捕获组。</p>
</section>
