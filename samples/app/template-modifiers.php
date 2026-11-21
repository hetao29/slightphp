<?php

/** 自定义 Modifier：当前值是第一个参数，冒号后的参数依次传入；只返回数据。 */
function sample_tpl_label($value, string $prefix = 'Member', string $suffix = ''): string
{
    return $prefix . ': ' . (string) $value . $suffix;
}
