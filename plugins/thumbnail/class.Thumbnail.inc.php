<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
//+----------------------------------------------------------------------+
//| WAMP (XP-SP2/2.2/5.2/5.1.0)                                          |
//+----------------------------------------------------------------------+
//| Copyright(c) 2001-2008 Michael Wimmer                                |
//+----------------------------------------------------------------------+
//| Licence: GNU General Public License v3                               |
//+----------------------------------------------------------------------+
//| Authors: Michael Wimmer <flaimo@gmail.com>                           |
//+----------------------------------------------------------------------+
//
// $Id$

/**
* @package Thumbnail
*/
/**
* Creates a thumbnail from a source image with GD2 functions
*
* Tested with Apache 2.2 and PHP 5.2.
* Last change: 2008-04-06
*
* @access public
* @author Michael Wimmer <flaimo@gmail.com>
* @copyright Michael Wimmer
* @link http://code.google.com/p/flaimo-php
* @package Thumbnail
* @example sample_thumb.php Sample script
* @version 2.001
*/
class Thumbnail {

	/*-------------------*/
	/* V A R I A B L E S */
	/*-------------------*/

	/**
	* possible image formats
	*
	* @var array
	*/
	protected $formats = array('gif' => 1, 'jpg' => 2, 'png' => 3, 'wbmp' => 15,
						 'string' => 999);

	/**
	* maximal height of the generated thumbnail
	*
	* @var int
	*/
	protected $thumb_max_height = 100;

	/**
	* maximal width of the generated thumbnail
	*
	* @var int
	*/
	protected $thumb_max_width = 100;

	/**
	* quality or speed when generating the thumbnail
	*
	* @var boolean
	*/
	protected $quality_thumb = TRUE;

	/**
	* path/filename of imagefile
	*
	* @var string
	*/
	protected $image_path;

	/**
	* @var int
	*/
	protected $image_width;

	/**
	* @var int
	*/
	protected $image_height;

	/**
	* image format of source
	*
	* @var int
	*/
	protected $image_type;

	/**
	* @var int
	*/
	protected $thumbnail_height;

	/**
	* @var int
	*/
	protected $thumbnail_width;

	/**
	* image format of the thumbnail
	*
	* @var int
	*/
	protected $thumbnail_type = 3;

	/**
	* @var resource
	*/
	protected $image;

	/**
	* @var resource
	*/
	protected $thumbnail;

	const VERSION = '2.001';


	/*-----------------------*/
	/* C O N S T R U C T O R */
	/*-----------------------*/

	/**
	* Constructor
	*
	* @param string $file  path/filename of picture or stream from a DB field
	* @return void
	* @uses $image_path
	*/
	function __construct($file = '') {
		$this->image_path = (string) $file;
	} // end constructor

	/*-------------------*/
	/* F U N C T I O N S */
	/*-------------------*/

	/**
	* sets the output type of the thumbnail
	*
	* @param string $format gif, jpg, png, wbmp
	* @return void
	* @uses $thumbnail_type
	* @uses $formats
	*/
	public function setOutputFormat($format = 'png') {
		if (array_key_exists(trim($format), $this->formats)) {
			$this->thumbnail_type = $this->formats[trim($format)];
		} // end if
	} // end function

	/**
	* sets the max. height of the thumbnail
	*
	* @param int $height
	* @return boolean
	* @uses readSourceImageData()
	* @uses $image_height
	* @uses $thumb_max_height
	*/
	public function setMaxHeight($height = 0) {
		$this->readSourceImageData();
		$height = (int) $height;
		if ($height < $this->image_height && $height > 0) {
			$this->thumb_max_height = $height;
			return (boolean) TRUE;
		} // end if
		return (boolean) FALSE;
	} // end function

	/**
	* sets the max. width of the thumbnail
	*
	* @param int $width
	* @return boolean
	* @uses readSourceImageData()
	* @uses $image_width
	* @uses $thumb_max_width
	*/
	public function setMaxWidth($width = 0) {
		$this->readSourceImageData();
		$width = (int) $width;
		if ($width < $this->image_width && $width > 0) {
			$this->thumb_max_width = $width;
			return (boolean) TRUE;
		} // end if
		return (boolean) FALSE;
	} // end function

