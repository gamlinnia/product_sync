#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$productCollection = Mage::getModel('catalog/product')->getCollection();

foreach ($productCollection as $product) {
    $id = $product->getId();
    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    echo "processing id: $id" . PHP_EOL;
    echo json_encode($stockItem->getData(), JSON_PRETTY_PRINT);
    $stockItem->setData('manage_stock', 1);
    $stockItem->save();

    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    $stockItem->setData('is_in_stock', 1);
    $stockItem->setData('qty', 100);
    $stockItem->save();
}
