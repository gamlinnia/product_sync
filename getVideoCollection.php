#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$videoGalleryCollection = Mage::getModel("videogallery/videogallery")->getCollection();
$productvideos_collection=Mage::getModel('productvideos/productvideos')->getCollection();
var_dump($productvideos_collection);
$response = array();

foreach ($videoGalleryCollection as $videoGallery) {
    $tmpArray = $videoGallery->debug();
    $tmpArray["sku"] = array();
    foreach ($productvideos_collection as $productvideo) {
        if($tmpArray["videogallery_id"] == $productvideo->getData("videogallery_id")){
            $product_id = $productvideo->getData("product_id");
            $product = Mage::getModel('catalog/product')->load($product_id);
            $sku = $product->getSku();
            $tmpArray["sku"][] = $sku;
        }
    }
    $response[] = $tmpArray;
}
//var_dump($response);


