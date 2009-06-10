<?php
require_once("../SlightPHP.php");
SlightPHP::setDebug(true);
SlightPHP::setSplitFlag("-_");
SlightPHP::setPluginsDir("../plugins");	

SlightPHP::loadPlugin("SLanguage");


Slanguage::setLanguageDir("./language");

Slanguage::setLocale("en");
echo Slanguage::tr("name","main");
Slanguage::setLocale("zh-CN");
echo Slanguage::tr("name","main");
?>
