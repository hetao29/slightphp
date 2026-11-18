{include "workspace-head.tpl"}
<div class="eyebrow">PLUGIN LAB / {$name}</div>
<h1>{$name} · {SampleI18n::t('lab_example')}</h1>
<p class="lead">{SampleI18n::t('lab_intro_' . $id)}</p>
<nav class="lab-links" aria-label="{SampleI18n::t('lab_catalog')}">
  <a href="/lab/template/entry">Tpl</a>
  <a href="/lab/language/entry">Language</a>
  {foreach ($labs as $lab)}<a href="/lab/plugin/entry/{$lab}" {if ($lab === $id)}aria-current="page"{/if}>{ucfirst($lab)}</a>{/foreach}
</nav>
<p><a class="text-link" href="/site/main/docs?doc={$manual}.md">{SampleI18n::t('lab_manual')} →</a> · <a href="/site/main/plugins">{SampleI18n::t('lab_catalog')}</a></p>
{if (!$ready)}<p class="notice" role="status">{SampleI18n::t('lab_not_ready')} {SampleI18n::t('lab_setup')}</p>{/if}
<section class="panel">
  <h2>{SampleI18n::t('lab_run')}</h2>
  {if ($id === 'config')}
  <form method="get" action="/lab/plugin/entry/config" class="lab-form">
    <label>{SampleI18n::t('lab_format')}<select name="format"><option value="jsonc" {if ($format === 'jsonc')}selected{/if}>JSONC</option><option value="json" {if ($format === 'json')}selected{/if}>JSON</option></select></label>
    <label>zone<select name="zone"><option value="application" {if ($zone === 'application')}selected{/if}>application</option><option value="missing" {if ($zone === 'missing')}selected{/if}>missing → default / null</option><option value="default" {if ($zone === 'default')}selected{/if}>default</option><option value="whole" {if ($zone === 'whole')}selected{/if}>{SampleI18n::t('lab_whole')}</option></select></label>
    <button type="submit">{SampleI18n::t('lab_run')}</button>
  </form>
  {elseif ($id === 'request')}
  <form method="get" action="/lab/plugin/entry/request" class="lab-form">
    <label>GET · message<input name="message" value="Hello SlightPHP" maxlength="200"></label>
    <button type="submit">{SampleI18n::t('lab_send')}</button>
  </form>
  <form method="post" enctype="multipart/form-data" action="/lab/plugin/run/request" class="lab-form">
    {\SlightPHP\Csrf::tokenInput()|raw}
    <label>POST · message<input name="message" value="Hello SlightPHP" maxlength="200"></label>
    <label>{SampleI18n::t('lab_file')}<input type="file" name="attachment"></label>
    <p>{SampleI18n::t('lab_upload_note')}</p>
    <button type="submit">{SampleI18n::t('lab_send')}</button>
  </form>
  <div data-lab-json data-token="{$token}">
    <p>{SampleI18n::t('lab_json_note')}</p>
    <button type="button" data-json-case="valid" disabled>POST JSON</button>
    <button type="button" data-json-case="invalid" disabled>{SampleI18n::t('lab_invalid_json')}</button>
    <pre class="sample-response" aria-live="polite"><code data-lab-output>{SampleI18n::t('lab_ready')}</code></pre>
  </div>
  {elseif ($id === 'response')}
  <div data-lab-response>
    <div class="lab-links">
      <a href="/lab/plugin/preview?kind=json" data-preview>201 · JSON</a>
      <a href="/lab/plugin/preview?kind=text" data-preview>418 · text</a>
      <a href="/lab/plugin/preview?kind=ended" data-preview>write / end</a>
      <a href="/lab/plugin/preview?kind=redirect">303 · Location</a>
    </div>
    <pre class="sample-response" aria-live="polite"><code data-lab-output>{SampleI18n::t('lab_ready')}</code></pre>
  </div>
  <form method="post" action="/lab/plugin/run/response" class="lab-form">
    {\SlightPHP\Csrf::tokenInput()|raw}
    <label>Cookie<select name="action"><option value="set">{SampleI18n::t('lab_cookie_set')}</option><option value="delete">{SampleI18n::t('lab_cookie_delete')}</option></select></label>
    <button type="submit">{SampleI18n::t('lab_run')}</button>
  </form>
  <p><a href="/lab/plugin/entry/response">{SampleI18n::t('lab_cookie_read')}</a></p>
  {else}
  <form method="post" action="/lab/plugin/run/{$id}" class="lab-form">
    {\SlightPHP\Csrf::tokenInput()|raw}
    {if ($id === 'captcha')}
      {if ($ready)}<img src="/lab/plugin/image" width="140" height="50" alt="{SampleI18n::t('lab_captcha_alt')}" data-captcha-image><p><a href="/lab/plugin/entry/captcha">{SampleI18n::t('lab_captcha_refresh')}</a></p>{/if}
      <label>{SampleI18n::t('lab_captcha_code')}<input name="captcha" maxlength="10" autocomplete="off" required></label>
    {else}
      <label>{SampleI18n::t('lab_message')}<input name="message" value="Hello SlightPHP" maxlength="200"></label>
    {/if}
    {if ($id === 'log' || $id === 'error')}
      <label>{SampleI18n::t('lab_format')}<select name="format"><option value="json">JSON</option><option value="text">text</option>{if ($id === 'error')}<option value="html">HTML</option>{/if}</select></label>
    {/if}
    {if ($id === 'log')}
      <label>{SampleI18n::t('lab_level')}<select name="level"><option>notice</option><option>warning</option><option>error</option><option>debug</option></select></label>
    {elseif ($id === 'error')}
      <label>{SampleI18n::t('lab_kind')}<select name="kind"><option value="notice">E_USER_NOTICE</option><option value="exception">RuntimeException</option></select></label>
    {elseif ($id === 'http')}
      <label>{SampleI18n::t('lab_method')}<select name="method"><option>GET</option><option>POST</option><option value="404">404 · RequestException</option></select></label>
      <p>{SampleI18n::t('lab_http_note')}</p>
    {/if}
    <button type="submit" {if (!$ready)}disabled{/if}>{SampleI18n::t('lab_run')}</button>
  </form>
  {/if}
  <noscript><p>{SampleI18n::t('lab_nojs')}</p></noscript>
</section>
<section class="panel">
  <h2>{SampleI18n::t('lab_result')}</h2>
  <pre class="sample-response" aria-live="polite"><code data-plugin-result>{$result !== '' ? $result : SampleI18n::t('lab_ready')}</code></pre>
</section>
<section class="panel">
  <h2>{SampleI18n::t('lab_code')}</h2>
  <div class="code-block"><button type="button" data-copy="plugin-code">{SampleI18n::t('copy')}</button><pre><code id="plugin-code">{$code}</code></pre></div>
  <p><a class="text-link" href="/site/main/docs?doc={$manual}.md">{SampleI18n::t('lab_manual')} →</a></p>
</section>
<footer>{SampleI18n::t('lab_footer')}</footer></main></body></html>
