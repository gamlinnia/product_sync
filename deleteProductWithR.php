#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('default')->setCurrentStore( Mage_Core_Model_App :: ADMIN_STORE_ID );

$productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('name');

foreach ($productCollection as $eachProduct) {
    $sku = $eachProduct->getSku();
    preg_match('/(.+)[R]$/i', $sku, $match);
    if ($match) {
        echo "found sku: $sku" . PHP_EOL;
        $product = Mage::getModel('catalog/product');
        $product_id = $product->getIdBySku($match[1]);
        if ($product_id) {
            echo $match[1] . " with product id: $product_id relate to $sku" . PHP_EOL;
            echo "need to delete $sku" . PHP_EOL;
            $productToBeDelete = getProductObject($sku, 'sku');
            var_dump($productToBeDelete->getData());
            $productToBeDelete->delete();
        }
    }
}
