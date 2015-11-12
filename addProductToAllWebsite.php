<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');

$websiteIds = getAllWebisteIds();
echo 'Website IDs: ';
var_dump($websiteIds);
//die();

$productList = Mage::getModel('catalog/product')->getCollection();
foreach ($productList as $product){
    echo 'SKU: ' . $product->getSku() . PHP_EOL;
    if($product->getUrlKey()){
        echo 'URL Key: ' . $product->getUrlKey() . PHP_EOL;
        $product->setUrlKey(false);
    }
    $product->setWebsiteIds($websiteIds);
    $product->save();
}