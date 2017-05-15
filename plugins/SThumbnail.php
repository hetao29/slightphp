<?php
/*{{{LICENSE
+-----------------------------------------------------------------------+
| SlightPHP Framework                                                   |
+-----------------------------------------------------------------------+
| This program is free software; you can redistribute it and/or modify  |
| it under the terms of the GNU General Public License as published by  |
| the Free Software Foundation. You should have received a copy of the  |
| GNU General Public License along with this program.  If not, see      |
| http://www.gnu.org/licenses/.                                         |
| Copyright (C) 2008-2009. All Rights Reserved.                         |
+-----------------------------------------------------------------------+
| Supports: http://www.slightphp.com                                    |
+-----------------------------------------------------------------------+
}}}*/



require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."thumbnail/class.WMThumbnail.inc.php");
/**
 * @package SlightPHP
 */
class SThumbnail extends SlightPHP\WMThumbnail{
	/**
	 * spanned file
	 * 
	 * @param $fileName
	 */
	var $image_type = 3;
	public function imageflip(&$image,$mode) {
		if(function_exists("imageflip")){
			imageflip($image,$mode);
		}else{
			return $image;
		}
	}
	public function genFile($fileName,$quality = 100,$orientation= 0) {
        $image = parent::returnThumbnail();
		if($orientation>=1 && $orientation<=8){
			switch($orientation) {
			case 1:
				break;
			case 2:
				self::imageflip($image , 1);
				break;
			case 3:
				$image = imagerotate($image,180,0);
				break;
			case 4:
				self::imageflip($image , 2);
				break;
			case 5:
				self::imageflip($image , 2);
				$image = imagerotate($image,-90,0);
				break;
			case 6:
				$image = imagerotate($image,-90,0);
				break;
			case 7:
				self::imageflip($image , 1);
				$image = imagerotate($image,-90,0);
				break;
			case 8:
				$image = imagerotate($image,90,0);
				break;
			}
			imagealphablending($image, true); 
			imagesavealpha($image, true); 
		}
		$result = false;
	    if (!empty($image)){
		    switch ($this->image_type) {
		        case 1:
		        	$result = imagegif($image, $fileName);
		            break;
		        case 2:
		        	$quality = (int) $quality;
		        	if ($quality < 0 || $quality > 100) {
						$quality = 75;
		        	} // end if
					$result = imagejpeg($image, $fileName, $quality);
		            break;
		        case 3:
		            $result = imagepng($image, $fileName);
		            break;
		        case 15:
		        	$result = imagewbmp($image, $fileName);
		            break;
		    } // end switch
		}
		return $result;
	}
}
