<?php
/**
 * imwg/config/settings.php
 * Default Settings File
 * @package  Laravel-Imwg
 * @author   Nico R <lt500r@gmail.com>
 * @link     https://github.com/Sentences
 */
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
    //'ttf_font' => path('public').'fonts/mylovelyfont.ttf',
    /**
     * Should images, modified via route, been cached?
     */
    'use_cache' => true,
    /**
     * Path, where the images should be cached 
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

    /**
     * The folder to put your custom routes in your application/config folder
     */
    'routes_folder' => 'route_options',
);