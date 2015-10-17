<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$product=Mage::getModel('catalog/product')->load(3);

// Remove unset images, add image to gallery if exists
$importDir = Mage::getBaseDir('media') . DS . 'import/';
die($importDir);

//            $product->addImageToMediaGallery($filePath, $imageType, false);
