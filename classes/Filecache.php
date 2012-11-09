<?php
namespace ImageManipulationWithGd;

use \Laravel\File as File;
use \Laravel\Config as Config;

/**
 * The filecache
 * I might think this needs some work, but for now, it works..
 *
 * @package  Laravel-Imwg
 * @author   Nico R <lt500r@gmail.com>
 * @link     https://github.com/Sentences
 */
class Filecache
{

    /**
     * The full path to the caching dir
     * @var string
     */
    private static $cache_path = '';

    /**
     * The lifetime for the cached file
     * @var int
     */
    private static $cache_lifetime = 0;

    /**
     * Retrieves the settings from the config file
     */
    private static function loadConfig()
    {
        static::$cache_path = Config::get('cache_path', '');
        static::$cache_lifetime = Config::get('cache_lifetime', 0);
    }

    /**
     * Checks, if the cache has the given file
     * @param  string  $file     The filename
     * @param  int     $lifetime Lifetime of the file in cache
     * @return boolean false if not found
     */
    public static function has($file, $lifetime = 0)
    {
        static::loadConfig();
        $lifetime = $lifetime > 0 ? $lifetime : static::$cache_lifetime;
        $file = static::$cache_path . $file;

        if (File::exists($file)) {
            $filemtime = filemtime($file);
            if ($filemtime + $lifetime < time()) {
                File::delete($file);

                return false;
            }

            return true;
        }
    }

    /**
     * Get the Full imagepath from cache
     * @param  string         $file
     * @return string|boolean
     */
    public static function getPath($file)
    {
        static::loadConfig();
        $file = static::$cache_path . $file;

        return $file;
    }

    /**
     * Creates the filename used for caching
     * @param  string $image  The original image path
     * @param  array  $option The route_option
     * @return string The Filename
     */
    public static function retrieveValidFilename($image, $option)
    {
        $ext = File::extension($image);

        return md5($option . $image) . '.' . $ext;
    }

}
