#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$pageSize = 10;
$pageNumber = 1;
$count = getCountNumberOfProducts();

$forTimes = floor($count / $pageSize) + 1;
echo 'need to do ' . $forTimes . ' times' . PHP_EOL;
$response = array();
for ($i = 0; $i < $forTimes; $i++) {
    echo 'this is the ' . ($i+1) . ' times' . PHP_EOL;
    $result = getProductInfoFromMagentoForExport($pageSize, $i+1, array(
        'description', 'ne_description', 'pspec_pan_tilt_zoom', 'ne_highlight',
        'url_path',
        'url_key',
        'stock_item (Varien_Object)'
    ));
    foreach ($result as $product) {
        $parsedProductInfo = parseProductAttributesForExport($product);
        $response[] = $parsedProductInfo;
    }
}
echo 'total counts: ' . count($response);

require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
exportArrayToXlsx($response, array("filename"=>"rwProductList.xls", "title"=>"Product List"));

$product = Mage::getModel('catalog/product')->load(15);
$cats = $product->getCategoryIds();
foreach ($cats as $category_id) {
    $_cat = Mage::getModel('catalog/category')->load($category_id) ;
    echo $_cat->getName() . ' id:' . $category_id . PHP_EOL;
}

$category = Mage::getModel('catalog/category')->load(2);
echo $category->getName() . PHP_EOL;
var_dump($category->getData());


$categoryCollection = $product->getCategoryCollection()
    ->addAttributeToSelect('name');
foreach ($categoryCollection as $each) {
    echo $each->getName();
    var_dump($each->getData());
}
