<?php
/**
 * SlightPHP
 * @copyright Copyright (c) 2026 SlightPHP
 * @license MIT
 */

/**
 * @package SlightPHP
 */

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."thumbnail/class.WMThumbnail.inc.php");
if (!function_exists('imageflip')) {
	define('IMG_FLIP_HORIZONTAL', 0);
	define('IMG_FLIP_VERTICAL', 1);
	define('IMG_FLIP_BOTH', 2);

	/**
	 * 在缺少原生 imageflip 时原地翻转 GD 图片。
	 * @param GdImage|resource $image 待修改的图片。
	 * @param int $mode IMG_FLIP_HORIZONTAL、IMG_FLIP_VERTICAL 或 IMG_FLIP_BOTH。
	 * @return void
	 */
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
/** 在缩略图生成器上提供按方向写入文件的能力。 */
class SThumbnail extends SlightPHP\WMThumbnail{
	/**
	 * @var int 输出图片类型：1 GIF、2 JPEG、3 PNG、15 WBMP。
	 */
	var $image_type = 3;
	/**
	 * 生成缩略图并写入文件。
	 * @param string $fileName 输出文件路径。
	 * @param int $quality JPEG 质量，超出 0–100 时使用 75。
	 * @param int $orientation 方向值 1–8；其他值不变换。
	 * @return bool 写入是否成功。
	 */
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
