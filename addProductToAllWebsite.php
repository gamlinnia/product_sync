<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');

$websiteIds = getAllWebisteIds();
$productList = Mage::getModel('catalog/product')->getCollection();

foreach ($productList as $each) {
    $product = Mage::getModel('catalog/product')->load($each->getId());
    echo 'Current Page: ' . $currentPage . PHP_EOL;
    echo 'SKU: ' . $product->getSku() . PHP_EOL;
    echo 'URL Key: ' . $product->getUrlKey() . PHP_EOL;
    $url_key = $product->getUrlKey();
    if (!empty($url_key)) {
        $product->setUrlKey(false);
    } else {
        $url = preg_replace('/[^0-9a-z]+/i', '-', $product->getName());
        $url = strtolower($url);
        $product->setUrlKey($url);
        echo 'New URL Key: ' . $url . PHP_EOL;
    }
    $oldWebsiteIds = $product->getWebsiteIds();
    if ( $oldWebsiteIds !== $websiteIds)
    {
        $product->setWebsiteIds($websiteIds);
        try {
            $product->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'jobs.log');
        }
        sleep(rand(1, 3));
    }
}