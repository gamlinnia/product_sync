<?php
/*
 * for color attribute now
 *
 * */
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

$attributeName = 'brand';
$groupName = 'General';

$attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection');

foreach($attributeSetCollection as $each){
    $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
    $attributeSetName = $each->getAttributeSetName();
    echo $attributeSetName . PHP_EOL;
    foreach($attributes as $eachAttr){
        preg_match('/brand/', $eachAttr['code'], $matchBrand);
        if(count($matchBrand) >= 1){
            moveAttributeToGroup($attributeName, $attributeSetName, $groupName);
        }
    }
}