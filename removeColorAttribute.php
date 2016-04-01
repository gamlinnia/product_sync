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

$prepareToRemove = array();
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
                    echo "    More than one" . PHP_EOL;
                    $attributeCode[2] = $eachAttr;
                }
                $attributeCode[0] = $eachAttr;
            } else {
                $attributeCode[1] = $eachAttr;
            }
        }
    }
    $attribute_value = null;
    //delete
    if (count($attributeCode) == 2) {
//        $prepareToRemove[] = array(
//            'attribute_set_id' => $each->getId(),
//            'attribute_set_name' => $each->getAttributeSetName(),
//            'attribute_code' => $attributeCode[0]['code'],
//            'attribute_id' => $attributeCode[0]['attribute_id'],
//            'action' => 'delete'
//        );
        //$setup = Mage::getResourceModel('catalog/setup','catalog_setup');
        //$attribute_code = $attributeCode[0]['code'];
        echo "Attrbiute set name: " . $each->getAttributeSetName() . PHP_EOL;
        echo "Attrbiute set ID: " . $each->getId() . PHP_EOL;
        echo "Attrbiute name: " . $attributeCode[0]['code'] . PHP_EOL;
        echo "Attrbiute ID: " . $attributeCode[0]['attribute_id'] . PHP_EOL;
        echo "=============================================================================";
        Mage::getModel('catalog/product_attribute_set_api')->attributeRemove($attributeCode[0]['attribute_id'], $each->getId());
    } else if (count($attributeCode) > 2) {
//        $prepareToRemove[] = array(
//            'attribute_set_id' => $each->getId(),
//            'attribute_set_name' => $each->getAttributeSetName(),
//            'attribute_code' => $attributeCode[1]['code'],
//            'attribute_id' => $attributeCode[1]['attribute_id'],
//            'action' => 'remove'
//        );.
        echo "Attrbiute set name: " . $each->getAttributeSetName() . PHP_EOL;
        echo "Attrbiute set ID: " . $each->getId() . PHP_EOL;
        echo "Attrbiute name: " . $attributeCode[1]['code'] . PHP_EOL;
        echo "Attrbiute ID: " . $attributeCode[1]['attribute_id'] . PHP_EOL;
        echo "=============================================================================";
        Mage::getModel('catalog/product_attribute_set_api')->attributeRemove($attributeCode[0]['attribute_id'], $each->getId());
    } else {

    }
}
