<?php Namespace ImageManipulationWithGd;

class Bmp2jpg
{

  /**
   * Creates an jpg imageressource from a Bitmap
   * Original found here http://www.php.net/manual/de/function.imagecreate.php#53879
   * Modified version here http://www.programmierer-forum.de/function-imagecreatefrombmp-laeuft-mit-allen-bitraten-t143137.htm
   * @param string $filename Path to the Bitmap
   * @return mixed false or a imageressource 
   */
  public static function make($filename)
  {
    try
    {
      // version 1.00
      if (!($fh = fopen($filename, 'rb')))
      {
        trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
        return false;
      }
      // read file header
      $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
      // check for bitmap
      if ($meta['type'] != 19778)
      {
        trigger_error('imagecreatefrombmp: ' . $filename . ' is not a valid bitmap!', E_USER_WARNING);
        return false;
      }
      // read image header
      $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
      // read additional 16bit header
      if ($meta['bits'] == 16)
      {
        $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
      }
      // set bytes and padding
      $meta['bytes'] = $meta['bits'] / 8;
      $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4) - floor($meta['width'] * $meta['bytes'] / 4)));
      if ($meta['decal'] == 4)
      {
        $meta['decal'] = 0;
      }
      // obtain imagesize
      if ($meta['imagesize'] < 1)
      {
        $meta['imagesize'] = $meta['filesize'] - $meta['offset'];
        // in rare cases filesize is equal to offset so we need to read physical size
        if ($meta['imagesize'] < 1)
        {
          $meta['imagesize'] = @filesize($filename) - $meta['offset'];
          if ($meta['imagesize'] < 1)
          {
            trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
            return false;
          }
        }
      }
      // calculate colors
      $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
      // read color palette
      $palette = array();
      if ($meta['bits'] < 16)
      {
        $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
        // in rare cases the color value is signed
        if ($palette[1] < 0)
        {
          foreach ($palette as $i => $color)
          {
            $palette[$i] = $color + 16777216;
          }
        }
      }
      // create gd image
      $im = imagecreatetruecolor($meta['width'], $meta['height']);
      $data = fread($fh, $meta['imagesize']);
      $p = 0;
      $vide = chr(0);
      $y = $meta['height'] - 1;
      $error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';
      // loop through the image data beginning with the lower left corner
      while ($y >= 0)
      {
        $x = 0;
        while ($x < $meta['width'])
        {
          switch ($meta['bits'])
          {
            case 32:
            case 24:
              if (!($part = substr($data, $p, 3)))
              {
                trigger_error($error, E_USER_WARNING);
                return $im;
              }
              $color = unpack('V', $part . $vide);
              break;
            case 16:
              if (!($part = substr($data, $p, 2)))
              {
                trigger_error($error, E_USER_WARNING);
                return $im;
              }
              $color = unpack('v', $part);
              $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3);
              break;
            case 8:
              $color = unpack('n', $vide . substr($data, $p, 1));
              $color[1] = $palette[$color[1] + 1];
              break;
            case 4:
              $color = unpack('n', $vide . substr($data, floor($p), 1));
              $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
              $color[1] = $palette[$color[1] + 1];
              break;
            case 1:
              $color = unpack('n', $vide . substr($data, floor($p), 1));
              switch (($p * 8) % 8)
              {
                case 0:
                  $color[1] = $color[1] >> 7;
                  break;
                case 1:
                  $color[1] = ($color[1] & 0x40) >> 6;
                  break;
                case 2:
                  $color[1] = ($color[1] & 0x20) >> 5;
                  break;
                case 3:
                  $color[1] = ($color[1] & 0x10) >> 4;
                  break;
                case 4:
                  $color[1] = ($color[1] & 0x8) >> 3;
                  break;
                case 5:
                  $color[1] = ($color[1] & 0x4) >> 2;
                  break;
                case 6:
                  $color[1] = ($color[1] & 0x2) >> 1;
                  break;
                case 7:
                  $color[1] = ($color[1] & 0x1);
                  break;
              }
              $color[1] = $palette[$color[1] + 1];
              break;
            default:
              trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
              return false;
          }
          imagesetpixel($im, $x, $y, $color[1]);
          $x++;
          $p += $meta['bytes'];
        }
        $y--;
        $p += $meta['decal'];
      }
      fclose($fh);
      return $im;
    }
    catch (Exception $e)
    {
      Laravel\Error::exception($e);
    }
  }

}
