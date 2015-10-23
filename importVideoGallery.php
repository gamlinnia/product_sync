#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

// Remove unset images, add image to gallery if exists
$importDir = Mage::getBaseDir('media') . DS . 'import/';
if (!file_exists($importDir)) {
    mkdir($importDir);
}
$url = 'http://www.bikez.com/pictures/um/2007/dsf-200.jpg';
$pathInfo = pathinfo($url);     // get array of dirname, basename, extension, filename
$fileName = getFileNameFromUrl($url);
if (!$fileName) {
    die('Can not get file name from url');
}
$tmpFile = file_get_contents($url);
file_put_contents($importDir . $fileName, $tmpFile);
$filePath = $importDir . $fileName;

$data = array(
    'videogallery_id' => '102',
    'videogallery_category' =>  'Product Video',
    'videogallery_url' =>  'https://www.youtube.com/watch?v=OUI6iM8iPOs',
    'name' =>  'test video',
    'image' =>  'videogallery_OUI6iM8iPOs.jpg',
    'gallery_image' => '',
    'created' =>  '2015-10-23'
);

$videoimage = $fileName;
$videoname = 'test video';
$model = Mage::getModel('videogallery/videogallery');

$model -> addImageToVideoGallery($filePath);
//$model->setData($data);
$model->setData($data)->setImage($videoimage)->setName($videoname)->setVideogalleryUrl($data['videogallery_url'])->setVideogalleryCategory($data['videogallery_category']);
$model -> save();
