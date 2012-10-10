laravel-imwg
============

Image Manipulation with GD - a Laravel Bundle

# Install #

	git clone https://github.com/Sentences/laravel-imwg.git imwg
Or just download your copy from github and locate it in bundles folder.

Then you need to auto-load the bundle, and if you like to use the route based image manipulation, tell laravel to handle that route in bundles.php:

	return array(
		'imwg' => array(
        	'auto'      => true,
        	'handles'   => 'imwg'
    	)
	);
## Usage ##
Then you can easily start to manipulate images in your code.

	$img = Imwg::open(path('public').'img/tests/yosemite.jpg')
	->resize(150)->polaroid()
	->ttftext('My polaroid title')
	->save(path('public').'img/tests/yosemite-polaroid.jpg');
In this cas, $img is an array which holds some information about the createt image.

	Array
	(
    	[width] => 170
    	[height] => 173
    	[type] => JPG
    	[attr] => width="170" height="173"
    	[bits] => 8
    	[channels] => 3
    	[mime] => image/jpeg
	)
## Working with route based image manipulation ##
Edit `config/settings.php` to fit your needs.

	return array (
    /**
     * Path, where the images can be found if Imwg routing is used
     * Example: You call the route like 
     * example.com/imwg/my_options/cute_images/image.jpg
     * Your image_path ist set to path('public').'img/'
     * then the full path to the image is somtehing like:
     * /var/www/html_public/laravel/public/img/cute_images/image.jpg
     */
    'image_path' => path('public').'img/',
    /**
     * Default font, used for Imwg::ttftext()
     * Can be changend in runtime
     */
    'ttf_font' => path('public').'fonts/TalkingToTheMoon.ttf',
    /**
     * Should images, modified via route, been cached?
     */
    'use_cache' => true,
    /**
     * Path, where the images should be cached 
	 * Here we need read/write access
     */
    'cache_path' => path('storage').'cache/',
    /**
     * Default cache lifetime in seconds
     * 1 hour = 3600
     * 1 day  = 86400
     * 1 week = 604800
     * 1 month = 2419200
     */
    'cache_lifetime' => 2419200,
	);
Create an options file in `config/route_options/`

Example file `polaroid.php`:
	
	<?php
	/**
 	* Every option, that should be parsed by Imwg needs an array as value 
 	*/
	return array(
		// Create a square from the full image
    	'square' => array(),
		// add a watermark in the top left corner
    	'watermark' => array(path('public').'img/tests/bad.png',Imwg::NW),
		// create the polaroid
    	'polaroid' => array(),
		// write some text inside the white polaroid field
    	'ttftext' => array('Just a test',null,14,null,null,null,array(255,0,0),true),
		// disable caching for testing purpose
    	'use_cache' => false,
	);
Now every image, called via the route will be polaroided ^-^
http://example.com/imwg/polaroid/myimage.jpg

myimage.jpg must be located in `'image_path'` previous defined in `config/settings.php`

## List of all possible methods and their defaults ##

**resize**

	/**
	* Increase or decrease an image.
   	* if only 1 parameter is given, the image will be reiszed proportional, 
   	* otherwise not.
   	* @param int width the new width
   	* @param int height the new height
   	* @param bool min if true, the shortest edge length will be $width
   	* @return \ImageManipulationWithGd\Imwg
   	*/
  	resize($width = null, $height = null, $min = false)

**resize_min**

	/**
   	* Increase or decrease an image.
   	* Shortest edge is $length
   	* Shorthand for resize($length, null, true);
   	* @param int lenght longest edge for the image
   	* @return \ImageManipulationWithGd\Imwg
   	* @see \ImageManipulationWithGd\Imwg::resize()
   	*/
  	resize_min($length = null)

**resize_max**

	/**
   	* Increase or decrease an image.
   	* Longest edge is $length
   	* Shorthand for resize($length, null, false);
   	* @param int lenght shortest edge for the image
   	* @return \ImageManipulationWithGd\Imwg
   	* @see \ImageManipulationWithGd\Imwg::resize()
   	*/
  	resize_max($length = null)

