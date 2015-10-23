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
    'videogallery_id' => 100,
    'videogallery_category' =>  'Product Video',
    'videogallery_url' =>  'https://www.youtube.com/watch?v=OUI6iM8iPOs',
    'name' =>  'test video',
    'image' =>  'ideogallery_OUI6iM8iPOs.jpg',
    'gallery_image' => '',
    'created' =>  '2015-10-23'
);

$videoimage = 'ideogallery_OUI6iM8iPOs.jpg';
$videoname = 'test video';
$model = Mage::getModel('videogallery/videogallery');
//$model->setData($data);
$model->setData($data)->setImage($videoimage)->setName($videoname)->setVideogalleryUrl($data['videogallery_url'])->setVideogalleryCategory($data['videogallery_category']);
