<?php
/*
 * for color attribute now
 *
 * */
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$new_attribute_code = 'brand';

$new_attribute_options = getAttributeOptions('attributeName', 'brand');

//var_dump($new_attribute_options);

foreach($new_attribute_options['options'] as $option){
    if(strtolower($option['label']) == strtolower('rosewill')){
        $new_attribute_value = $option['value'];
    }
}
//echo $new_attribute_value . PHP_EOL;

$productCollection = Mage::getModel('catalog/product')->getCollection();
foreach($productCollection as $each) {
    $product = Mage::getModel('catalog/product')->load($each->getId());
    echo "Product ID: " . $product->getId() . PHP_EOL;
    try {
        $product->setData($new_attribute_code, $new_attribute_value);
        $product->save();
    } catch (exception $e) {
        echo $e->getMessage() . PHP_EOL;
    }
}
