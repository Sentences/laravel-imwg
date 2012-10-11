<?php

namespace ImageManipulationWithGd;

use \Laravel\Config as Config,
    \Laravel\File as File,
    \Laravel\Response as Response,
    \Laravel\Error as Error;

/**
 * Imwg - Imagemanipulation with GD
 *
 * @package  Laravel-Imwg
 * @author   Nico R <lt500r@gmail.com>
 * @link     https://github.com/Sentences
 */
class Imwg
{

  /**
   * Holds the filters
   * config/filters.php
   * @var array 
   */
  private static $settings = array();

  /**
   * Holds the path to the current image
   * @var string 
   */
  private static $file = null;

  /**
   * The basename of the image
   * @var string 
   */
  private static $file_name;

  /**
   * Holds infos about the image
   * size, mime
   * @var array 
   */
  private static $file_info = array();

  /**
   * The image resource
   * @var resource 
   */
  private static $image_resource = null;

  /**
   * Quality of the new image for JPG and PNG
   * JPG between 0 (worst) and 100 (best)
   * PNG between 0 (best) and 9 (worst)
   * @var integer
   */
  private static $image_quality = 92;

  /**
   * Constants for cutting images 
   */

  CONST N = 2;
  CONST E = 6;
  CONST S = 8;
  CONST W = 4;
  CONST NE = 3;
  CONST SE = 9;
  CONST SW = 7;
  CONST NW = 1;
  CONST M = 5;
  CONST FN = 10;
  CONST FW = 10;
  CONST F = 11;
  CONST FE = 12;
  CONST FS = 12;

  /**
   * Holds the position, where text can be placed inside of a polaroid
   * pos x
   * @var int 
   */
  private static $text_pos_x = 0;

  /**
   * Holds the position, where text can be placed inside of a polaroid
   * pos y
   * @var int 
   */
  private static $text_pos_y = 0;

  /**
   * Instantiates Imwg and receives the filetype, width, height and attributes for
   * <img src='' ..attributes.. />
   * @param mixed $file string path or array from Input::file('form_field') 
   * can _NOT_ hold multiple images
   */
  public function __construct($file)
  {
    if (is_array($file))
    {
      $file = $file['tmp_name'];
    }
    static::$settings = Config::get('imwg::settings');
    static::$file = $file;
    static::$file_info = static::image_info($file);
    $this->create_image_resource();
  }

  /**
   * Static call
   * Returns a new Imwg object
   * @param  mixed $file string path or array from Input::file('form_field')
   * @return Imwg
   */
  public static function open($file)
  {
    return new Imwg($file);
  }

  /**
   * This creates the imageresource and sets the 
   * default quality for JPG and PNG
   * @throws type 
   */
  private function create_image_resource()
  {
    if (File::exists(static::$file))
    {
      switch (static::$file_info['type'])
      {
        case 'JPG':
          static::$image_resource = imagecreatefromjpeg(static::$file);
          static::$image_quality = 92;
          break;

        case 'GIF':
          static::$image_resource = imagecreatefromgif(static::$file);
          break;

        case 'PNG': //allow transparency for png
          static::$image_resource = imagecreatefrompng(static::$file);
          imagealphablending(static::$image_resource, false);
          imagesavealpha(static::$image_resource, true);
          static::$image_quality = 0;
          break;

        case 'BMP': //convert the bmp to jpg
          static::$image_resource = namespace\Bmp2jpg::make(static::$file);
          $this->convert("JPG");
          break;

        default:
          throw(new Exception('Wrong image-type ' . static::$file . '; Only JPG, GIF, PNG and some kind of BMP are supportet'));
      }

      static::$file_name = basename(static::$file);
    }
    else
    {
      throw(new Exception('File ' . static::$file . ' does not exist.'));
    }
  }

