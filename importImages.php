<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$product=Mage::getModel('catalog/product')->load(1);

// Remove unset images, add image to gallery if exists
$importDir = Mage::getBaseDir('media') . DS . 'import/';

$url = 'http://www.bikez.com/pictures/um/2007/dsf-200.jpg';
$fileName = getFileNameFromUrl($url);
if (!$fileName) {
    die('Can not get file name from url');
}
$tmpFile = file_get_contents($url, $fileName);
file_put_contents($importDir . $fileName, $tmpFile);
$filePath = $importDir . $fileName;

$mediaArray = array(
    'thumbnail',
    'small_image',
    'image'
);

$product->addImageToMediaGallery($filePath, null, true);
