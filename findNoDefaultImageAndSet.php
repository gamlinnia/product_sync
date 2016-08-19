#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* set the store id to 0, to change the attr by default */
Mage::app('admin');

$collection = Mage::getModel('catalog/product')->getCollection()
    ->setOrder('entity_id', 'DESC');

foreach ($collection as $_product) {

    $product = Mage::getModel('catalog/product')->load($_product->getId());

    $mediaGalleryArray = $product->getMediaGallery();

    if (!empty($mediaGalleryArray['images'])) {
        var_dump($mainImage = $mediaGalleryArray['images'][0]['file']);
        if ($product->getImage() != $mainImage) {
            Zend_Debug::dump($product->getSku());
            $product->setImage($mainImage);
            $product->setSmallImage($mainImage);
            $product->setThumbnail($mainImage);
            $product->save();
            sleep(rand(1,4));
        }
    } else {
        echo 'no image exists' . PHP_EOL;
    }
}
