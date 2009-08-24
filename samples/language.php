<?php
require_once("global.php");
SlightPHP::setDebug(true);
SlightPHP::setSplitFlag("-_");

Slanguage::setLanguageDir("./language");

Slanguage::setLocale("en");
echo Slanguage::tr("name","main");
Slanguage::setLocale("zh-CN");
echo Slanguage::tr("name","main");
?>
