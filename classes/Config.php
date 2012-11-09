<?php
namespace ImageManipulationWithGd;

use \Config as LaravelConfig;

class Config extends LaravelConfig
{
  private static $options = array();

  /**
   * Load and merge the bundle and the user's configuration
   *
   * @return array The options loaded
   */
  public static function merge()
  {
    $defaults = Config::file('imwg', 'settings');
    $user = Config::file('application', 'imwg');

    return static::$options = array_merge($defaults, $user);
  }

  /**
   * Get an item from the custom configuration
   *
   * @param string $key
   * @param string $default
   *
   * @return mixed
   */
  public static function get($key = null, $default = null)
  {
    if(!$key) return static::$options;

    return array_get(static::$options, $key, $default);
  }

  /**
   * Get an option from a route
   *
   * @param string $route   The route
   * @param string $option  The option
   * @param string $default Fallback value
   *
   * @return string The desired option
   */
  public static function route($route, $option = null, $default = null)
  {
    if ($option) $route .= '.'.$option;
    $customFolder = static::get('routes_folder').'/';

    $route = LaravelConfig::has('imwg::route_options/'.$route)
      ? LaravelConfig::get('imwg::route_options/'.$route)
      : LaravelConfig::get($customFolder.$route, array());

    return $route;
  }
}
