#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$product = getProductObject('03-105-026', 'sku');

$entityTypeId = $product->getEntityTypeId();
$mediaGalleryAttribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($entityTypeId, 'media_gallery');
$gallery = $product->getMediaGalleryImages();
foreach ($gallery as $image)
    $mediaGalleryAttribute->getBackend()->removeImage($product, $image->getFile());
$product->save();