<section class="page-intro container"><div class="eyebrow">DOCUMENTATION</div><h1>{SampleI18n::e('documentation')}</h1><p>{SampleI18n::e('docs_intro')}</p></section>
<div class="container docs-layout">
<nav class="docs-nav" aria-label="{SampleI18n::e('guide_contents')}">
{foreach ($documents as $name => $label)}
<a href="/site/main/docs?doc={rawurlencode($name)}" lang="zh-CN" {if ($document['name'] === $name)}aria-current="page"{/if}>{htmlspecialchars($label, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')}</a>
{/foreach}
</nav>
<article class="docs-content" lang="zh-CN">{$document['html']}</article>
</div>
