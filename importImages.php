#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

//$product = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('sku','12-132-132');
$product = Mage::getModel('catalog/product')->load(3);

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

$mediaArray = array(
    'thumbnail',
    'small_image',
    'image'
);

/* public function addImageToMediaGallery($file, $mediaAttribute=null, $move=false, $exclude=true) */
$product->addImageToMediaGallery($filePath, null, true, false);
$attributes = $product->getTypeInstance(true)->getSetAttributes($product);
$attributes['media_gallery']->getBackend()->updateImage($product, $filePath, $data=array('postion'=>1,'label'=>'images'));
$product->save();