  /**
   * Increase or decrease an image.
   * if only 1 parameter is given, the image will be reiszed proportional, 
   * otherwise not.
   * @param int width the new width
   * @param int height the new height
   * @param bool min if true, the shortest edge length will be $width
   * @return \ImageManipulationWithGd\Imwg
   */
  public function resize($width = null, $height = null, $min = false)
  {
    if (static::$image_resource)
    {
      $holdProportions = true;
      if (is_null($width) && is_null($height))
      { // nothing to do, return
        return $this;
      }
      // Set the max-width/height if only 1 param is given
      if (!is_null($width) && is_null($height))
      {
        $height = $width;
      }
      elseif (is_null($width) && !is_null($height))
      {
        $width = $height;
      }
      else
      {
        $holdProportions = false;
        $newW = $width;
        $newH = $height;
      }
      if ($holdProportions)
      {
        // if $min is false, longest edge is $width
        // if $min is true, shortest edge is $width
        $ratioW = $width / static::$file_info['width'];
        $ratioH = $height / static::$file_info['height'];
        if ($min) // shortest edge is $width
        {
          if ($ratioW < $ratioH)
          {
            $newW = round(static::$file_info['width'] * $ratioH);
            $newH = round(static::$file_info['height'] * $ratioH);
          }
          else
          {
            $newW = round(static::$file_info['width'] * $ratioW);
            $newH = round(static::$file_info['height'] * $ratioW);
          }
        }
        else // longest edge is $width
        {
          if ($ratioW < $ratioH)
          {
            $newW = round(static::$file_info['width'] * $ratioW);
            $newH = round(static::$file_info['height'] * $ratioW);
          }
          else
          {
            $newW = round(static::$file_info['width'] * $ratioH);
            $newH = round(static::$file_info['height'] * $ratioH);
          }
        }
      }
      $tempImage = ImageCreateTruecolor($newW, $newH);
      // PNG needs some help, otherwise it loses the transparency
      // This works, maybe someone has a shorter version, so let me know
      if (static::$file_info['type'] == "PNG")
      {
        // make the tempImage transparent
        imagealphablending($tempImage, true);
        $transparent = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
        imagefill($tempImage, 0, 0, $transparent);
        ImageCopyResampled($tempImage, static::$image_resource, 0, 0, 0, 0, $newW, $newH, static::$file_info['width'], static::$file_info['height']);
        ImageDestroy(static::$image_resource);
        // new resource
        static::$image_resource = ImageCreateTruecolor($newW, $newH);
        imagealphablending(static::$image_resource, true);
        imagesavealpha(static::$image_resource, false);
        $transparent = imagecolorallocatealpha(static::$image_resource, 0, 0, 0, 127);
        imagefill(static::$image_resource, 0, 0, $transparent);
        imagecopy(static::$image_resource, $tempImage, 0, 0, 0, 0, $newW, $newH);
        // Alphablending for savealpha to false
        imagealphablending(static::$image_resource, false);
        // Alpha save
        imagesavealpha(static::$image_resource, true);
      }
      else
      {
        ImageCopyResampled($tempImage, static::$image_resource, 0, 0, 0, 0, $newW, $newH, static::$file_info['width'], static::$file_info['height']);
        ImageDestroy(static::$image_resource);
        // new resource
        static::$image_resource = ImageCreateTruecolor($newW, $newH);
        imagecopy(static::$image_resource, $tempImage, 0, 0, 0, 0, $newW, $newH);
      }
      ImageDestroy($tempImage);

      static::$file_info['width'] = $newW;
      static::$file_info['height'] = $newH;
      static::$file_info['attr'] = 'width="' . $newW . '" height="' . $newH . '"';
    }
    return $this;
  }

  /**
   * Increase or decrease an image.
   * Shortest edge is $length
   * Shorthand for resize($length, null, true);
   * @param int lenght longest edge for the image
   * @return \ImageManipulationWithGd\Imwg
   * @see \ImageManipulationWithGd\Imwg::resize()
   */
  public function resize_min($length = null)
  {
    $this->resize($length, null, true);
    return $this;
  }

  /**
   * Increase or decrease an image.
   * Longest edge is $length
   * Shorthand for resize($length, null, false);
   * @param int lenght shortest edge for the image
   * @return \ImageManipulationWithGd\Imwg
   * @see \ImageManipulationWithGd\Imwg::resize()
   */
  public function resize_max($length = null)
  {
    $this->resize($length, null, false);
    return $this;
  }

  /**
   * Cuts out a region from the image
   * X is the result
   * <code>
   *         $posX
   *       +---+--------------+
   *       |   |              |
   * $posY +---+---+          |
   *       |   | X | $height  |
   *       |   +---+          |
   *       |    $width        |
   *       |                  |
   *       +------------------+
   * </code>
   * @param int $width Width of the cut out image
   * @param int $height Height of the cut out image
   * @param int $posX Point X where we start cutting from top
   * @param int $posY Point Y where we start cutting from left
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function cut($width = null, $height = null, $posX = 0, $posY = 0)
  {
    if (static::$image_resource)
    {
      if (is_null($width) && is_null($height))
      {
        return $this;
      }
      // No width given, lets make a square
      elseif (!is_null($width) && is_null($height))
      {
        $height = $width;
      }

      $tempImage = ImageCreateTruecolor(static::$file_info['width'], static::$file_info['height']);
      if (static::$file_info['type'] == "PNG")
      { // PNG alpha help
        imagealphablending($tempImage, true);
        $transparent = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
        imagefill($tempImage, 0, 0, $transparent);
      }
      ImageCopy($tempImage, static::$image_resource, 0, 0, 0, 0, static::$file_info['width'], static::$file_info['height']);
      ImageDestroy(static::$image_resource);
      // new resource
      static::$image_resource = ImageCreateTruecolor($width, $height);
      if (static::$file_info['type'] == "PNG")
      { // PNG alpha help
        imagealphablending(static::$image_resource, false);
        $transparent = imagecolorallocatealpha(static::$image_resource, 0, 0, 0, 127);
        imagefill(static::$image_resource, 0, 0, $transparent);
      }
      imagecopy(static::$image_resource, $tempImage, 0, 0, $posX, $posY, $width, $height);
      if (static::$file_info['type'] == "PNG")
      {
        // alphabelinding false for savealpha
        imagealphablending(static::$image_resource, false);
        // save alpha
        imagesavealpha(static::$image_resource, true);
      }
      ImageDestroy($tempImage);
      static::$file_info['width'] = $width;
      static::$file_info['height'] = $height;
      static::$file_info['attr'] = 'width="' . $width . '" height="' . $height . '"';
    }
    return $this;
  }

  /**
   * Cuts rectangles out of the original
   * @param int $with the width of the rectangle
   * @param int $height the height of the rectangle
   * @param int $pos the cutting position
   * @return \ImageManipulationWithGd\Imwg
   */
  public function cutout($width, $height, $pos = Imwg::M)
  {
    if (static::$image_resource)
    {
      $cutX = 0;
      $cutY = 0;
      switch ($pos)
      {
        case static::NW:
        case static::FN:
        case static::FW:
          // default 0 0
          break;

        case static::N:
          // N
          $cutX = round((static::$file_info['width'] / 2) - ($width / 2));
          break;

        case static::NE:
          // NE
          $cutX = static::$file_info['width'] - $width;
          break;

        case static::W:
          // W
          $cutY = round((static::$file_info['height'] / 2) - ($height / 2));
          break;

        case static::M:
          // M
          $cutX = round((static::$file_info['width'] / 2) - ($width / 2));
          $cutY = round((static::$file_info['height'] / 2) - ($height / 2));
          break;

        case static::E:
          // E
          $cutX = static::$file_info['width'] - $width;
          $cutY = round((static::$file_info['height'] / 2) - ($height / 2));
          break;

        case static::SW:
          // SW
          $cutY = static::$file_info['height'] - $height;
          break;

        case static::S:
          // S
          $cutX = round((static::$file_info['width'] / 2) - ($width / 2));
          $cutY = static::$file_info['height'] - $height;
          break;

        case static::SE:
          // SE
          $cutX = static::$file_info['width'] - $width;
          $cutY = static::$file_info['height'] - $height;
          break;

        case static::F:
          // F
          $cutX = round((static::$file_info['width'] / 2) - ($width / 2));
          $cutY = round((static::$file_info['height'] / 2) - ($height / 2));
          break;

        case static::FE:
        case static::FS:
          // FE FS
          $cutX = static::$file_info['width'] - $width;
          $cutY = static::$file_info['height'] - $height;
          break;
      }
      $this->cut($width, $height, $cutX, $cutY);
    }
    return $this;
  }

