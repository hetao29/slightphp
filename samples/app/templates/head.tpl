<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="SlightPHP 轻量级 PHP 框架：路径调度、PAGE 与 NAMESPACE 控制器、独立插件和使用指南。">
<meta name="theme-color" content="#101114">
<title>{%htmlspecialchars($title, ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%} · SlightPHP</title>
<link rel="stylesheet" href="/assets/site.css?v={%filemtime(SlightPHP::getAppDir() . '/../www/assets/site.css')%}"><script src="/assets/site.js?v={%filemtime(SlightPHP::getAppDir() . '/../www/assets/site.js')%}" defer></script>
</head>
<body>
<a class="skip-link" href="#main">跳转到正文</a>
<header class="site-header"><div class="container nav-row">
<a class="brand" href="/" aria-label="SlightPHP 首页"><span class="brand-mark" aria-hidden="true">s.</span><span>SlightPHP</span><small class="version-tag">PHP 8+</small></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="site-nav" type="button">导航 ☰</button>
<nav id="site-nav" aria-label="主导航">{%foreach ($nav as $key => $item)%}<a href="{%htmlspecialchars($item[1], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}" {%if (strcmp($view, $key) == 0)%}aria-current="page"{%/if%}><span class="nav-label">{%htmlspecialchars($item[0], ENT_QUOTES + ENT_SUBSTITUTE, 'UTF-8')%}</span></a>{%/foreach%}<a class="github-link" href="https://github.com/hetao29/slightphp" target="_blank" rel="noopener noreferrer">GitHub ↗</a></nav>
<button class="theme-toggle" type="button" aria-label="切换浅色主题" aria-pressed="false"><span data-theme-icon aria-hidden="true">☀</span><span data-theme-label>浅色</span></button>
</div></header>
<main id="main">