	/**
	* sets the max. width and height of the thumbnail
	*
	* passes values to the functions setMaxHeight() and setMaxWidth()
	*
	* @param int $width
	* @param int $height
	* @return boolean
	* @uses setMaxHeight()
	* @uses setMaxWidth()
	*/
	public function setMaxSize($width = 0, $height = 0) {
		if ($this->setMaxWidth($width) === TRUE
			&& $this->setMaxHeight($height) === TRUE) {
			return (boolean) TRUE;
		} else {
			return (boolean) FALSE;
		} // end if
	} // end function

	/**
	* whether to create thumbs fast or with good quality
	*
	* @param boolean $boolean
	* @return void
	* @uses $quality_thumb
	*/
	public function setQualityOutput($boolean = TRUE) {
		$this->quality_thumb = (boolean) $boolean;
	} // end function

	/**
	* reads metadata of the source image
	*
	* @return void
	* @uses $image_width
	* @uses $image_height
	* @uses $image_type
	* @uses $formats
	*/
	protected function readSourceImageData() {
		if (!file_exists($this->image_path)) { // if source pic wasnt found
			$this->image_path = 'error_pic';
			$this->image_width =& $this->thumb_max_width;
			$this->image_height =& $this->thumb_max_height;
			$this->image = @imagecreatetruecolor($this->image_width, $this->image_height)
							or die ('Cannot Initialize new GD image stream');
			$text_color = imagecolorallocate($this->image, 255, 255, 255);
			imagestring($this->image, 1, 2,
					    ($this->image_height / 2 - 10),
					    "Could't find", $text_color);
			imagestring($this->image, 1, 2,
					    ($this->image_height / 2 - 4),
					    'source image', $text_color);
			imagestring($this->image, 1, 2,
						($this->image_height / 2 + 4),
						'(Thumbnail V' . self::VERSION . ')', $text_color);
		} else {
			if (!isset($this->image_width)) {
				list($this->image_width, $this->image_height, $this->image_type, $attr) = getimagesize($this->image_path);
				unset($attr);
				if (!in_array($this->image_type, $this->formats)) {
					die("Can't create thumbnail from '" . $this->image_type . "' source: " . $this->image_path);
				} // end if
			} // end if
		} // end if
	} // end function

	/**
	* reads the source image into a variable
	*
	* @return void
	* @uses $image
	* @uses readSourceImageData()
	* @uses $image_type
	* @uses $image_path
	*/
	protected function readSourceImage() {
		if (!isset($this->image)) {
		    $this->readSourceImageData();
		    switch ($this->image_type) {
		        case 1:
		            $this->image = imagecreatefromgif($this->image_path);
		            break;
		        case 2:
		            $this->image = imagecreatefromjpeg($this->image_path);
		            break;
		        case 3:
		            $this->image = imagecreatefrompng($this->image_path);
		            break;
		        case 15:
		            $this->image = imagecreatefromwbmp($this->image_path);
		            break;
		        case 999:
		        default:
					$this->image = imagecreatefromstring($this->image_path);
					break;
		    } // end switch
		} // end if
	} // end function

	/**
	* sets the actual width and height of the thumbnail based on the source image size and the max limits for the thumbnail
	*
	* @return void
	* @uses readSourceImageData()
	* @uses $image_height
	* @uses $thumb_max_height
	* @uses $image_width
	* @uses $thumb_max_width
	*/
	protected function setThumbnailSize() {
	    $this->readSourceImageData();
	    if (($this->image_height > $this->thumb_max_height)
	    	|| ($this->image_width > $this->thumb_max_width)) {
	        $sizefactor = (double) (($this->image_height > $this->image_width) ? ($this->thumb_max_height / $this->image_height) : ($this->thumb_max_width / $this->image_width));
	    } else {
	        $sizefactor = (int) 1;
	    } // end if
	    $this->thumbnail_width = (int) ($this->image_width * $sizefactor);
	    $this->thumbnail_height = (int) ($this->image_height * $sizefactor);

	    $sizefactor = (int) 1;
	    if ($this->image_height > $this->image_width) {
	    	if ($this->thumbnail_width > $this->thumb_max_width) {
	    		$sizefactor = (double) ($this->thumb_max_width / $this->thumbnail_width);
	    	} // end if
		} else {
	    	if ($this->thumbnail_height > $this->thumb_max_height) {
	    		$sizefactor = (double) ($this->thumb_max_height / $this->thumbnail_height);
	    	} // end if
		} // end if // end if
	    $this->thumbnail_width = (int) ($this->thumbnail_width * $sizefactor);
	    $this->thumbnail_height = (int) ($this->thumbnail_height * $sizefactor);
	    unset($sizefactor);
	} // end function

