{include "workspace-head.tpl"}
<div class="eyebrow">TPL / INCLUDE + PART + CONTEXT</div>
<h1>{SampleI18n::t('templates_and_modules')}</h1>
<p class="lead">{SampleI18n::t('include_reuses_templates_and_current_variables_part_calls_another')}</p>
<section class="panel">
    <h2>{SampleI18n::t('1_include_share_parent_variables')}</h2>
    <p>{SampleI18n::t('the_parent_template_sets_scope_to')} <code>PARENT</code>{SampleI18n::t('an_include_with_a_dynamic_filename_loads_a_shared')}</p>
    <pre><code>&#123;include $include_child&#125;</code></pre>
    {include $include_child}
</section>
<section class="panel">
    <h2>{SampleI18n::t('2_part_call_the_widgets_module')}</h2>
    <p>{SampleI18n::t('card_creates_its_own_tpl_instance_sets_scope_to')} <code>CHILD</code>{SampleI18n::t('and_reads_the_path_parameter')} <code>demo</code>{SampleI18n::t('the_include_inside_the_card_shares_child')}</p>
    <pre><code>&#123;part "/widgets/template/card/demo"&#125;</code></pre>
    {part "/widgets/template/card/demo"}
    <p data-example="parent-after-card">{SampleI18n::t('parent_after_the_call')}<strong>{$scope|escape}</strong></p>
    <p><a href="/widgets/template/card/direct">{SampleI18n::t('open_the_module_card_directly')}</a></p>
</section>
<section class="panel">
    <h2>{SampleI18n::t('3_string_returns_and_mixed_output')}</h2>
    <p>{SampleI18n::t('part_inserts_the_module_output_followed_by_its_string')}</p>
    <pre><code>&#123;part "/widgets/template/returned/demo"&#125;
&#123;part "/widgets/template/mixed"&#125;</code></pre>
    {part "/widgets/template/returned/demo"}
    <p>{part "/widgets/template/mixed"}</p>
</section>
<section class="panel">
    <h2>{SampleI18n::t('4_nested_parts_and_response_completion')}</h2>
    <p>{SampleI18n::t('nested_calls_returned_again_after_an_internal_response_end')}</p>
    <pre><code>&#123;part "/widgets/template/nested"&#125;
&#123;part "/widgets/template/ended"&#125;</code></pre>
    {part "/widgets/template/nested"}
    {part "/widgets/template/ended"}
    <p data-example="parent-final">{SampleI18n::t('all_modules_finished_parent_is_still')}<strong>{$scope|escape}</strong></p>
</section>
<section class="panel">
    <h2>{SampleI18n::t('tpl_demo_title')}</h2>
    <p>{SampleI18n::t('tpl_demo_intro')}</p>
    <pre><code>$tpl = new \SlightPHP\Tpl();
$tpl-&gt;render('widgets/template-context.tpl', $data);</code></pre>
    {part "/widgets/template/context"}
    <p><a href="/widgets/template/context">{SampleI18n::t('tpl_demo_direct')}</a></p>
</section>
<section class="panel">
    <h2>{SampleI18n::t('further_reading')}</h2>
    <p>{SampleI18n::t('for_invocation_rules_file_locations_and_exception_handling_see')} <a href="/site/main/docs?doc=templates.md">docs/templates.md</a>{SampleI18n::t('escape_dynamic_html_where_templates_and_modules_output_it')}</p>
</section>
{include "workspace-footer.tpl"}
