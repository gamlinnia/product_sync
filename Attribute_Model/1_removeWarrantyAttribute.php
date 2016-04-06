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

$attributeCollection = Mage::getModel('catalog/resource_eav_attribute')->getCollection();

$prepareToRemove = array();

foreach ($attributeCollection as $eachAttr) {
    preg_match('/_model/', $eachAttr->getCode(), $matchWarranty);
    if (count($matchWarranty) >= 1) {
        $prepareToRemove[] = array(
            'attribute_code' => $eachAttr->getCode(),
            'attribute_id' => $eachAttr->getId()
        );
        echo "=============================================================================" . PHP_EOL;
        echo "    Attrbiute name: " . $eachAttr->getCode() . PHP_EOL;
        echo "    Attrbiute ID: " . $eachAttr->getId() . PHP_EOL;
        if(!$debug) {
            //delete attribute from database
            $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
            $setup->removeAttribute('catalog_product', $eachAttr->getCode());
        }
    }
}

var_dump($prepareToRemove);