  /**
   * Cuts out an square
   * If size is not given, the result can be an rectangle, because it cuts the
   * image from a 3x3 field
   * @param int $size the length of edges. If you cut from the fullimage 
   * ( F, FN, FE, FW, FS ), the result image will be resized to $size
   * @param int $pos the postion where the image will be cuttet. Can be one of
   * <code>
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
   * +--------+---+  +--+------+--+ +---+-------+ 
   * |        |   |  |  |      |  | |   |       |
   * |   FW   |   |  |  |   F  |  | |   |  FE   |  <-- The Image
   * |        |   |  |  |      |  | |   |       |
   * +--------+---+  +--+------+--+ +---+-------+ 
   * 
   * +--------+  +--------+  +--------+
   * |        |  |        |  |        |
   * |   FN   |  +--------+  |        |
   * |        |  |   F    |  +--------+   <-- The Image
   * +--------+  |        |  |        |
   * |        |  +--------+  |   FS   |
   * |        |  |        |  |        |
   * +--------+  +--------+  +--------+
   * </code>
   * @return \ImageManipulationWithGd\Imwg
   */
  public function square($size = null, $pos = Imwg::F)
  {
    if (static::$image_resource)
    {
      $resizeAfterCut = false;
      // If $pos >= 10, cut from fullimage
      if ($pos >= 10)
      {
        if (static::$file_info['width'] > static::$file_info['height'])
        {
          $width = static::$file_info['height'];
          $height = static::$file_info['height'];
        }
        elseif (static::$file_info['width'] < static::$file_info['height'])
        {
          $width = static::$file_info['width'];
          $height = static::$file_info['width'];
        }
        else
        {
          $width = static::$file_info['width'];
          $height = static::$file_info['height'];
        }
        if (!is_null($size))
        {
          $resizeAfterCut = true;
        }
      }
      elseif ($pos < 10 && is_null($size))
      { // No size, cut the image from 3x3 fields
        $width = round(static::$file_info['width'] / 3);
        $height = round(static::$file_info['height'] / 3);
      }
      else
      {
        $width = $size;
        $height = $size;
      }
      // Cutting
      $this->cutout($width, $height, $pos);
      if ($resizeAfterCut)
      { // resizing
        $this->resize($size);
      }
    }
    return $this;
  }

