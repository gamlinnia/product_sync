#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$product=Mage::getModel('catalog/product')->load(3);


foreach ($product->getMediaGalleryImages() as $image) {
    echo $image->getUrl();
    Zend_Debug::dump($image);
}
// var_dump($product->getMediaGalleryImages());

// echo Mage::getModel('catalog/product_media_config')
//        ->getMediaUrl( $product->getImage() ); //getSmallImage(), getThumbnail();

