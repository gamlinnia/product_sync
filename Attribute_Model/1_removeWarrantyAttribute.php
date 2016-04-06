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

//$attributeCollection = Mage::getModel('catalog/resource_eav_attribute')->getCollection();
$attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection');

$prepareToRemove = array();

foreach ($attributeCollection as $eachAttr) {
    //var_dump($eachAttr->getData());
    preg_match('/_model/', $eachAttr->getAttributeCode(), $matchModel);
    if (count($matchModel) >= 1) {
        $prepareToRemove[] = array(
            'attribute_code' => $eachAttr->getAttributeCode(),
            'attribute_id' => $eachAttr->getId()
        );
        echo "=============================================================================" . PHP_EOL;
        echo "    Attrbiute name: " . $eachAttr->getAttributeCode() . PHP_EOL;
        echo "    Attrbiute ID: " . $eachAttr->getId() . PHP_EOL;
        if(!$debug) {
            //delete attribute from database
            $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
            $setup->removeAttribute('catalog_product', $eachAttr->getAttributeCode());
        }
    }
}

var_dump($prepareToRemove);