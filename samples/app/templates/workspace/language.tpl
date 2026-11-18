{include "workspace-head.tpl"}
<div class="eyebrow">LANGUAGE / JSON</div>
<h1>{SampleI18n::t('language_example')}</h1>
<p class="lead">{SampleI18n::t('language_example_intro')}</p>
<section class="panel">
<h2>{SampleI18n::t('language_demo_try')}</h2>
<form class="language-demo-form" method="get" action="/lab/language/entry">
<label for="demo-locale">{SampleI18n::t('language')}<select id="demo-locale" name="lang">
{foreach (SampleI18n::LANGUAGES as $key => $label)}<option value="{$key}" {if ($selected_locale === $key)}selected{/if}>{$label}</option>{/foreach}
</select></label>
<label for="demo-name">{SampleI18n::t('language_demo_name')}<input id="demo-name" name="name" value="{$name|escape}" maxlength="80" required></label>
<button class="primary" type="submit">{SampleI18n::t('language_demo_run')}</button>
</form>
{if ($invalid)}<p class="notice" role="alert">{SampleI18n::t('language_demo_invalid_name')}</p>{/if}
<p>{SampleI18n::t('language_demo_current')} <code data-language-locale>{$selected_locale|escape}</code> · <code>zone = demo</code></p>
<div class="notice" role="status" data-language-greeting>{$greeting|escape}</div>
<dl><dt>Language::tr('action', 'demo')</dt><dd data-language-action>{$action|escape}</dd><dt>Language::tr('missing_demo_key', 'demo')</dt><dd><code data-language-fallback>{$missing|escape}</code></dd></dl>
<p>{SampleI18n::t('language_demo_fallback_note')}</p>
</section>
<section class="panel">
<h2>{SampleI18n::t('language_demo_json')}</h2>
<p><code>{$catalog_path|escape}</code></p>
<div class="code-block"><button type="button" data-copy="language-json">{SampleI18n::t('copy')}</button><pre><code id="language-json">{$catalog|escape}</code></pre></div>
</section>
<section class="panel">
<h2>{SampleI18n::t('language_demo_code')}</h2>
<p>{SampleI18n::t('language_demo_boot_note')}</p>
<div class="code-block"><button type="button" data-copy="language-php">{SampleI18n::t('copy')}</button><pre><code id="language-php">{$code|escape}</code></pre></div>
<a href="/site/main/docs?doc=plugins.md#language">{SampleI18n::t('language_demo_docs')}</a>
</section>
{include "workspace-footer.tpl"}
