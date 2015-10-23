#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$data = array(
    'videogallery_category' =>  'Product Video',
    'videogallery_url' =>  'https://www.youtube.com/watch?v=OUI6iM8iPOs',
    'name' =>  'test video',
    'image' =>  'videogallery_OUI6iM8iPOs.jpg',
    'gallery_image' => '',
    'created' =>  '2015-10-23'
);

parse_str( parse_url( $data['videogallery_url'], PHP_URL_QUERY ) );
$imageUrl = 'http://img.youtube.com/vi/'.$v.'/0.jpg';
$videoimage = $v;
$videoname = $data['name'];

$tmpFile = file_get_contents($imageUrl);
file_put_contents(Mage::getBaseDir('media').DS."videogallery".DS.'videogallery_'.$videoimage.'.jpg', $tmpFile);

$model = Mage::getModel('videogallery/videogallery')->getVideogalleryIdByVideogalleryUrl('https://www.youtube.com/watch?v=OUI6iM8iPOs');
$model -> setData($data);
$model -> save();