**cut**

	/**
   	* Cuts out a region from the image
   	* X is the result
   	*         $posX
   	*       +---+--------------+
   	*       |   |              |
   	* $posY +---+---+          |
   	*       |   | X | $height  |
   	*       |   +---+          |
   	*       |    $width        |
   	*       |                  |
   	*       +------------------+
   	* @param int $width Width of the cut out image
   	* @param int $height Height of the cut out image
   	* @param int $posX Point X where we start cutting from top
   	* @param int $posY Point Y where we start cutting from left
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
	cut($width = null, $height = null, $posX = 0, $posY = 0)

**cutout**

	/**
   	* Cuts rectangles out of the original
   	* @param int $with the width of the rectangle
   	* @param int $height the height of the rectangle
   	* @param int $pos the cutting position
   	* @return \ImageManipulationWithGd\Imwg
   	*/
  	cutout($width, $height, $pos = Imwg::M)

**square**

	/**
   	* Cuts out an square
   	* If size is not given, the result can be an rectangle, because it cuts the
   	* image from a 3x3 field
   	* @param int $size the length of edges. If you cut from the fullimage 
   	* ( F, FN, FE, FW, FS ), the result image will be resized to $size
   	* @param int $pos the postion where the image will be cuttet. Can be one of
   	* Const     Name    Integer Value
   	* Imwg::N = North, (int 2)
   	* Imwg::E = East, (int 6)
   	* Imwg::S = South, (int 8)
   	* Imwg::W = West, (int 4)
   	* Imwg::NW = Nortwest, (int 1)
   	* Imwg::NE = Northeast, (int 3)
   	* Imwg::SE = Southeast, (int 9)
   	* Imwg::SW = Southwest, (int 7)
   	* Imwg::M = Middle, (int 5)
   	* Imwg::FN = Fullimage North, (int 10)
   	* Imwg::FS = Fullimage South, (int 12)
   	* Imwg::F = Fullimage, (int 11)
   	* Imwg::FE = Fullimage East, (int 12)
   	* Imwg::FW = Fullimage West, (int 10)
   	* +----+---+----+
   	* | NW | N | NE |
   	* +----+---+----+
   	* | W  | M | E  |    <-- The Image
   	* +----+---+----+
   	* | SW | S | SE |
   	* +----+---+----+
   	* FN,FW and FE,FS has internal the same int value. so they return the 
   	* same result
   	* +--------+---+  +--+------+--+  +---+-------+ 
   	* |        |   |  |  |      |  |  |   |       |
   	* |   FW   |   |  |  |   F  |  |  |   |  FE   |  <-- The Image
   	* |        |   |  |  |      |  |  |   |       |
   	* +--------+---+  +--+------+--+  +---+-------+ 
   	* 
   	* +--------+  +--------+  +--------+
   	* |        |  |        |  |        |
   	* |   FN   |  +--------+  |        |
   	* |        |  |   F    |  +--------+   <-- The Image
   	* +--------+  |        |  |        |
   	* |        |  +--------+  |   FS   |
   	* |        |  |        |  |        |
   	* +--------+  +--------+  +--------+
   	* @return \ImageManipulationWithGd\Imwg
   	*/
  	square($size = null, $pos = Imwg::F)

**set_quality**

	/**
    * Sets the Quality of the new image for JPG and PNG
    * JPG between 0 (worst) and 100 (best)
    * PNG between 0 (best) and 9 (worst)
    * @param int $quality
    * @return \ImageManipulationWithGd\Imwg 
    */
  	set_quality($quality = 92)

