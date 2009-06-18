<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Hetal <hetao@hetao.name>                                    |
  |          SlightPHP <admin@slightphp.com>                             |
  |          http://www.slightphp.com                                    |
  +----------------------------------------------------------------------+
*/



require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."thumbnail/class.WMThumbnail.inc.php");
class SThumbnail extends WMThumbnail{
	/**
	 * 生成文件
	 * 
	 * @param $fileName
	 */
	public function genFile($fileName,$quality = 100) {
		$image = parent::returnThumbnail();
		$result = false;
	    if (strlen(trim($image)) > 0) {
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