<?php
/*
 * for color attribute now
 *
 * */
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

require_once 'lib/ganon.php';

$excludeArray = array('c19000_group_he_cables_color');

$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

foreach($attributeSetCollection as $each) {
    $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
    $attributeCode = array();
    foreach ($attributes as $eachAttr) {
        if (in_array($eachAttr['code'], $excludeArray)) {
            continue;
        }
        preg_match('/color$/', $eachAttr['code'], $matchColor);
        if (count($matchColor) >= 1) {
            if (strlen($eachAttr['code']) > 5) {
                if (isset($attributeCode[0])) {
                    echo "    Alert~~" . PHP_EOL;
                    return;
                }
                $attributeCode[0] = $eachAttr;
            } else {
                $attributeCode[1] = $eachAttr;
            }
        }
    }
    $attribute_value = null;
    if (count($attributeCode) == 2) {
        var_dump($attributeCode);
        //$setup = Mage::getResourceModel('catalog/setup','catalog_setup');
        //$attribute_code = $attributeCode[0]['code'];
        //$setup->removeAttribute('catalog_product',$attribute_code);
    }
}
