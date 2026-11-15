<?php

use SlightPHP\Route;

// PHP 规则：/r/hello -> /lab/route/entry/hello。
Route::set([
    'pattern' => '^\/r\/:slug$',
    ':slug' => '[A-Za-z0-9_]{1,40}',
    'zone' => 'lab',
    'page' => 'route',
    'entry' => 'entry',
]);

// INI 规则追加在 PHP 规则之后，首条匹配规则优先。
Route::setConfigFile(__DIR__ . '/routes.ini');
