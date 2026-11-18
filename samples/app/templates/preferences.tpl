<div class="preferences">
<details class="language-menu">
<summary aria-label="{SampleI18n::e('language')}">{SampleI18n::LANGUAGES[SampleI18n::locale()]}</summary>
<div class="language-options">
{foreach (SampleI18n::LANGUAGES as $locale => $label)}
<a href="{SampleI18n::url($locale)}" lang="{$locale}" hreflang="{$locale}" data-language-link {if (SampleI18n::locale() === $locale)}aria-current="true"{/if}>{$label}</a>
{/foreach}
</div>
</details>
<button class="theme-toggle" type="button" aria-label="{SampleI18n::e('switch_theme')}" data-theme-mode="auto"><span data-theme-icon aria-hidden="true">◐</span><span data-theme-label>{SampleI18n::e('auto')}</span></button>
</div>
