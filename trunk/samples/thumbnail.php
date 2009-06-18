<?php
require_once("../SlightPHP.php");
SlightPHP::setPluginsDir("../plugins");
SlightPHP::loadPlugin("SThumbnail");

$thumbnail = new SThumbnail('thumbnail/sampleimage.jpg', 100); //

$thumbnail->addLogo('thumbnail/logo2.png', 3, 1);
$thumbnail->addLogo('thumbnail/icon2.png', 2, 3); // add more logos if you want
/* set max. width and height of the thumbnail (default: 100, 100) */
$thumbnail->setMaxSize(150, 121);
/* quality or speed when creating the thumbnail (default: true) */
$thumbnail->setQualityOutput(TRUE);

/* picture type (png, jpg, gif, wbmp), jpg-quality (0-100) (default: png, 75) */
$a = $thumbnail->genFile("thumbnail/thumbnail.png");
var_dump($a);

?>