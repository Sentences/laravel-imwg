<?php
namespace ImageManipulationWithGd;

use Laravel\File;
use Laravel\Config;
/**
 *
 * @author Nico R
 */
class Resp extends \Laravel\Response
{

    /**
     * Create a response that will force a image to be displayed inline.
     *
     * @param string $path Path to the image
     * @param string $name Filename
     * @param int $lifetime Lifetime in browsers cache
     * @return Response
     */
    public static function inline($path, $name = null, $lifetime = 0)
    {
        if (is_null($name)) {
            $name = basename($path);
        }
        
        $filetime = filemtime($path);
        $etag = md5($filetime . $path);
        $time = gmdate('r', $filetime);
        $expires = gmdate('r', $filetime + $lifetime);
        $length = filesize($path);
        
        $headers = array(
            // Content-Disposition is not part of HTTP1/1
            // I think i will remove it in the future
            'Content-Disposition' => 'inline; filename="' . $name . '"',
            'Last-Modified' => $time,
            'Cache-Control' => 'must-revalidate',
            'Expires' => $expires,
            'Pragma' => 'public',
            'Etag' => $etag,
        );

        // If enabled, we need to disable the profiler
        Config::set('application.profiler', false);

        // Check the Browsers cache
        $headerTest1 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $time;
        $headerTest2 = isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag;
        if ($headerTest1 || $headerTest2) { //image is cached by the browser, we dont need to send it again
            return static::make('', 304, $headers);
        }

        $fileinfos = Imwg::imageInfo($path);
        $headers = array_merge($headers, array(
            'Content-Type' => $fileinfos['mime'],
            'Content-Length' => $length,
                ));
        
        return static::make(File::get($path), 200, $headers);

    }

}