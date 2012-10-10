<?php
/**
 * Imwg - Imagemanipulation with GD
 *
 * @package  Laravel-Imwg
 * @version  1.0
 * @author   Nico R <lt500r@gmail.com>
 * @link     https://github.com/Sentences
 */
Route::get('(:bundle)/(:any)/(:all)', function($options, $image)
        {
          // Load the default options
          $config = Config::get('imwg::settings');
          $image = $config['image_path'] . $image;

          // Check if the Config file exists
          if ((Config::has('imwg::route_options/' . $options)) && (File::exists($image)))
          {
            $opt = Config::get('imwg::route_options/' . $options);
            // check, if we should use the cache
            // First, check the $options for the route
            //  - then check the config file
            $use_cache = Config::get('imwg::route_options/' . $options . '.use_cache', Config::get('imwg::settings.use_cache'));
            if ($use_cache)
            { // We should use the cache
              $lifetime = Config::get('imwg::route_options/' . $options . '.cache_lifetime', Config::get('imwg::settings.cache_lifetime'));
              // retrieve a filename for the cache
              $cache_filename = ImageManipulationWithGd\Filecache::retrieve_valid_filename($image, $options);
              if (ImageManipulationWithGd\Filecache::has($cache_filename, $lifetime))
              { // The cache has our file, get the full filepath
                $cached_image = ImageManipulationWithGd\Filecache::get_path($cache_filename);
                send_image_to_browser($cached_image, $lifetime, true);
              }
              else
              { // File is not cached, lets get a path
                $cache_image_path = ImageManipulationWithGd\Filecache::get_path($cache_filename);

                $imwg = ImageManipulationWithGd\Imwg::open($image);
                parse_options($imwg, $opt);
                $imwg->save($cache_image_path);
                send_image_to_browser($cache_image_path, $lifetime, true);
              }
            }
            else
            {
              // caching is disabled
              $imwg = ImageManipulationWithGd\Imwg::open($image);
              parse_options($imwg, $opt);
              $imwg->display();
              exit();
            }
          }
          // No config file
          // Check if the image exists
          if (File::exists($image))
          {
            send_image_to_browser($image);
          }
          // No config and
          // no image found
          return Response::error('404');
        });

/**
 * This parses the options to the Imwg Class
 * @param resource $imwg The Class instance
 * @param array $options Array with options to parse
 */
function parse_options($imwg, $options)
{
  foreach ($options AS $method => $params)
  { //pass through all options and check if we have that method
    if (($method == 'display') || ($method == 'image_info') || ($method == 'destroy')) // Filter bad methods out
      continue;
    if (method_exists($imwg, $method))
    { // call each method
      call_user_func_array(array($imwg, $method), $params);
    }
  }
}

/**
 * This sends the image to the browser and handles the Browser Imagecache
 * @param string $image The full path to the image
 * @param int $lifetime The lifetime of the image
 * @param bool $cache If its a cached image, true
 */
function send_image_to_browser($image, $lifetime = 0, $cache = false)
{ // Working with the Browsercache
  $filetime = filemtime($image);
  $etag = md5($filetime . $image);
  $time = gmdate('r', $filetime);
  $expires = gmdate('r', $filetime + $lifetime);

  $headers = array(
      'x-created-with' => 'Imwg Laravel Bundle',
      'Last-Modified' => $time,
      'Cache-Control' => 'must-revalidate',
      'Expires' => $expires,
      'Etag' => $etag,
  );
  if ($cache)
  { // Add the cache filename to the header
    $headers = array_merge($headers, array(
        'x-cache-file' => basename($image),
            ));
  }
  $headerTest1 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $time;
  $headerTest2 = isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag;
  if ($headerTest1 || $headerTest2)
  { //image is cached by the browser, we dont need to send it again
    Response::make('', 304, $headers)->send_headers();
    exit();
  }
  // Image is not Cached, send it
  $fileinfos = Imwg::image_info($image);
  $length = filesize($image);
  $headers = array_merge($headers, array(
      'Content-Type' => $fileinfos['mime'],
      'Content-Length' => $length,
          ));
  Response::make('', 200, $headers)->send_headers();
  readfile($image);
  exit();
}