	/**
	* creates the thumbnail and saves it to a variable
	*
	* @return void
	* @uses setThumbnailSize()
	* @uses readSourceImage()
	* @uses $thumbnail
	* @uses $thumbnail_width
	* @uses $thumbnail_height
	* @uses $quality_thumb
	* @uses $image
	* @uses $image_width
	* @uses $image_height
	*/
	protected function createThumbnail() {
		$this->setThumbnailSize();
		$this->readSourceImage();

		if (!isset($this->thumbnail)) {
			$this->thumbnail = imagecreatetruecolor($this->thumbnail_width,
													$this->thumbnail_height);

			if ($this->quality_thumb === TRUE) {
				imagecopyresampled($this->thumbnail, $this->image, 0, 0, 0, 0,
								   $this->thumbnail_width, $this->thumbnail_height,
								   $this->image_width, $this->image_height);
			} else {
				imagecopyresized($this->thumbnail, $this->image, 0, 0, 0, 0,
								 $this->thumbnail_width, $this->thumbnail_height,
								 $this->image_width, $this->image_height);
			} // end if
		} // end if
	} // end function

	/**
	* outputs the thumbnail to the browser
	*
	* @param string $format
	* @param int $quality
	* @return void
	* @uses setOutputFormat()
	* @uses createThumbnail()
	* @uses $thumbnail_type
	* @uses $thumbnail
	*/
	public function outputThumbnail($format = 'png', $quality = 75) {
	    $this->setOutputFormat($format);
	    $this->createThumbnail();
	    switch ($this->thumbnail_type) {
	        case 1:
	        	header('Content-type: image/gif');
	        	imagegif($this->thumbnail);
	            break;
	        case 2:
	        	$quality = (int) $quality;
	        	if ($quality < 0 || $quality > 100) {
					$quality = 75;
	        	} // end if

	        	header('Content-type: image/jpeg');
	        	imagejpeg($this->thumbnail, '', $quality);
	            break;
	        case 3:
	        	header('Content-type: image/png');
	            imagepng($this->thumbnail);
	            break;
	        case 15:
	            header('Content-type: image/vnd.wap.wbmp');
	            imagewbmp($this->thumbnail);
	            break;
	    } // end switch
	    imagedestroy($this->thumbnail);
	    imagedestroy($this->image);
	} // end function

	/**
	* returns the variable with the thumbnail image
	*
	* @return mixed
	* @uses setOutputFormat()
	* @uses createThumbnail()
	* @uses $thumbnail
	*/
	public function returnThumbnail() {
		$this->setOutputFormat();
		$this->createThumbnail();
		return $this->thumbnail;
	} // end function

	/**
	* returns the height of the thumbnail
	*
	* @return int
	* @uses $thumbnail_height
	*/
	public function getThumbHeight() {
		$this->createThumbnail();
		return (int) $this->thumbnail_height;
	} // end function

	/**
	* returns the width of the thumbnail
	*
	* @return int
	* @uses $thumbnail_width
	*/
	public function getThumbWidth() {
		$this->createThumbnail();
		return (int) $this->thumbnail_width;
	} // end function

	/**
	* returns the width of the thumbnail
	*
	* @return int
	* @uses $thumbnail_width
	*/
	public function getPictureName() {
		return (string) $this->image_path;
	} // end function
} // end class Thumbnail
?>