  /**
   * Sets the Quality of the new image for JPG and PNG
   * JPG between 0 (worst) and 100 (best)
   * PNG between 0 (best) and 9 (worst)
   * @param int $quality
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function set_quality($quality = 92)
  {
    // JPEG must be between 0 and 100
    if (static::$file_info['type'] == "JPG")
    {
      static::$image_quality = (!is_null($quality) && $quality >= 0 && $quality <= 100) ? $quality : 92;
    }
    // PNG must be between 0 and 9
    elseif (static::$file_info['type'] == "PNG")
    {
      static::$image_quality = (!is_null($quality) && $quality >= 0 && $quality <= 9 ) ? $quality : 0;
    }
    return $this;
  }

  /**
   * Creates an image, that looks like a polaroid with a white border
   * @param int $size the size of the result image
   * @param int $borderTopLeftRight the top, left and right border
   * @param int $borderBottom the bottom border
   * @param array $polaroidColor backgroundcolor default white array(255,255,255)
   * @param array $bordercolor bordercolor default black array(0,0,0)
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function polaroid($size = null, $borderTopLeftRight = 10, $borderBottom = 50, $polaroidColor = array(255, 255, 255), $bordercolor = array(0, 0, 0))
  {
    if (static::$image_resource)
    {
      if (!is_null($size))
      { // if no new size is given, we increase the size of the original to fit
        $abzug = (static::$file_info['width'] > static::$file_info['height']) ? ($borderTopLeftRight * 2) : ($borderTopLeftRight + $borderBottom);
        $this->resize($size - $abzug);
      }
      $polaroidWidth = static::$file_info['width'] + ($borderTopLeftRight * 2);
      $polaroidHeight = static::$file_info['height'] + $borderTopLeftRight + $borderBottom;
      // pos for the image inside the polaroid
      $imagePosX = ($polaroidWidth - static::$file_info['width']) / 2;
      $imagePosY = ($polaroidWidth - static::$file_info['width']) / 2;
      // temp image
      $tempImage = ImageCreateTruecolor($polaroidWidth, $polaroidHeight);
      $background_color = imagecolorallocate($tempImage, $polaroidColor[0], $polaroidColor[1], $polaroidColor[2]);
      imagefill($tempImage, 0, 0, $background_color);
      // image border
      // 1px with $polaroidColor as background
      $frameColor = imagecolorallocate($tempImage, $bordercolor[0], $bordercolor[1], $bordercolor[2]);
      imagefilledrectangle($tempImage, $imagePosX - 1, $imagePosY - 1, $imagePosX + static::$file_info['width'], $imagePosY + static::$file_info['height'], $frameColor);
      imagefilledrectangle($tempImage, $imagePosX, $imagePosY, $imagePosX + static::$file_info['width'] - 1, $imagePosY + static::$file_info['height'] - 1, $background_color);
      // copy our image inside the polaroid
      imagecopy($tempImage, static::$image_resource, $imagePosX, $imagePosY, 0, 0, static::$file_info['width'], static::$file_info['height']);

      ImageDestroy(static::$image_resource);
      // new resource
      static::$image_resource = ImageCreateTruecolor($polaroidWidth, $polaroidHeight);
      if (static::$file_info['type'] == "PNG")
      { // we dont want to lose transpancy, this gives cool effects with colored polaroids :)
        imagealphablending(static::$image_resource, true);
        imagesavealpha(static::$image_resource, false);
        $transparent = imagecolorallocatealpha(static::$image_resource, 0, 0, 0, 127);
        imagefill(static::$image_resource, 0, 0, $transparent);
      }

      imagecopy(static::$image_resource, $tempImage, 0, 0, 0, 0, $polaroidWidth, $polaroidHeight);
      ImageDestroy($tempImage);
      // hold the position from the "white" field, to maybe write text into it.
      static::$text_pos_x = $imagePosX;
      static::$text_pos_y = $imagePosY + static::$file_info['height'] + 2;
      static::$file_info['width'] = $polaroidWidth;
      static::$file_info['height'] = $polaroidHeight;
      static::$file_info['attr'] = 'width="' . $polaroidWidth . '" height="' . $polaroidHeight . '"';
    }
    return $this;
  }

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
  public function text($message = null, $fontType = 3, $posX = 0, $posY = 0, $color = array(0, 0, 0), $shadowColor = array(185, 211, 238))
  {
    if (($posX == 0 && $posY == 0) && (static::$text_pos_x > 0 || static::$text_pos_y > 0))
    {
      $posX = static::$text_pos_x;
      $posY = static::$text_pos_y;
    }

    if (static::$image_resource && !is_null($message))
    {
      $color = imagecolorallocate(static::$image_resource, $color[0], $color[1], $color[2]);
      (!is_bool($shadowColor)) ? $shadow = imagecolorallocate(static::$image_resource, $shadowColor[0], $shadowColor[1], $shadowColor[2]) : "";
      $messageArray = explode("\n", $message);
      $y = 0;
      for ($i = 0; $i < count($messageArray); $i++)
      {
        imagestring(static::$image_resource, $fontType, $posX, $posY + $y, $messageArray[$i], $color);
        (!is_bool($shadowColor)) ? imagestring(static::$image_resource, $fontType, $posX + 1, $posY + 1 + $y, $messageArray[$i], $shadow) : "";
        $y += imagefontheight($fontType);
      }
    }
    return $this;
  }

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
  public function ttftext($message = null, $fontFile = null, $fontSize = 12, $rotate = 0, $posX = 0, $posY = 0, $color = array(0, 0, 0), $center = false, $middle = false)
  {
    if ($posX == 0)
      $posX = static::$text_pos_x;

    if ($posY == 0)
      $posY = static::$text_pos_y;

    if (is_null($fontFile) && array_key_exists('ttf_font', static::$settings))
      $fontFile = static::$settings['ttf_font'];

    if (static::$image_resource && !is_null($message) && File::exists($fontFile))
    {// split the message on newline
      $text = explode("\\n", $message);
      if (is_array($text))
      {
        for ($i = 0; $i < count($text); $i++)
        {
          $pX = $posX;
          $pY = $posY;
          $textBox = static::calculateTextBox($fontSize, $rotate, $fontFile, $text[$i]);
          if ($center)
          { // centered horizontal
            $pX = (static::$file_info['width'] - $textBox['width']) / 2;
          }
          if ($middle)
          { // centered vertical
            $pY = ((static::$file_info['height'] - $textBox['height']) / 2) + ($i * $textBox['height']);
          }
          else
          {
            $pY = $posY + $i * $textBox['height'];
          }
          if (static::$file_info['type'] == "PNG")
          { // Help for PNG Alpha
            imagealphablending(static::$image_resource, true);
            imagesavealpha(static::$image_resource, true);
          }
          $color = imagecolorallocate(static::$image_resource, $color[0], $color[1], $color[2]);
          ImageTTFText(static::$image_resource, $fontSize, $rotate, $pX, $pY + $fontSize + 4, $color, $fontFile, $text[$i]);
        }
      }
    }
    return $this;
  }

  /**
   * Copys an image to another, also known as watermark.
   * The watermark image should be an png with alpha channel, otherwise
   * you dont see anything from the original behind the watermark
   * The watermark will be resized to fit the position
   * @param string $imageWatermark Path to the watermark image
   * @param int $pos Position of the watermark image. Can be one of
   * <code>
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
   * +--------+---+  +--+------+--+ +---+-------+ 
   * |        |   |  |  |      |  | |   |       |
   * |   FW   |   |  |  |   F  |  | |   |  FE   |  <-- The Image
   * |        |   |  |  |      |  | |   |       |
   * +--------+---+  +--+------+--+ +---+-------+ 
   * 
   * +--------+  +--------+  +--------+
   * |        |  |        |  |        |
   * |   FN   |  +--------+  |        |
   * |        |  |   F    |  +--------+   <-- The Image
   * +--------+  |        |  |        |
   * |        |  +--------+  |   FS   |
   * |        |  |        |  |        |
   * +--------+  +--------+  +--------+
   * </code>
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function watermark($imageWatermark, $pos = Imwg::F)
  {
    if (static::$image_resource)
    {
      if (File::exists($imageWatermark))
      {
        $watermarkInfo = static::image_info($imageWatermark);
        if ($watermarkInfo['type'] == "PNG")
        {
          $oWatermark = imagecreatefrompng($imageWatermark);
        }
        elseif ($watermarkInfo['type'] == "JPG")
        {
          $oWatermark = imagecreatefromjpeg($imageWatermark);
        }
        elseif ($watermarkInfo['type'] == "GIF")
        {
          $oWatermark = imagecreatefromgif($imageWatermark);
        }
        elseif ($watermarkInfo['type'] == "BMP")
        { // just a try, no idea if it works ^_^
          $oWatermark = Bmp2jpg::make($imageWatermark);
        }
        $watermarkWidth = $watermarkInfo['width'];
        $watermarkHeight = $watermarkInfo['height'];
        $fieldWidth = round(static::$file_info['width'] / 3);
        $fieldHeight = round(static::$file_info['height'] / 3);
        // if $pos >= 10, calculate from full image
        // size of a field is (imagesize / 3)
        if ($pos >= 10)
        {
          $ratioW = static::$file_info['width'] / $watermarkWidth;
          $ratioH = static::$file_info['height'] / $watermarkHeight;
        }
        else
        {
          $ratioW = $fieldWidth / $watermarkWidth;
          $ratioH = $fieldHeight / $watermarkHeight;
        }
        // Calculate the new watermark size
        if ($ratioW < $ratioH)
        {
          $newWatermarkWidth = round($watermarkWidth * $ratioW);
          $newWatermarkHeight = round($watermarkHeight * $ratioW);
        }
        else
        {
          $newWatermarkWidth = round($watermarkWidth * $ratioH);
          $newWatermarkHeight = round($watermarkHeight * $ratioH);
        }
        // additional px for the watermark to fit the full image
        $xBonus = 0;
        $yBonus = 0;
        if ($pos >= 10)
        {
          if (static::$file_info['width'] > $newWatermarkWidth)
          {
            $xBonus = round((static::$file_info['width'] - $newWatermarkWidth) / 2);
          }
          if (static::$file_info['height'] > $newWatermarkHeight)
          {
            $yBonus = round((static::$file_info['height'] - $newWatermarkHeight) / 2);
          }
        }
        // additional px for the watermark to fit a image field
        else
        {
          if ($fieldWidth > $newWatermarkWidth)
          {
            $xBonus = round(($fieldWidth - $newWatermarkWidth) / 2);
          }
          if ($fieldHeight > $newWatermarkHeight)
          {
            $yBonus = round(($fieldHeight - $newWatermarkHeight) / 2);
          }
        }
        $wPointX = $xBonus;
        $wPointY = $yBonus;
        switch ($pos)
        {
          case 1:
          case 10:
          case 11:
          case 12:
            // default top left corner
            break;

          case 2:
            // N
            $wPointX += $fieldWidth;
            break;

          case 3:
            // NE
            $wPointX += $fieldWidth * 2;
            break;

          case 4:
            // W
            $wPointY += $fieldHeight;
            break;

          case 5:
            // M
            $wPointX += $fieldWidth;
            $wPointY += $fieldHeight;
            break;

          case 6:
            // E
            $wPointX += $fieldWidth * 2;
            $wPointY += $fieldHeight;
            break;

          case 7:
            // SW
            $wPointY += $fieldHeight * 2;
            break;

          case 8:
            // S
            $wPointX += $fieldWidth;
            $wPointY += $fieldHeight * 2;
            break;

          case 9:
            // SE
            $wPointX += $fieldWidth * 2;
            $wPointY += $fieldHeight * 2;
            break;
        }
        // Alot of create and copy, but this is the only way i get alpha
        // to work... any ideas? mr. wise guy? ^_^
        // create a new watermark
        $tempImage = ImageCreateTruecolor($newWatermarkWidth, $newWatermarkHeight);
        // enable alpha
        imagealphablending($tempImage, true);
        $transparent = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
        imagefill($tempImage, 0, 0, $transparent);
        ImageCopyResampled($tempImage, $oWatermark, 0, 0, 0, 0, $newWatermarkWidth, $newWatermarkHeight, $watermarkWidth, $watermarkHeight);
        ImageDestroy($oWatermark);
        // update resource of watermark
        $oWatermark = ImageCreateTruecolor($newWatermarkWidth, $newWatermarkHeight);
        // enable alpha
        imagealphablending($oWatermark, true);
        imagesavealpha($oWatermark, false);
        $transparent = imagecolorallocatealpha($oWatermark, 0, 0, 0, 127);
        imagefill($oWatermark, 0, 0, $transparent);
        imagecopy($oWatermark, $tempImage, 0, 0, 0, 0, $newWatermarkWidth, $newWatermarkHeight);
        // disable alpha to save it
        imagealphablending($oWatermark, false);
        // save alpha
        imagesavealpha($oWatermark, true);
        ImageDestroy($tempImage);
        // new image
        $tempImage = imagecreatetruecolor(static::$file_info['width'], static::$file_info['height']);
        // enable alpha
        imagealphablending($tempImage, true);
        $transparent = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
        imagefill($tempImage, 0, 0, $transparent);
        // copy image to temp
        imagecopyresampled($tempImage, static::$image_resource, 0, 0, 0, 0, static::$file_info['width'], static::$file_info['height'], static::$file_info['width'], static::$file_info['height']);
        // copy watermark to temp
        imagecopyresampled($tempImage, $oWatermark, $wPointX, $wPointY, 0, 0, $newWatermarkWidth, $newWatermarkHeight, $newWatermarkWidth, $newWatermarkHeight);
        // disable alpha for saving
        imagealphablending($tempImage, false);
        // save alpha
        imagesavealpha($tempImage, true);
        imagedestroy(static::$image_resource);
        static::$image_resource = $tempImage;
      }
    }
    return $this;
  }

  /**
   * Rotates the image
   * @param int $degrees Rotation in degrees 0 - 360, default 180
   * @param array $bgcolor Specifies the color of the uncovered zone after the rotation
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function rotate($degrees = 180, $bgcolor = -1)
  {
    (is_null($degrees) || $degrees <= 0 || $degrees >= 360) ? $degrees = 180 : "";
    (is_null($bgcolor)) ? $bgcolor = "-1" : "";
    if (static::$image_resource)
    {
      if (is_array($bgcolor))
      {
        $bgcolor = imagecolorallocate(static::$image_resource, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
      }
      static::$image_resource = imagerotate(static::$image_resource, $degrees, $bgcolor);
      if (static::$file_info['type'] == "PNG")
      {
        // disable alpha for save
        imagealphablending(static::$image_resource, false);
        imagesavealpha(static::$image_resource, true);
      }
      static::$file_info['width'] = imagesx(static::$image_resource);
      static::$file_info['height'] = imagesy(static::$image_resource);
      static::$file_info['attr'] = 'width="' . static::$file_info['width'] . '" height="' . static::$file_info['height'] . '"';
    }
    return $this;
  }

  /**
   * Creates a reflection under the image
   * @param int $reflectionSize The size of the reflection
   * @param array $bgcolor Color of the reflection background 
   * default white array(255, 255, 255)
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function reflection($reflectionSize = 30, $bgcolor = array(255, 255, 255))
  {
    if (static::$image_resource)
    {
      $shadowHeight = round($reflectionSize / 5);
      // Create the new image
      $tempImg = imagecreatetruecolor(static::$file_info['width'], static::$file_info['height'] + $reflectionSize);
      if (static::$file_info['type'] == "PNG")
      { // Help PNG Alpha
        imagealphablending($tempImg, true);
        imagesavealpha($tempImg, false);
        $transparent = imagecolorallocatealpha($tempImg, 0, 0, 0, 127);
        imagefill($tempImg, 0, 0, $transparent);
      }
      else
      { // or create the background
        $background = imagecolorallocate($tempImg, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
        imagefill($tempImg, 0, 0, $background);
      }
      // copy the source
      imagecopy($tempImg, static::$image_resource, 0, 0, 0, 0, static::$file_info['width'], static::$file_info['height']);
      // flip the source
      imagecopyresampled(static::$image_resource, $tempImg, 0, 0, 0, static::$file_info['height'] - 1, static::$file_info['width'], static::$file_info['height'], static::$file_info['width'], -static::$file_info['height']);
      // merge the images
      imagecopy($tempImg, static::$image_resource, 0, static::$file_info['height'], 0, 0, static::$file_info['width'], static::$file_info['height']);
      // create the reflection
      $alpha = 60 / ($reflectionSize - 1);
      for ($i = 0; $i < $reflectionSize; $i++)
      {
        $col = imagecolorallocatealpha($tempImg, $bgcolor[0], $bgcolor[1], $bgcolor[2], 60 - $i * $alpha);
        imageline($tempImg, 0, static::$file_info['height'] + $i, static::$file_info['width'], static::$file_info['height'] + $i, $col);
      }
      // Create the shadow
      $alphaF = 60 / ($shadowHeight - 1);
      for ($i = 0; $i < $shadowHeight; $i++)
      {
        $shadowColor = imagecolorallocatealpha($tempImg, 160, 160, 160, $i * $alphaF + 67);
        imageline($tempImg, 0, static::$file_info['height'] + $i, static::$file_info['width'], static::$file_info['height'] + $i, $shadowColor);
      }
      if (static::$file_info['type'] == "PNG")
      { // PNG Alpha
        imagealphablending($tempImg, false);
        imagesavealpha($tempImg, true);
      }
      imagedestroy(static::$image_resource);
      static::$image_resource = $tempImg;
      static::$file_info['height'] += $reflectionSize;
      static::$file_info['attr'] = 'width="' . static::$file_info['width'] . '" height="' . static::$file_info['height'] . '"';
    }
    return $this;
  }

  /**
   * This, of course, mirrors the image
   * @param string $type h, v, b or	HORIZONTAL, VERTICAL, BOTH
   * @return \ImageManipulationWithGd\Imwg
   */
  public function mirror($type = 'BOTH')
  {
    if (static::$image_resource)
    {
      $tempImage = imagecreatetruecolor(static::$file_info['width'], static::$file_info['height']);
      if (static::$file_info['type'] == "PNG")
      {
        // Mögliche Transparenzen halten...
        imagealphablending($tempImage, true);
        imagesavealpha($tempImage, false);
        $transparent = imagecolorallocatealpha($tempImage, 0, 0, 0, 127);
        imagefill($tempImage, 0, 0, $transparent);
      }
      switch (strtolower($type))
      {
        case "h":
        case "horizontal":
          imagecopyresampled($tempImage, static::$image_resource, 0, 0, static::$file_info['width'] - 1, 0, static::$file_info['width'], static::$file_info['height'], -static::$file_info['width'], static::$file_info['height']);
          break;
        case "v":
        case "vertical":
          imagecopyresampled($tempImage, static::$image_resource, 0, 0, 0, static::$file_info['height'] - 1, static::$file_info['width'], static::$file_info['height'], static::$file_info['width'], -static::$file_info['height']);
          break;
        default: //both
          $tempImage2 = imagecreatetruecolor(static::$file_info['width'], static::$file_info['height']);
          if (static::$file_info['type'] == "PNG")
          { // PNG Alpha
            imagealphablending($tempImage2, true);
            imagesavealpha($tempImage2, false);
            $transparent = imagecolorallocatealpha($tempImage2, 0, 0, 0, 127);
            imagefill($tempImage2, 0, 0, $transparent);
          }
          imagecopyresampled($tempImage2, static::$image_resource, 0, 0, static::$file_info['width'] - 1, 0, static::$file_info['width'], static::$file_info['height'], -static::$file_info['width'], static::$file_info['height']);
          imagecopyresampled($tempImage, $tempImage2, 0, 0, 0, static::$file_info['height'] - 1, static::$file_info['width'], static::$file_info['height'], static::$file_info['width'], -static::$file_info['height']);
          imagedestroy($tempImage2);
      }
      if (static::$file_info['type'] == "PNG")
      { // PNG Alpha
        imagealphablending($tempImage, false);
        imagesavealpha($tempImage, true);
      }
      imagedestroy(static::$image_resource);
      static::$image_resource = $tempImage;
    }
    return $this;
  }

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
  public function filter($filter = 999, $arg1 = 0, $arg2 = 0, $arg3 = 0, $alpha = 0)
  {
    if (static::$image_resource)
    {
      switch ($filter)
      {
        case IMG_FILTER_NEGATE:
        case IMG_FILTER_GRAYSCALE:
        case IMG_FILTER_EDGEDETECT:
        case IMG_FILTER_EMBOSS:
        case IMG_FILTER_GAUSSIAN_BLUR:
        case IMG_FILTER_SELECTIVE_BLUR:
        case IMG_FILTER_MEAN_REMOVAL:
          imagefilter(static::$image_resource, $filter);
          break;

        case IMG_FILTER_BRIGHTNESS:
        case IMG_FILTER_CONTRAST:
        case IMG_FILTER_SMOOTH:
        case IMG_FILTER_PIXELATE:
          imagefilter(static::$image_resource, $filter, $arg1);
          break;

        case IMG_FILTER_PIXELATE:
          imagefilter(static::$image_resource, $filter, $arg1, $arg2);
          break;

        case IMG_FILTER_COLORIZE:
          imagefilter(static::$image_resource, $filter, $arg1, $arg2, $arg3, $alpha);
          break;

        default: // Sepia like
          imagefilter(static::$image_resource, IMG_FILTER_GRAYSCALE);
          imagefilter(static::$image_resource, IMG_FILTER_COLORIZE, 100, 50, 0);
          break;
      }
    }
    return $this;
  }

