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
    $product_id = $product->getId();
    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    echo "processing id: $product_id" . PHP_EOL;
    echo json_encode($stockItem->getData(), JSON_PRETTY_PRINT);

    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    if (!$stockItem->getData('manage_stock')) {
        $stockItem->setData(array(
            "product_id"=> $product_id,
            "stock_id"=> "1"
        ));
        $stockItem->save();
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $stockItem->setData(array(
            'is_in_stock' => 1,
            "qty"=> 100,
            "manage_stock"=> 1,
        ));
        $stockItem->save();
    } else {
        $stockItem->setData('manage_stock', 1);
        $stockItem->setData('is_in_stock', 1);
        $stockItem->setData('qty', 100);
        $stockItem->save();
    }
}
