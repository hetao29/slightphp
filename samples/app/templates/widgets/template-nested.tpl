<div data-example="part-nested">
    <p>{SampleI18n::e('nested_module_variable')}<strong>{$scope|escape}</strong></p>
    {part "/widgets/template/returned/nested"}
    <p data-example="nested-after">{SampleI18n::e('after_the_inner_part_the_module_variable_is_still')}<strong>{$scope|escape}</strong></p>
</div>
