#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

$productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToselect('website_ids');

foreach ($productCollection as $product) {
    $productWebisteIds = $product->getWebsiteIds();
    if (!$productWebisteIds) {
        $product->setWebsiteIds(getAllWebisteIds());
        $product->save();
    }

    $product_id = $product->getId();
    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    echo "processing id: $product_id" . PHP_EOL;
    echo json_encode($stockItem->getData(), JSON_PRETTY_PRINT);

    if (!$stockItem->getData('manage_stock')) {
        echo 'not managed by stock' . PHP_EOL;
        $stockItem->setData('product_id', $product_id);
        $stockItem->setData('stock_id', 1);
        $stockItem->save();

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        echo json_encode($stockItem->getData(), JSON_PRETTY_PRINT);
        $stockItem->setData('manage_stock', 1);
        $stockItem->setData('is_in_stock', 1);
        $stockItem->setData('qty', 100);
        $stockItem->save();
        sleep(rand(1, 3));
    } else {
        if ((string)$stockItem->getData('qty') != '100') {
            $stockItem->setData('manage_stock', 1);
            $stockItem->setData('is_in_stock', 1);
            $stockItem->setData('qty', 100);
            $stockItem->save();
            sleep(rand(1, 3));
        }
    }
}
