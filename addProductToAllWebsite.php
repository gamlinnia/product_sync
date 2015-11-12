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

$productList = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('Name');
$i=0;
foreach ($productList as $product){
    echo 'SKU: ' . $product->getSku() . PHP_EOL;
    echo 'URL Key: ' . $product->getUrlKey() . PHP_EOL;
    $url_key = $product->getUrlKey();
    if(!empty($url_key)){
        echo 'No Url Key~~';
        $product->setUrlKey(false);
    }
    else{
        $url = preg_replace('/[^0-9a-z]+/ig', '-', $product->getName());
        $url = strtolower($url);
        $product->setUrlKey($url);
    }
    echo 'New URL Key: ' . $url . PHP_EOL;
    $product->setWebsiteIds($websiteIds);
    $product->save();
    $i++;
    if($i==2)
    {
        die();
    }
}