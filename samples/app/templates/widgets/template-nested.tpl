<div data-example="part-nested">
    <p>嵌套模块变量：<strong>{%$scope|escape%}</strong></p>
    {%part "/widgets/template/returned/nested"%}
    <p data-example="nested-after">内部 part 完成，模块变量仍为：<strong>{%$scope|escape%}</strong></p>
</div>
