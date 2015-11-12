<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');

$websiteIds = getAllWebisteIds();
//echo 'Website IDs: ';
//var_dump($websiteIds);

$productList = Mage::getModel('catalog/product')
               ->getCollection()
               ->addAttributeToSelect('name')
               ->addAttributeToSelect('url_key');

foreach ($productList as $product){
    //var_dump($product->getData());
    //die();
    echo 'SKU: ' . $product->getSku() . PHP_EOL;
    echo 'URL Key: ' . $product->getUrlKey() . PHP_EOL;
    $url_key = $product->getUrlKey();
    if(!empty($url_key)){
        $product->setUrlKey(false);
    }
    else{
        $url = preg_replace('/[^0-9a-z]+/i', '-', $product->getName());
        $url = strtolower($url);
        $product->setUrlKey($url);
        echo 'New URL Key: ' . $url . PHP_EOL;
    }
    $product->setWebsiteIds($websiteIds);
    $product->save();
    //die();
}