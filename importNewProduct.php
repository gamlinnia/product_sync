#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

if (!isset($argv[1])) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}

preg_match('/[\d]{2}-[\d]{3}-[\d]{3}/', $argv[1] , $match);
if (count($match) < 1) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}
$sku = $match[0];

/* save product json to local files in dev environment. */
$dir = './rest/productJson/';
if (!file_get_contents($dir . 'mappingAttrs.json')) {
    echo 'Error getting mapping table file.' . PHP_EOL;
    return;
}


$mapTable = json_decode(file_get_contents($dir . 'mappingAttrs.json'));
var_dump($mapTable);
$productJson = json_decode(file_get_contents($dir . $sku));
var_dump($productJson);
