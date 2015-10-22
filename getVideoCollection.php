#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

//$productvideos_collection=Mage::getModel('productvideos/productvideos')->getCollection();
//
//$response = array();
//foreach ($productvideos_collection as $productvideo) {
//    $response[] = $productvideo->debug();
//}
//
//var_dump($response);


$videoGalleryCollection = Mage::getModel("videogallery/videogallery")->getCollection();
$response = array();

foreach ($videoGalleryCollection as $videoGallery) {
    //var_dump($videoGallery);
    //die();
    $response[] = $videoGallery->debug();
    var_dump($response);
    die();
}

