{include "workspace-head.tpl"}
<div class="eyebrow">CSRF / SESSION + ROUTE</div>
<h1>{SampleI18n::t('csrf_demo_title')}</h1>
<p class="lead">{SampleI18n::t('csrf_demo_intro')}</p>
{if ($flash !== '')}<p class="notice" role="status" data-csrf-flash>{$flash}</p>{/if}
<p>{SampleI18n::t('csrf_demo_count')} <strong data-csrf-count>{$accepted}</strong></p>
<section class="panel">
  <h2>{SampleI18n::t('csrf_demo_form_title')}</h2>
  <p>{SampleI18n::t('csrf_demo_form_intro')}</p>
  <form method="post" action="/lab/csrf/submit" data-csrf-form>
    <input type="hidden" name="_csrf" value="{$csrf}">
    <button class="primary" type="submit">{SampleI18n::t('csrf_demo_form_button')}</button>
  </form>
  <p><code>_csrf</code> → <code>#[Route(method: 'POST', csrf: true)]</code> → <code>303</code></p>
  <p>{SampleI18n::t('csrf_demo_two_forms')}</p>
  <div class="code-block"><button type="button" data-copy="csrf-form-code">{SampleI18n::t('copy')}</button><pre><code id="csrf-form-code">{$form_code}</code></pre></div>
</section>
<section class="panel">
  <h2>{SampleI18n::t('csrf_demo_direct_title')}</h2>
  <p>{SampleI18n::t('csrf_demo_direct_intro')}</p>
  <form method="post" action="/lab/csrf/submit" data-csrf-direct-form>
    {\SlightPHP\Csrf::tokenInput()|raw}
    <button class="primary" type="submit">{SampleI18n::t('csrf_demo_form_button')}</button>
  </form>
  <div class="code-block"><button type="button" data-copy="csrf-direct-code">{SampleI18n::t('copy')}</button><pre><code id="csrf-direct-code">{$direct_form_code}</code></pre></div>
</section>
<section class="panel" data-csrf-demo data-csrf-token="{$csrf}" data-csrf-path="/lab/csrf/check" data-csrf-header="X-CSRF-Token">
  <h2>{SampleI18n::t('csrf_demo_ajax_title')}</h2>
  <p>{SampleI18n::t('csrf_demo_ajax_intro')}</p>
  <div class="csrf-demo-actions">
    <button type="button" data-csrf-case="valid" disabled>{SampleI18n::t('csrf_demo_valid')}</button>
    <button type="button" data-csrf-case="missing" disabled>{SampleI18n::t('csrf_demo_missing')}</button>
    <button type="button" data-csrf-case="invalid" disabled>{SampleI18n::t('csrf_demo_invalid')}</button>
  </div>
  <pre class="sample-response" aria-live="polite" aria-atomic="true"><code data-csrf-result>{SampleI18n::t('csrf_demo_ready')}</code></pre>
  <noscript><p>{SampleI18n::t('csrf_demo_nojs')}</p></noscript>
</section>
<section class="panel" data-csrf-demo data-csrf-token="{$csrf}" data-csrf-path="/lab/csrf/customcheck" data-csrf-header="--csrf--token">
  <h2>{SampleI18n::t('csrf_demo_custom_title')}</h2>
  <p>{SampleI18n::t('csrf_demo_custom_intro')}</p>
  <form method="post" action="/lab/csrf/customsubmit" data-csrf-custom-form>
    {$custom_csrf_input|raw}
    <button class="primary" type="submit">{SampleI18n::t('csrf_demo_form_button')}</button>
  </form>
  <p><code>#[Route(method: 'POST', csrf: '--csrf--token')]</code></p>
  <div class="csrf-demo-actions">
    <button type="button" data-csrf-case="valid" disabled>{SampleI18n::t('csrf_demo_valid')}</button>
    <button type="button" data-csrf-case="missing" disabled>{SampleI18n::t('csrf_demo_missing')}</button>
    <button type="button" data-csrf-case="invalid" disabled>{SampleI18n::t('csrf_demo_invalid')}</button>
  </div>
  <pre class="sample-response" aria-live="polite" aria-atomic="true"><code data-csrf-result>{SampleI18n::t('csrf_demo_ready')}</code></pre>
  <div class="code-block"><button type="button" data-copy="csrf-custom">{SampleI18n::t('copy')}</button><pre><code id="csrf-custom">{$custom_code}</code></pre></div>
</section>
<section class="panel">
  <h2>{SampleI18n::t('csrf_demo_rotate_title')}</h2>
  <p>{SampleI18n::t('csrf_demo_rotate_intro')}</p>
  <form method="post" action="/lab/csrf/rotate" data-csrf-rotate>
    {$csrf_input|raw}
    <button type="submit">{SampleI18n::t('csrf_demo_rotate_button')}</button>
  </form>
</section>
<section class="panel">
  <h2>{SampleI18n::t('csrf_demo_code')}</h2>
  <div class="code-block"><button type="button" data-copy="csrf-php">{SampleI18n::t('copy')}</button><pre><code id="csrf-php">{$code}</code></pre></div>
  <div class="code-block"><button type="button" data-copy="csrf-fetch">{SampleI18n::t('copy')}</button><pre><code id="csrf-fetch">{$fetch_code}</code></pre></div>
  <p><a class="text-link" href="/site/main/docs?doc=csrf.md">{SampleI18n::t('csrf_demo_manual')}</a></p>
</section>
{include "workspace-footer.tpl"}
