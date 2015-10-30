#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToselect('visibility');

$valueIdOfInvisible = getAttributeValueIdFromOptions('attributeName', 'visibility', 'Not Visible Individually');

foreach($productCollection as $product) {
    $mediaGalleryImages = Mage::getModel('catalog/product')->load($product->getId())->getMediaGalleryImages();
    if (count($mediaGalleryImages) < 2) {
        foreach ($mediaGalleryImages as $image) {
            $pathinfo = pathinfo($image['url']);
            preg_match('/cs/', $pathinfo['basename'], $match);
            if ($match) {
                echo $pathinfo['basename'] . PHP_EOL;
            }
        }
    }
}
