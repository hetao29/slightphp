<!doctype html>
<html lang="{%SampleI18n::htmlLang()%}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="{%SampleI18n::e('slightphp_a_lightweight_php_framework_path_dispatch_namespaced_control')%}">
<meta name="theme-color" content="#101114">
{%include "favicons.tpl"%}
<title>{%htmlspecialchars($title, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%} · SlightPHP 6.5.3</title>
<link rel="stylesheet" href="/assets/site.css?v={%filemtime(SlightPHP::getAppDir() . '/../www/assets/site.css')%}"><script src="/assets/site.js?v={%filemtime(SlightPHP::getAppDir() . '/../www/assets/site.js')%}" defer></script>
<script id="sample-messages" type="application/json">{%SampleI18n::clientMessages()%}</script>
<script src="/assets/theme.js?v={%filemtime(SlightPHP::getAppDir() . '/../www/assets/theme.js')%}"></script>
</head>
<body>
<a class="skip-link" href="#main">{%SampleI18n::e('skip_to_main_content')%}</a>
<header class="site-header"><div class="container nav-row">
<a class="brand" href="/" aria-label="{%SampleI18n::e('slightphp_home')%}"><span class="brand-mark" aria-hidden="true">s.</span><span>SlightPHP</span><small class="version-tag">6.5.3</small></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="site-nav" type="button">{%SampleI18n::e('menu')%}</button>
<nav id="site-nav" aria-label="{%SampleI18n::e('main_navigation')%}">{%foreach ($nav as $key => $item)%}<a href="{%htmlspecialchars($item[1], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}" {%if (strcmp($view, $key) == 0)%}aria-current="page"{%/if%}><span class="nav-label">{%htmlspecialchars($item[0], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</span></a>{%/foreach%}<a class="github-link" href="https://github.com/hetao29/slightphp" target="_blank" rel="noopener noreferrer">GitHub ↗</a></nav>
{%include "preferences.tpl"%}
</div></header>
<main id="main">
