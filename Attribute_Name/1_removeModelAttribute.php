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

$attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');

$prepareToRemove = array();

$excludeArray = array('name_long', 'name');
foreach ($attributeCollection as $eachAttr) {
    //var_dump($eachAttr->getData());
    $attributeLabel = $eachAttr->getAttributeLabel();
    $attributeCode  = $eachAttr->getAttributeCode();
    $attributeId = $eachAttr->getId();
    //use label to find attribute
    preg_match('/Name/', $attributeLabel, $matchName);
    if (count($matchName) >= 1 && !in_array($attributeCode, $excludeArray)) {
        $prepareToRemove[] = array(
            'attribute_code' => $attributeCode,
            'attribute_id' => $attributeId
        );
        echo "=============================================================================" . PHP_EOL;
        echo "    Attrbiute name: " . $attributeCode . PHP_EOL;
        echo "    Attrbiute ID: " . $attributeId . PHP_EOL;
        if(!$debug) {
            //delete attribute from database
            $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
            $setup->removeAttribute('catalog_product', $attributeCode);
        }
    }
}

var_dump($prepareToRemove);