<?php
ini_set('memory_limit', '512M');

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');

$websiteIds = getAllWebisteIds();
$productList = Mage::getModel('catalog/product')->getCollection();
$count = 1;
foreach ($productList as $each) {
    $product = Mage::getModel('catalog/product')->load($each->getId());
    $sku = $product->getSKU();
    $name = $product->getName();
    $url_key = $product->getUrlKey();
    echo $count . PHP_EOL;
    if (!empty($url_key)) {
        echo 'Url Key Exist!' . PHP_EOL;
        $product->setUrlKey(false);
    } else {
        echo 'SKU: ' . $sku . PHP_EOL;
        echo 'URL Key: ' . $url_key . PHP_EOL;
        //echo 'Url Key Not Exist!' . PHP_EOL;
        $url = preg_replace('/[^0-9a-z]+/i', '-', $name);
        $url = strtolower($url);
        $product->setUrlKey($url);
        echo 'New URL Key: ' . $url . PHP_EOL;
    }
    $oldWebsiteIds = $product->getWebsiteIds();
    if ( $oldWebsiteIds !== $websiteIds || empty($url_key))
    {
        $product->setWebsiteIds($websiteIds);
        $product->save();
        sleep(1);
    }
    $count++;
}