**polaroid**

	/**
   	* Creates an image, that looks like a polaroid with a white border
   	* @param int $size the size of the result image
   	* @param int $borderTopLeftRight the top, left and right border
   	* @param int $borderBottom the bottom border
   	* @param array $polaroidColor backgroundcolor default white array(255,255,255)
   	* @param array $bordercolor bordercolor default black array(0,0,0)
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	polaroid($size = null, $borderTopLeftRight = 10, $borderBottom = 50, $polaroidColor = array(255, 255, 255), $bordercolor = array(0, 0, 0))

**text**

	/**
   	* Writes text into the image
   	* Uses GD internal fonts
   	* You should, if possible, use Imwg::ttftext() because GD text is ugly :)
   	* @param string $message The message to display. 
   	* Newline (\n) makes... tadaa a newline :)
   	* @param int $fontType The internal GD font. Integer between 1 and 5
   	* @param int $posX X-Position where the text starts
   	* @param int $posY Y-Position where the text starts
   	* @param array $color Textcolor, default black array(0, 0, 0)
   	* @param array $shadowColor Textshadowcolor, default array(185, 211, 238)
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	text($message = null, $fontType = 3, $posX = 0, $posY = 0, $color = array(0, 0, 0), $shadowColor = array(185, 211, 238))

**ttftext**

	/**
   	* Writes text into the image with a TTF Font
   	* @param string $message The Message to display.
   	* Newline (\n) makes... tadaa a newline :) hopefully ^_^
   	* @param string $fontFile Path to your favorite TTF-Fontfile (path/to/example.ttf)
   	* @param int $fontSize The fontsize in... hmm px? i have no idea
   	* @param int $rotate Rotates the $essage in $rotate degrees 
   	* @param int $posX X-Position of the $message
   	* @param int $posY Y-Position of the $message
   	* @param array $color Text color, default black array(0,0,0)
   	* @param bool $center true centers the message horizontal
   	* @param bool $middle true centers the message vertical
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	ttftext($message = null, $fontFile = null, $fontSize = 12, $rotate = 0, $posX = 0, $posY = 0, $color = array(0, 0, 0), $center = false, $middle = false)

**watermark**

	/**
   	* Copys an image to another, also known as watermark.
   	* The watermark image should be an png with alpha channel, otherwise
   	* you dont see anything from the original behind the watermark
   	* The watermark will be resized to fit the position
   	* @param string $imageWatermark Path to the watermark image
   	* @param int $pos Position of the watermark image. Can be one of
	* For positions, see Imgw::square()
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	watermark($imageWatermark, $pos = Imwg::F)

**rotate**

	/**
   	* Rotates the image
   	* @param int $degrees Rotation in degrees 0 - 360, default 180
   	* @param array $bgcolor Specifies the color of the uncovered zone after the rotation
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	rotate($degrees = 180, $bgcolor = -1)

**reflection**

	/**
   	* Creates a reflection under the image
   	* @param int $reflectionSize The size of the reflection
   	* @param array $bgcolor Color of the reflection background 
   	* default white array(255, 255, 255)
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	reflection($reflectionSize = 30, $bgcolor = array(255, 255, 255))

**mirror**

	/**
   	* This, of course, mirrors the image
   	* @param string $type h, v, b or	HORIZONTAL, VERTICAL, BOTH
   	* @return \ImageManipulationWithGd\Imwg
   	*/
  	mirror($type = 'BOTH')

**filter**

	/**
   	* Applies a filter to an image
   	* @param int $filter For available filters and their params take a look in
   	* the php documentation for imagefilter.
   	* Default filter is some kind of SEPIA
   	* @param int $arg1
   	* @param int $arg2
   	* @param int $arg3
   	* @param int $alpha
   	* @see http://www.php.net/manual/en/function.imagefilter.php
   	* @see http://www.phpied.com/image-fun-with-php-part-2/
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	filter($filter = 999, $arg1 = 0, $arg2 = 0, $arg3 = 0, $alpha = 0)

**convert**

	/**
   	* Converts an image to another type
   	* @param string $filetype JPG, PNG, GIF
   	* @param array $bgcolor for converting PNG whith transparency to JPG
   	* @return \ImageManipulationWithGd\Imwg 
   	*/
  	convert($filetype = null, $bgcolor = array(255, 255, 255))

**image_info**

	/**
   	* Gives information about the image
   	* static call, so you can use it without loading an image to Imwg
   	* $info = Imwg::getImageInfo('path/to/image.jpg');
   	* @param string $file Path to the Image
   	* @return array An array with: width,height,type,attr,bits,channels,mime
   	*/
  	image_info($file)

**save**

	/**
   	* Saves an Image
   	* @param string $file_name The new Filename
   	* @param bool $override True to override existing files
   	* @param bool $destroy True to destroy the Imwg instance
   	* @return array with infos about the image. See Imwg::image_info()
   	*/
  	save($file_name, $override = true, $destroy = true)

**display**

	/**
   	* Displays the image
   	* @param bool $header true sends the imageheader
   	*/
  	display($header = true)