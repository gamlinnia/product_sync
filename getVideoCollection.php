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
$response = array();

foreach ($videoGalleryCollection as $videoGallery) {
    $tmpArray = $videoGallery->debug();

    foreach ($productvideos_collection as $productvideo) {
        if($tmpArray["videogallery_id"] == $productvideo->getData("videogallery_id")){
            $tmpArray["product_id"] = $productvideo->getData("product_id");
        }
    }
    $response[] = $tmpArray;

}
var_dump($response);

//$response = array();

