<?php
/**
 * /bundles/imwg/start.php
 * Imwg - Imagemanipulation with GD
 *
 * @package  Laravel-Imwg
 * @version  1.0.1
 * @author   Nico R <lt500r@gmail.com>
 * @link     https://github.com/Sentences
 */

Autoloader::namespaces(array(
	'ImageManipulationWithGd'   => __DIR__ . DS . 'classes' . DS,
));

Autoloader::alias('ImageManipulationWithGd\\Imwg', 'Imwg');