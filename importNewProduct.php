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
if (!file_get_contents($dir . 'categoryMapToAttributeSet.json')) {
    echo 'Error getting category mapping table file.' . PHP_EOL;
    return;
}


$productJson = json_decode(file_get_contents($dir . $sku), true);
$mapTable = json_decode(file_get_contents($dir . 'mappingAttrs.json'), true);
$categoryMapToAttributeSet = json_decode(file_get_contents($dir . 'categoryMapToAttributeSet.json'), true);
var_dump($mapTable);
var_dump($categoryMapToAttributeSet);

/*get SubcategoryName in baseinfo*/
$subcategoryName = $productJson['baseinfo']['SubcategoryName'];
echo 'SubcategoryName: ' . $subcategoryName . PHP_EOL;
$mappedAttrSets = $categoryMapToAttributeSet[$subcategoryName];
echo 'map to attribute set names: ' . $mappedAttrSets . PHP_EOL;

$mappedAttrSetsArray = explode(',', $mappedAttrSets);
if ( count($mappedAttrSetsArray) > 1 ) {
    do {
        /*透過 標準輸出 印出要詢問的內容*/
        fwrite(STDOUT, 'Enter attribute set name to import new product: ');
        /*抓取 標準輸入 的 內容*/
        $mappedAttrSet = trim(fgets(STDIN));
    } while (empty($mappedAttrSet));
    echo $mappedAttrSet . PHP_EOL;
} else if (count($mappedAttrSetsArray) == 1) {
    $mappedAttrSet = $mappedAttrSetsArray[0];
} else {
    echo 'no attribute set name map to subcategory: ' . $subcategoryName . PHP_EOL;
    return;
}
echo 'map to attribute set name: ' . $mappedAttrSet . PHP_EOL;
