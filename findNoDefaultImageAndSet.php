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

$collection = Mage::getModel('catalog/product')->getCollection();

foreach ($collection as $_product) {

    $product = Mage::getModel('catalog/product')->load($_product->getId());

    $mediaGalleryArray = $product->getMediaGallery();

    if (empty($product->getImage()) || $product->getImage() == 'no_selection') {
        Zend_Debug::dump($product->getSku());
        if (!empty($mediaGalleryArray['images'])) {
            var_dump($mainImage = $mediaGalleryArray['images'][0]['file']);
            $product->setImage($mainImage);
            $product->setSmallImage($mainImage);
            $product->setThumbnail($mainImage);
            $product->save();
        } else {
            echo 'no image exists' . PHP_EOL;
        }
    } else {
        /* set position 10 with base image to position 1 */
        $first = null;
        $ten = null;
        foreach ($mediaGalleryArray['images'] as $_media) {

            if ($_media['position'] == '1') {
                $first = $_media['file'];
            }
            if ($_media['position'] == '10') {
                $ten = $_media['file'];
            }
        }
        if (!empty($first) && $ten == $product->getImage()) {
            echo $product->getSku() . PHP_EOL;
            $product->setImage($first);
            $product->setSmallImage($first);
            $product->setThumbnail($first);
            $product->save();

        }
    }
}
