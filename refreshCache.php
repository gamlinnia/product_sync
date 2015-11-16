#!/usr/bin/php -q
<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');

foreach (array('block_html','collections','fpc') as $type) {
    Mage::app()->getCacheInstance()->cleanType($type);
    Mage::dispatchEvent('adminhtml_cache_refresh_type', array('type' => $type));
}

$indexCollection = Mage::getModel('index/process')->getCollection();
foreach ($indexCollection as $type => $index) {
    Mage::log('Rebuild indexes:' . $index->getIndexerCode(), null, 'jobs.log');
    /* @var $index Mage_Index_Model_Process */
    $index->reindexAll();
}