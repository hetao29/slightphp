{%include "workspace-head.tpl"%}
<div class="eyebrow">TPL / INCLUDE + PART</div>
<h1>{%SampleI18n::e('templates_and_modules')%}</h1>
<p class="lead">{%SampleI18n::e('include_reuses_templates_and_current_variables_part_calls_another')%}</p>
<section class="panel">
    <h2>{%SampleI18n::e('1_include_share_parent_variables')%}</h2>
    <p>{%SampleI18n::e('the_parent_template_sets_scope_to')%} <code>PARENT</code>{%SampleI18n::e('an_include_with_a_dynamic_filename_loads_a_shared')%}</p>
    <pre><code>&#123;%include $include_child%&#125;</code></pre>
    {%include $include_child%}
</section>
<section class="panel">
    <h2>{%SampleI18n::e('2_part_call_the_widgets_module')%}</h2>
    <p>{%SampleI18n::e('card_creates_its_own_tpl_instance_sets_scope_to')%} <code>CHILD</code>{%SampleI18n::e('and_reads_the_path_parameter')%} <code>demo</code>{%SampleI18n::e('the_include_inside_the_card_shares_child')%}</p>
    <pre><code>&#123;%part "/widgets/template/card/demo"%&#125;</code></pre>
    {%part "/widgets/template/card/demo"%}
    <p data-example="parent-after-card">{%SampleI18n::e('parent_after_the_call')%}<strong>{%$scope|escape%}</strong></p>
    <p><a href="/widgets/template/card/direct">{%SampleI18n::e('open_the_module_card_directly')%}</a></p>
</section>
<section class="panel">
    <h2>{%SampleI18n::e('3_string_returns_and_mixed_output')%}</h2>
    <p>{%SampleI18n::e('part_inserts_the_module_output_followed_by_its_string')%}</p>
    <pre><code>&#123;%part "/widgets/template/returned/demo"%&#125;
&#123;%part "/widgets/template/mixed"%&#125;</code></pre>
    {%part "/widgets/template/returned/demo"%}
    <p>{%part "/widgets/template/mixed"%}</p>
</section>
<section class="panel">
    <h2>{%SampleI18n::e('4_nested_parts_and_response_completion')%}</h2>
    <p>{%SampleI18n::e('nested_calls_returned_again_after_an_internal_response_end')%}</p>
    <pre><code>&#123;%part "/widgets/template/nested"%&#125;
&#123;%part "/widgets/template/ended"%&#125;</code></pre>
    {%part "/widgets/template/nested"%}
    {%part "/widgets/template/ended"%}
    <p data-example="parent-final">{%SampleI18n::e('all_modules_finished_parent_is_still')%}<strong>{%$scope|escape%}</strong></p>
</section>
<section class="panel">
    <h2>{%SampleI18n::e('further_reading')%}</h2>
    <p>{%SampleI18n::e('for_invocation_rules_file_locations_and_exception_handling_see')%} <code>docs/templates.md</code>{%SampleI18n::e('escape_dynamic_html_where_templates_and_modules_output_it')%}</p>
</section>
{%include "workspace-footer.tpl"%}
