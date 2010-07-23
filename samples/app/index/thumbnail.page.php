<?php
class index_thumbnail{
	function pageEntry($inPath){
		$path=SlightPHP::$appDir."index/";
		$a = new SError;
		$thumbnail = new SThumbnail($path.'thumbnail/sampleimage.jpg', 100); //
		
		$thumbnail->addLogo($path.'thumbnail/logo2.png', 3, 1);
		$thumbnail->addLogo($path.'thumbnail/icon2.png', 2, 3); // add more logos if you want
		/* set max. width and height of the thumbnail (default: 100, 100) */
		$thumbnail->setMaxSize(150, 121);
		/* quality or speed when creating the thumbnail (default: true) */
		$thumbnail->setQualityOutput(TRUE);
		
		/* picture type (png, jpg, gif, wbmp), jpg-quality (0-100) (default: png, 75) */
		$a = $thumbnail->genFile($path."thumbnail/thumbnail.png");
		var_dump($a);
	}
}
?>
