<?php
/**
 * Every option, that should be parsed by Imwg needs an array as value 
 */
return array(
    'square' => array(),
    'watermark' => array(path('public').'img/wmarks/company.png',Imwg::NW),
    'polaroid' => array(),
    'ttftext' => array('Just a test',null,14,null,null,null,array(255,0,0),true),
    'use_cache' => false,
);