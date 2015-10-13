<?php

$setting = json_decode(file_get_contents('setting.json'), true);
require_once '../public_html/app/Mage.php';
require_once 'functions.php';
Mage::app();

$product=Mage::getModel('catalog/product')->load(3);


foreach ($product->getMediaGalleryImages() as $image) {
echo $image->getUrl();
}
// var_dump($product->getMediaGalleryImages());

// echo Mage::getModel('catalog/product_media_config')
//        ->getMediaUrl( $product->getImage() ); //getSmallImage(), getThumbnail();

