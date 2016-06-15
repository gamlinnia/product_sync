#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

if (!isset($argv[1])) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}
$itemNumber = $argv[1];

$IMBaseUrl = 'https://apis.newegg.org';
$imageBase = 'http://images10.newegg.com/productimage';
//$imageBase = 'http://10.1.39.209/productimage';
$restPostfix = '/content/v1/item/' . $itemNumber . '/image';



//$product = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('sku','12-132-132');
$product = Mage::getModel('catalog/product')->load(1807);
$sku = $product->getSku();
$media = Mage::getModel('catalog/product_attribute_media_api');

// Remove unset images, add image to gallery if exists
$importDir = Mage::getBaseDir('media') . DS . 'import/';
if (!file_exists($importDir)) {
    mkdir($importDir);
}

$urlArray = array(
    'http://f8rentals.com/wp-content/uploads/2015/03/IMG_0067ft.jpg',
    'http://i765.photobucket.com/albums/xx291/just-meller/national%20geografic/Birds-national-geographic-6873734-1600-1200.jpg',
    'http://www.goodlightscraps.com/content/nature/nature-images-86.jpg',
    'http://www.thinkstockphotos.com/CMS/StaticContent/Hero/TS_AnonHP_462882495_01.jpg'
);
foreach ($urlArray as $key => $url) {
    // get array of dirname, basename, extension, filename
    $pathInfo = pathinfo($url);
    switch($pathInfo['extension']){
        case 'png':
            $mimeType = 'image/png';
            break;
        case 'jpg':
            $mimeType = 'image/jpeg';
            break;
        case 'gif':
            $mimeType = 'image/gif';
            break;
    }
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

    $newImage = array(
        'file' => array(
            'content' => base64_encode($filePath),
            'mime' => $mimeType,
            'name' => basename($filePath),
        ),
        'label' => 'whatever', // change this.
        'position' => $key + 20,
        'types' => array(),
        'exclude' => 0,
    );
    $media->create($sku, $newImage);
}
