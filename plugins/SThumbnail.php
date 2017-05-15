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
if (!function_exists('imageflip')) {
	define('IMG_FLIP_HORIZONTAL', 0);
	define('IMG_FLIP_VERTICAL', 1);
	define('IMG_FLIP_BOTH', 2);

	function imageflip($image, $mode) {
		switch ($mode) {
			case IMG_FLIP_HORIZONTAL: {
				$max_x = imagesx($image) - 1;
				$half_x = $max_x / 2;
				$sy = imagesy($image);
				$temp_image = imageistruecolor($image)? imagecreatetruecolor(1, $sy): imagecreate(1, $sy);
				for ($x = 0; $x < $half_x; ++$x) {
					imagecopy($temp_image, $image, 0, 0, $x, 0, 1, $sy);
					imagecopy($image, $image, $x, 0, $max_x - $x, 0, 1, $sy);
					imagecopy($image, $temp_image, $max_x - $x, 0, 0, 0, 1, $sy);
				}
				break;
			}
			case IMG_FLIP_VERTICAL: {
				$sx = imagesx($image);
				$max_y = imagesy($image) - 1;
				$half_y = $max_y / 2;
				$temp_image = imageistruecolor($image)? imagecreatetruecolor($sx, 1): imagecreate($sx, 1);
				for ($y = 0; $y < $half_y; ++$y) {
					imagecopy($temp_image, $image, 0, 0, 0, $y, $sx, 1);
					imagecopy($image, $image, 0, $y, 0, $max_y - $y, $sx, 1);
					imagecopy($image, $temp_image, 0, $max_y - $y, 0, 0, $sx, 1);
				}
				break;
			}
			case IMG_FLIP_BOTH: {
				$sx = imagesx($image);
				$sy = imagesy($image);
				$temp_image = imagerotate($image, 180, 0);
				imagecopy($image, $temp_image, 0, 0, 0, 0, $sx, $sy);
				break;
			}
			default: {
				return;
			}
		}
		imagedestroy($temp_image);
	}
}
class SThumbnail extends SlightPHP\WMThumbnail{
	/**
	 * spanned file
	 * 
	 * @param $fileName
	 */
	var $image_type = 3;
	public function genFile($fileName,$quality = 100,$orientation= 0) {
        $image = parent::returnThumbnail();
		if($orientation>=1 && $orientation<=8){
			switch($orientation) {
			case 1:
				break;
			case 2:
				imageflip($image , 1);
				break;
			case 3:
				$image = imagerotate($image,180,0);
				break;
			case 4:
				imageflip($image , 2);
				break;
			case 5:
				imageflip($image , 2);
				$image = imagerotate($image,-90,0);
				break;
			case 6:
				$image = imagerotate($image,-90,0);
				break;
			case 7:
				imageflip($image , 1);
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
