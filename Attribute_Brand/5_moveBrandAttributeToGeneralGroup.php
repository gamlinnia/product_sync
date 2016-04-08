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
$excludeAttributeSet = array('Default');
$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

foreach($attributeSetCollection as $each){
    $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
    $attributeSetName = $each->getAttributeSetName();
    echo $attributeSetName . PHP_EOL;
    if(!in_array($attributeSetName, $excludeAttributeSet)) {
        foreach ($attributes as $eachAttr) {
            echo "    " . $eachAttr['code'] . PHP_EOL;
            preg_match('/brand/', $eachAttr['code'], $matchBrand);
            if (count($matchBrand) >= 1) {
                moveAttributeToGroupInAttrbiuteSet($attributeName, $attributeSetName, $groupName);
            }
        }
    }
}