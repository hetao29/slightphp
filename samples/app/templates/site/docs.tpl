<section class="page-intro container">
<div class="eyebrow">DOCUMENTATION</div>
<h1>{SampleI18n::t('documentation')}</h1>
<p>{SampleI18n::t('docs_intro')}</p>
<p><a href="/site/main/docs?doc=CHANGELOG.md#6x">{SampleI18n::t('release_notes_6x')}</a> · <a href="/site/main/docs?doc=CHANGELOG.md#6x-升级与迁移指南">{SampleI18n::t('upgrade_guide_6x')}</a></p>
</section>
<div class="container docs-layout">
<nav class="docs-nav" aria-label="{SampleI18n::t('guide_contents')}">
{foreach ($documents as $name => $label)}
<a href="/site/main/docs?doc={$name}" lang="zh-CN" {if ($document['name'] === $name)}aria-current="page"{/if}>{$label}</a>
{/foreach}
</nav>
<article class="docs-content" lang="zh-CN">{$document['html']|raw}</article>
</div>