  /**
   * Converts an image to another type
   * @param string $filetype JPG, PNG, GIF
   * @param array $bgcolor for converting PNG whith transparency to JPG
   * @return \ImageManipulationWithGd\Imwg 
   */
  public function convert($filetype = null, $bgcolor = array(255, 255, 255))
  {
    switch ($filetype)
    {
      case "JPG":
        if (static::$file_info['type'] == "PNG")
        { // Fill png transparency with $bgcolor
          $tempImage = ImageCreateTruecolor(static::$file_info['width'], static::$file_info['height']);
          $col = imagecolorallocate($tempImage, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
          imagefill($tempImage, 0, 0, $col);
          ImageCopyResampled($tempImage, static::$image_resource, 0, 0, 0, 0, static::$file_info['width'], static::$file_info['height'], static::$file_info['width'], static::$file_info['height']);
          ImageDestroy(static::$image_resource);
          static::$image_resource = $tempImage;
          static::$image_quality = 92;
        }
        static::$file_info['type'] = "JPG";
        static::$file_info['mime'] = "image/jpg";
        break;

      case "PNG":
        static::$file_info['type'] = "PNG";
        static::$file_info['mime'] = "image/png";
        static::$image_quality = 0;
        break;

      case "GIF":
        static::$file_info['type'] = "GIF";
        static::$file_info['mime'] = "image/gif";
        break;

      default:
        // nothing
        break;
    }
    return $this;
  }

  /**
   * Calculates the area, that the message with the given font needs
   * @param int $fontSize The fontsize
   * @param int $fontAngle The rotation
   * @param string $fontFile The path to the fontfile
   * @param string $text The message
   * @return array with the following elements:
   * left, top => Coordinates for the left top corner
   * width, height => size of the textbox
   */
  private static function calculateTextBox($fontSize, $fontAngle, $fontFile, $text)
  {
    $rect = imagettfbbox($fontSize, $fontAngle, $fontFile, $text);
    $minX = min(array($rect[0], $rect[2], $rect[4], $rect[6]));
    $maxX = max(array($rect[0], $rect[2], $rect[4], $rect[6]));
    $minY = min(array($rect[1], $rect[3], $rect[5], $rect[7]));
    $maxY = max(array($rect[1], $rect[3], $rect[5], $rect[7]));

    return array(
        "left" => abs($minX) - 1,
        "top" => abs($minY) - 1,
        "width" => $maxX - $minX,
        "height" => $maxY - $minY,
    );
  }

  /**
   * Gives information about the image
   * static call, so you can use it without loading an image to Imwg
   * $info = Imwg::getImageInfo('path/to/image.jpg');
   * @param string $file Path to the Image
   * @return array An array with: width,height,type,attr,bits,channels,mime
   */
  public static function image_info($file)
  {
    $redefine_keys = array('width', 'height', 'type', 'attr', 'bits', 'channels', 'mime');
    $types = array(1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF', 5 => 'PSD', 6 => 'BMP', 7 => 'TIFF(intel byte order)', 8 => 'TIFF(motorola byte order)', 9 => 'JPC', 10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF', 15 => 'WBMP', 16 => 'XBM');
    $temp = array();
    $data = array();
    $temp = @getimagesize($file);
    if ($temp === false)
    {
      trigger_error('File ' . $file . ' is not a valid Image.', E_USER_ERROR);
    }
    $temp = array_values($temp);
    foreach ($temp AS $k => $v)
    {
      $data[$redefine_keys[$k]] = $v;
    }
    $data['type'] = $types[$data['type']];

    if (($data['type'] == "PNG") && !array_key_exists('mime', $data))
    {
      $data['mime'] = "image/png";
    }

    if (!array_key_exists('mime', $data))
    {
      $data['mime'] = "image/unknown";
    }
    return $data;
  }

  /**
   * Saves an Image
   * @param string $file_name The new Filename
   * @param bool $override True to override existing files
   * @param bool $destroy True to destroy the Imwg instance
   * @return array with infos about the image. example:<code>
   *    (
   *         [width] => 32
   *         [height] => 32
   *         [type] => GIF
   *         [attr] => width="32" height="32"
   *         [bits] => 4
   *         [channels] => 3
   *         [mime] => image/gif
   *     )
   * </code>
   */
  public function save($file_name, $override = true, $destroy = true)
  {
    if (static::$image_resource)
    {
      if (!File::exists($file_name) || $override)
      {
        // JPEG
        if (static::$file_info['type'] == "JPG")
        {
          ImageJPEG(static::$image_resource, $file_name, static::$image_quality);
        }
        // GIF
        elseif (static::$file_info['type'] == "GIF")
        {
          ImageGIF(static::$image_resource, $file_name);
        }
        // PNG
        elseif (static::$file_info['type'] == "PNG")
        {
          ImagePNG(static::$image_resource, $file_name, static::$image_quality);
        }
      }
      // cache $file_info
      $file_info = static::$file_info;
      if ($destroy) // destroy all resources
      {
        $this->destroy();
      }
      // return the infos about the image
      return $file_info;
    }
  }

  /**
   * Displays the image
   * @param bool $header true sends the imageheader
   */
  public function display($header = true)
  {
    if (static::$image_resource)
    {
      try
      {
        if ($header)
        {
          if (!headers_sent())
          {
            $headers = array(
                'x-created-with' => 'Imwg Laravel Bundle', // Just for fun...^-^
                'Last-Modified' => gmdate('r', time()),
                'Cache-Control' => 'must-revalidate',
                'Expires' => gmdate('r', time()),
                'Content-type' => static::$file_info['mime'],
            );
            Response::make('', 200, $headers)->send_headers();
          }
        }
        // JPEG
        if (static::$file_info['type'] == "JPG")
        {
          ImageJPEG(static::$image_resource, null, static::$image_quality);
        }
        // GIF
        elseif (static::$file_info['type'] == "GIF")
        {
          ImageGIF(static::$image_resource);
        }
        // PNG
        elseif (static::$file_info['type'] == "PNG")
        {
          ImagePNG(static::$image_resource, null, static::$image_quality);
        }
        $this->destroy();
        exit();
      }
      catch (Exception $e)
      {
        Error::exception($e);
      }
    }
  }

  /**
   * Removes all resources of Imwg
   */
  public function destroy()
  {
    if (static::$image_resource)
    {
      try
      {
        imageDestroy(static::$image_resource);
        static::$image_resource = null;
        foreach ($this as $key)
        {
          $this->$key = null;
        }
      }
      catch (Exception $e)
      {
        Error::exception($e);
      }
    }
  }

}