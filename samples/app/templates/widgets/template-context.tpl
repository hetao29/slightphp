<div class="tpl-context-examples" data-template-context>
    <h3>{SampleI18n::t('tpl_demo_modifiers')}</h3>
    <dl>
        <dt><code>{$sources['default']}</code></dt>
        <dd data-template-case="default">{$empty_name|default:SampleI18n::t('tpl_demo_guest')}</dd>
        <dt><code>{$sources['escape']}</code></dt>
        <dd data-template-case="escape">{$sample_text|escape}</dd>
        <dt><code>{$sources['tr']}</code></dt>
        <dd data-template-case="tr">{$translation_key|tr}</dd>
        <dt><code>{$sources['tostring']}</code></dt>
        <dd><pre data-template-case="tostring">{$debug_value|tostring}</pre></dd>
        <dt><code>{$sources['version']}</code></dt>
        <dd><code data-template-case="version">{$asset|version:"2.0"}</code></dd>
        <dt><code>{$sources['raw']}</code></dt>
        <dd>{$trusted_html|raw}</dd>
        <dt><code>{$sources['chain']}</code></dt>
        <dd data-template-case="chain">{$empty_name|default:"<Guest>"|escape}</dd>
    </dl>
    <p>{SampleI18n::t('tpl_demo_modifier_note')}</p>

    <h3>{SampleI18n::t('tpl_demo_custom_modifiers')}</h3>
    <p>{SampleI18n::t('tpl_demo_custom_modifier_note')}</p>
    <pre><code>{$sources['custom_definition']}</code></pre>
    <pre><code>{$sources['custom_load']}</code></pre>
    <dl>
        <dt><code>{$sources['custom']}</code></dt>
        <dd data-template-case="custom-modifier">{$modifier_name|sample_tpl_label:$modifier_prefix:"!"}</dd>
    </dl>

    <h3>{SampleI18n::t('tpl_demo_system_modifiers')}</h3>
    <p>{SampleI18n::t('tpl_demo_system_modifier_note')}</p>
    <dl>
        <dt><code>{$sources['system_trim']}</code></dt>
        <dd data-template-case="system-trim">{$modifier_text|trim}</dd>
        <dt><code>{$sources['system_upper']}</code></dt>
        <dd data-template-case="system-upper">{$modifier_name|strtoupper}</dd>
        <dt><code>{$sources['system_substr']}</code></dt>
        <dd data-template-case="system-substr">{$modifier_name|substr:0:3}</dd>
    </dl>

    <h3>{SampleI18n::t('tpl_demo_multilevel_modifiers')}</h3>
    <p>{SampleI18n::t('tpl_demo_multilevel_modifier_note')}</p>
    <dl>
        <dt><code>{$sources['multilevel']}</code></dt>
        <dd data-template-case="multilevel-modifier">{$modifier_text|trim|strtoupper|sample_tpl_label:"Framework":"!"|escape}</dd>
    </dl>
    <p>{SampleI18n::t('tpl_demo_modifier_resolution')}</p>

    <h3>{SampleI18n::t('tpl_demo_html_url')}</h3>
    <label for="tpl-context-input">{SampleI18n::t('tpl_demo_input')}</label>
    <input id="tpl-context-input" data-template-case="attribute" value="{$sample_text}" readonly>
    <p><a data-template-case="url" href="/lab/template/entry?q={$query|escape:"url"}">{$query}</a></p>
    <pre><code>{$sources['url']}</code></pre>

    <h3>{SampleI18n::t('tpl_demo_javascript')}</h3>
    <pre><code>{$sources['javascript']}</code></pre>
    <pre data-template-case="javascript">{SampleI18n::t('tpl_demo_js_pending')}</pre>
    <button type="button" data-template-action onclick="this.nextElementSibling.textContent={$sample_text}">{SampleI18n::t('tpl_demo_event_button')}</button><output data-template-case="event"></output>
    <pre><code>{$sources['event']}</code></pre>
    <script data-template-script>
    (() => {
        const data = {$payload};
        const single = '{$sample_text}';
        const double = "{$sample_text}";
        const template = `{$sample_text}`;
        const regexp = /{$sample_text}/u;
        {$js_prefix|raw}{$sample_text}";
        const values = {data, single, double, template, viaRaw, matches: regexp.test(single)};
        const output = document.querySelector('[data-template-case="javascript"]');
        if (output) output.textContent = JSON.stringify(values, null, 2);
    })();
    </script>

    <h3>{SampleI18n::t('tpl_demo_css')}</h3>
    <pre><code>{$sources['css']}</code></pre>
    <style data-template-style>
    #tpl-context-css::after { content:"{$css_text}"; color:{$color}; }
    </style>
    <p id="tpl-context-css" data-template-case="css">CSS: </p>

    <h3>{SampleI18n::t('tpl_demo_default_delimiters')}</h3>
    <pre><code>{$sources['default_delimiters']}</code></pre>
    {$default_output|raw}

    <h3>{SampleI18n::t('tpl_demo_comments')}</h3>
    <p>{SampleI18n::t('tpl_demo_comments_note')}</p>
    <pre><code>{$sources['comments']}</code></pre>
    {$comments_output|raw}

    <h3>{SampleI18n::t('tpl_demo_cache')}</h3>
    <p>{SampleI18n::t('tpl_demo_cache_note')}</p>
    <pre><code>{$sources['cache']}</code></pre>
    <p>{$cache_first|raw} / {$cache_second|raw}</p>

    <h3>{SampleI18n::t('tpl_demo_rejections')}</h3>
    <p>{SampleI18n::t('tpl_demo_rejection_note')}</p>
    <dl>{foreach $rejections as $case}<dt>{$case['label']}</dt><dd data-template-rejection>{$case['status']}</dd>{/foreach}</dl>
</div>
