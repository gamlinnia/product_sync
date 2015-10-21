#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$product=Mage::getModel('catalog/product')->load(1222);

$response = array(
    'user_manual' => null,
    'driver' => null,
    'firmware' => null
);

$downloadables = array(
    'user_manual' => 'usermanuals/usermanuals',
    'driver' => 'drivers/drivers',
    'firmware' => 'firmware/firmware'
);

foreach ($downloadables as $downloadType => $relativeModel) {
    $objectArray = Mage::getModel($relativeModel)->getCollection()->addFieldToFilter('product_id',$product->getId());
    if(count($objectArray)>0) {
        $response[$downloadType] = array();
        foreach($objectArray as $object) {
            $response[$downloadType][] = array(
                'base' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
                'file' => $object->getFile()
            );
        }
    }
}

var_dump($response);

//$user_manuals=Mage::getModel('usermanuals/usermanuals')->getCollection()->addFieldToFilter('product_id',$_product->getId());
//if(count($user_manuals)>0) {
//    foreach($user_manuals as $user_manual) {
//        echo(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA));
//        echo($user_manual->getFile());
//    }
//}
//
//$drivers=Mage::getModel('drivers/drivers')->getCollection()->addFieldToFilter('product_id',$_product->getId());
//if(count($drivers)>0) {
//    foreach($drivers as $driver) {
//        echo(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA));
//        echo($driver->getFile());
//    }
//}
//
//$firmwares=Mage::getModel('firmware/firmware')->getCollection()->addFieldToFilter('product_id',$_product->getId());
//if(count($firmwares)>0) {
//    foreach($firmwares as $firmware) {
//        echo(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA));
//        echo($firmware->getFile());
//    }
//}