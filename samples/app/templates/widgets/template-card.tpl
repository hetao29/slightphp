<article data-example="part-card">
    <h3>{SampleI18n::t('widgets_module_card')}</h3>
    <p>{SampleI18n::t('module_variable')}<strong>{$scope|escape}</strong>{SampleI18n::t('path_parameter')}<span data-example="part-label">{$label|escape}</span></p>
    {include "widgets/template-meta.tpl"}
</article>
