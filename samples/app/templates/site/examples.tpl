<section class="page-intro container">
  <div class="eyebrow">SAMPLES / {SampleI18n::e('learn_by_doing_examples')}</div>
  <h1>{SampleI18n::e('sample_walkthrough_title')}</h1>
  <p>{SampleI18n::e('sample_walkthrough_intro')}</p>
</section>
<div class="container guide-layout sample-walkthrough">
  <nav class="guide-nav" aria-label="{SampleI18n::e('guide_contents')}">
    {foreach ($chapters as $chapter)}
    <a href="#{htmlspecialchars($chapter['id'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}">{htmlspecialchars($chapter['number'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')} / {SampleI18n::e($chapter['title'])}</a>
    {/foreach}
    <a href="#sample-demos">{SampleI18n::e('sample_live_demos')}</a>
  </nav>
  <div class="guide-content">
    {foreach ($chapters as $chapter)}
    <section id="{htmlspecialchars($chapter['id'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}">
      <div class="eyebrow">{htmlspecialchars($chapter['number'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')} / SAMPLES</div>
      <h2>{SampleI18n::e($chapter['title'])}</h2>
      <p>{SampleI18n::e($chapter['intro'])}</p>
      {foreach ($chapter['blocks'] as $index => $block)}
      <h3>{htmlspecialchars($block['label'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</h3>
      <div class="code-block">
        <button type="button" data-copy="{htmlspecialchars($chapter['id'] . '-code-' . $index, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}">{SampleI18n::e('copy')}</button>
        <pre><code id="{htmlspecialchars($chapter['id'] . '-code-' . $index, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}">{htmlspecialchars($block['code'], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</code></pre>
      </div>
      {/foreach}
      {foreach ($chapter['notes'] as $note)}<p>{SampleI18n::e($note)}</p>{/foreach}
      {if ($chapter['id'] === 'sample-route-methods')}
      <div class="doc-note" data-route-method-demo>
        <strong>{SampleI18n::e('sample_route_methods_try')}</strong>
        <p>{SampleI18n::e('sample_route_methods_try_intro')}</p>
        <div class="sample-links">
          <button class="button" type="button" data-route-method-case="get">Entry · GET → 200</button>
          <button class="button" type="button" data-route-method-case="rejected">Entry · POST → 405</button>
          <button class="button" type="button" data-route-method-case="preview-get">Preview · GET → 200</button>
          <button class="button" type="button" data-route-method-case="preview-post">Preview · POST → 200</button>
        </div>
        <pre class="sample-response" aria-live="polite" aria-atomic="true"><code data-demo-result>{SampleI18n::e('sample_request_type_ready')}</code></pre>
        <noscript><p>{SampleI18n::e('sample_request_type_nojs')}</p></noscript>
      </div>
      {/if}
      {if ($chapter['id'] === 'sample-request-type')}
      <div class="doc-note" data-request-type-demo>
        <strong>{SampleI18n::e('sample_request_type_try')}</strong>
        <p>{SampleI18n::e('sample_request_type_try_intro')}</p>
        <div class="sample-links">
          <button class="button" type="button" data-request-type-case="plain">{SampleI18n::e('sample_request_type_plain')}</button>
          <button class="button" type="button" data-request-type-case="ajax">Request-Type: ajax → 200</button>
          <button class="button" type="button" data-request-type-case="rejected">{SampleI18n::e('sample_request_type_rejected')}</button>
        </div>
        <pre class="sample-response" aria-live="polite" aria-atomic="true"><code data-demo-result>{SampleI18n::e('sample_request_type_ready')}</code></pre>
        <noscript><p>{SampleI18n::e('sample_request_type_nojs')}</p></noscript>
      </div>
      {/if}
      <details class="sample-sources">
        <summary>{SampleI18n::e('sample_source_files')}</summary>
        <ul>{foreach ($chapter['sources'] as $source)}<li><a class="text-link" href="https://github.com/hetao29/slightphp/blob/master/{htmlspecialchars($source, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}" target="_blank" rel="noopener noreferrer"><code>{htmlspecialchars($source, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</code></a></li>{/foreach}</ul>
      </details>
      <p class="sample-links">{foreach ($chapter['links'] as $link)}<a class="text-link" href="{htmlspecialchars($link[0], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}">{SampleI18n::e($link[1])}</a>{/foreach}</p>
    </section>
    {/foreach}
  </div>
</div>
<section class="container" id="sample-demos">
  <div class="eyebrow">TRY IT / SAMPLES</div>
  <h2>{SampleI18n::e('sample_live_demos')}</h2>
  <p>{SampleI18n::e('these_examples_run_in_this_app_without_an_external')}</p>
</section>
<section class="container example-grid"><article class="example-card"><span class="example-number">01 / NAMESPACE + SESSION</span><h2>{SampleI18n::e('task_workspace')}</h2><p>{SampleI18n::e('add_complete_delete_search_and_filter_tasks_to_see')}</p><ul><li>{SampleI18n::e('namespaced_namespace_controllers')}</li><li>{SampleI18n::e('session_isolation_and_html_escaping')}</li><li>{SampleI18n::e('form_feedback_and_progress_statistics')}</li></ul><a class="button dark" href="/workspace/main/entry">{SampleI18n::e('open_the_workspace')}</a></article><article class="example-card"><span class="example-number">02 / NAMESPACE</span><h2>{SampleI18n::e('route_lab')}</h2><p>{SampleI18n::e('run_php_and_json_custom_routes_to_inspect_the')}</p><ul><li>Lab\RouteController::Entry($request, $response)</li><li>{SampleI18n::e('php_json_rules_and_multiple_parameters')}</li><li>{SampleI18n::e('query_strings_method_limits_and_default_path_fallback')}</li></ul><a class="button dark" href="/r/hello?source=examples">{SampleI18n::e('run_the_route_lab')}</a></article><article class="example-card"><span class="example-number">03 / CONFIG + JSON</span><h2>{SampleI18n::e('configuration_and_environment_checks')}</h2><p>{SampleI18n::e('view_real_config_parsing_results_and_check_whether_pdo')}</p><ul><li>{SampleI18n::e('json_objects_arrays_numbers_and_booleans')}</li><li>{SampleI18n::e('disable_the_configuration_cache_during_development')}</li><li>{SampleI18n::e('loaded_extensions_versus_connected_services')}</li></ul><a class="button dark" href="/workspace/main/plugins">{SampleI18n::e('view_plugin_examples')}</a></article><article class="example-card"><span class="example-number">04 / REQUEST-TYPE</span><h2>{SampleI18n::e('sample_request_type_title')}</h2><p>{SampleI18n::e('sample_request_type_try_intro')}</p><ul><li>Entry / ajaxEntry</li><li>Request-Type: ajax</li><li>GET 200 / 405</li></ul><a class="button dark" href="#sample-request-type">{SampleI18n::e('sample_request_type_try')}</a></article><article class="example-card"><span class="example-number">05 / TPL + MODULES</span><h2>{SampleI18n::e('templates_and_modules')}</h2><p>{SampleI18n::e('run_include_and_part_to_see_shared_template_variables')}</p><ul><li>{SampleI18n::e('dynamic_include_filenames_and_nested_templates')}</li><li>{SampleI18n::e('cross_module_part_calls_and_path_parameters')}</li><li>{SampleI18n::e('module_output_order_and_variable_isolation')}</li></ul><a class="button dark" href="/lab/template/entry">{SampleI18n::e('run_the_template_example')}</a></article><article class="example-card"><span class="example-number">06 / LANGUAGE + JSON</span><h2>{SampleI18n::e('language_example')}</h2><p>{SampleI18n::e('language_example_card')}</p><ul><li>{SampleI18n::e('language_example_json_feature')}</li><li>{SampleI18n::e('language_example_switch_feature')}</li><li>{SampleI18n::e('language_example_fallback_feature')}</li></ul><a class="button dark" href="/lab/language/entry">{SampleI18n::e('run_language_example')}</a></article></section><section class="section container"><div class="doc-note"><strong>{SampleI18n::e('from_examples_to_real_applications')}</strong><p>{SampleI18n::e('the_workspace_demonstrates_basic_interactions_without_a_full_user')}</p></div></section>
<p class="sr-only" id="copy-status" role="status"></p>
