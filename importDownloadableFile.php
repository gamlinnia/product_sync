#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$product = getProductObject('17-997-071', 'sku');
$productId = $product->getId();

//$path = Mage::getBaseDir('media') . DS . 'Download_Files' . DS . 'user_manual' . DS;
//$filename=implode('_',explode(' ',$filename));
$file_path = 'download' . DS . 'user_manual' . DS . $filename;

Mage::getModel('usermanuals/usermanuals')
    ->setFile('test/test/test/test.pdf')
    ->setProductId($productId)
    ->setId(null)
    ->save();
