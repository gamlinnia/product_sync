#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

// 透過 標準輸出 印出要詢問的內容
fwrite(STDOUT, 'To edit or to delete: ');
// 抓取 標準輸入 的 內容
$action = trim(fgets(STDIN));
echo $action . PHP_EOL;


die();

if (!isset($argv[1])) {
    preg_match('/[\d]{2}-[\d]{3}-[\d]{3}/', $argv[1] , $match);
    if (count($match) < 1) {
        echo 'Model number is not specified.' . PHP_EOL;
        return;
    }
}

/* save product json to local files in dev environment. */
$dir = './rest/productJson/';
if (!file_get_contents($dir . 'mappingAttrs.json')) {
    echo 'Error getting mapping table file.' . PHP_EOL;
    return;
}
if (!file_get_contents($dir . 'mappingAttrs.json')) {
    echo 'Error getting mapping table file.' . PHP_EOL;
    return;
}

$mapTable = json_encode(file_get_contents($dir . 'mappingAttrs.json'));