<?php
use ImageManipulationWithGd\Config;
use ImageManipulationWithGd\Filecache;
use ImageManipulationWithGd\Imwg;
use ImageManipulationWithGd\Resp;

/**
 * Imwg - Imagemanipulation with GD
 *
 * @package  Laravel-Imwg
 * @version  1.0.1
 * @author   Nico R <lt500r@gmail.com>
 * @link     https://github.com/Sentences
 */
Route::get('(:bundle)/(:any)/(:all)', function($options, $image) {

    // Load the default options
    $config = Config::get();
    $image = $config['image_path'] . $image;

    // Check if the Config file exists
    if ((Config::route($options)) && (File::exists($image))) {
        $opt = Config::route($options);
        // check, if we should use the cache
        // First, check the $options for the route
        //  - then check the config file
        $use_cache = Config::route($options, 'use_cache', Config::get('use_cache'));
        if ($use_cache) { // We should use the cache
            $lifetime = Config::route($options, 'cache_lifetime', Config::get('cache_lifetime'));
            // retrieve a filename for the cache
            $cache_filename = Filecache::retrieveValidFilename($image, $options);
            if (Filecache::has($cache_filename, $lifetime)) { // The cache has our file, get the full filepath
                $cached_image = Filecache::getPath($cache_filename);

                return Resp::inline($cached_image, basename($image), $lifetime);
            } else { // File is not cached, lets get a path
                $cache_image_path = Filecache::getPath($cache_filename);

                $imwg = Imwg::open($image);
                parseOptions($imwg, $opt);
                $imwg->save($cache_image_path);

                return Resp::inline($cache_image_path, basename($image), $lifetime);
            }
        } else {
            // caching is disabled
            $imwg = Imwg::open($image);
            parseOptions($imwg, $opt);

            return $imwg->display();
            #$imwg->display();
            #exit();
        }
    }
    // No config file
    // Check if the image exists
    if (File::exists($image)) {
        return Resp::inline($image);
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
function parseOptions($imwg, $options)
{
    foreach ($options AS $method => $params) { //pass through all options and check if we have that method
        if (($method == 'display') || ($method == 'image_info') || ($method == 'destroy')) // Filter bad methods out
            continue;
        if (method_exists($imwg, $method)) // call each method
            call_user_func_array(array($imwg, $method), $params);
    }
}
