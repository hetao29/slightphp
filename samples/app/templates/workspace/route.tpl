<div class="eyebrow">UNDER THE HOOD</div>
<h1>{SampleI18n::e('trace_every_request')}</h1>
<p class="lead">{SampleI18n::e('map_custom_urls_to_namespaced_controllers_with_php_or')}</p>
<section class="panel">
  <h2>{SampleI18n::e('route_lab')}</h2>
  <p>{SampleI18n::e('enter_a_parameter_to_visit_this_page_through_the')}</p>
  <form id="route-form" data-base="/r/">
    <label for="segment">{SampleI18n::e('extra_parameter_letters_numbers_or_underscores_up_to_40')}</label>
    <div class="add-form"><input id="segment" name="segment" required pattern="[A-Za-z0-9_]{1,40}" maxlength="40" value="hello"><button class="primary">{SampleI18n::e('run_route')}</button></div>
  </form>
  <noscript><a href="/r/hello">{SampleI18n::e('open_the_hello_custom_route_example')}</a></noscript>
  <h3>{SampleI18n::e('this_request')}</h3>
  <dl>
    <dt>{SampleI18n::e('original_request')}</dt><dd>{htmlspecialchars($original_uri, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</dd>
    <dt>{SampleI18n::e('controller_file')}</dt><dd>app/Lab/RouteController.php</dd>
    <dt>{SampleI18n::e('actual_method')}</dt><dd>Lab\RouteController::Entry($request, $response)</dd>
  </dl>
  <h3>{SampleI18n::e('request_route_complete_mapped_path')}</h3>
  <pre data-route-segments>{htmlspecialchars($json, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</pre>
  <h3>{SampleI18n::e('request_get_query_parameters_preserved_separately')}</h3>
  <pre data-route-query>{htmlspecialchars($query_json, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</pre>
</section>
<section class="panel">
  <h2>{SampleI18n::e('runnable_custom_routes')}</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>URL</th><th>{SampleI18n::e('configuration_source')}</th><th>{SampleI18n::e('mapped_result')}</th></tr></thead>
    <tbody>
      <tr><td><a href="/r/hello?source=php">/r/hello?source=php</a></td><td>routes.php</td><td>/lab/route/entry/hello</td></tr>
      <tr><td><a href="/r-json/hello?source=json">/r-json/hello?source=json</a></td><td>routes.json</td><td>/lab/route/entry/hello</td></tr>
      <tr><td><a href="/r-pair/2026/11">/r-pair/2026/11</a></td><td>{SampleI18n::e('routes_json_multiple_parameters')}</td><td>/lab/route/entry/2026/11</td></tr>
      <tr><td><a href="/tasks">/tasks</a></td><td>{SampleI18n::e('routes_json_fixed_url')}</td><td>/workspace/main/entry</td></tr>
      <tr><td><a href="/tasks.json">/tasks.json</a></td><td>{SampleI18n::e('routes_json_get_api')}</td><td>/workspace/main/api</td></tr>
      <tr><td><a href="/lab/route/entry/hello">/lab/route/entry/hello</a></td><td>{SampleI18n::e('default_namespace_path')}</td><td>Lab\RouteController::Entry</td></tr>
    </tbody>
  </table></div>
  <p>{SampleI18n::e('the_first_matching_rule_in_registration_order_wins_query')}</p>
</section>
<section class="panel">
  <h2>{SampleI18n::e('php_configuration_samples_config_routes_php')}</h2>
  <pre>{htmlspecialchars($routes_php, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</pre>
  <h2>{SampleI18n::e('json_configuration_samples_config_routes_json')}</h2>
  <pre>{htmlspecialchars($routes_json, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</pre>
  <p>{SampleI18n::e('the_entry_loads_rules_before_run_then_route_parse')}</p>
</section>
