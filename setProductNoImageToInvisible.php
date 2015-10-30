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
    foreach (Mage::getModel('catalog/product')->load($product->getId()->getMediaGalleryImages()) as $image) {
        var_dump($image);
    }
}
