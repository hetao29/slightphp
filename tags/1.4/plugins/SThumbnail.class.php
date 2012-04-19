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
class SThumbnail extends WMThumbnail{
	/**
	 * 生成文件
	 * 
	 * @param $fileName
	 */
	var $image_type = 3;
	public function genFile($fileName,$quality = 100) {
		$image = parent::returnThumbnail();
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
?>
