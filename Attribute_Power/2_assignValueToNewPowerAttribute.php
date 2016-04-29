<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

$proceedArray = array(
    'power_watts' => array('c09140_fryers_power', 'c27150_rice_cookers_power', 'c30150_thermo_pot_power', 'c31230_toaster_oven_power', 'c03290_led_power_consumption'),
    'power_voltage' => array('c11090_power_supply', 'c13100_power_supply')
);

$count = 0;
foreach ($proceedArray as $newAttrCode => $matchedAttributeCode) {
    $productCollection = Mage::getModel('catalog/product')->getCollection()->setOrder('entity_id', 'desc');
    foreach($productCollection as $eachProduct) {
        $product = Mage::getModel('catalog/product')->load($eachProduct->getId());
        echo "Prodcut ID: " . $product->getId() . PHP_EOL;
        $existPowerAttribute = $product->getData($newAttrCode);
        if(!empty($existFeaturesAttribute)) {
            continue;
        }

        $attributeSetId = $product->getAttributeSetId();
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);

        $origAttributeValue = '';
        $origAttributeCode = '';
        foreach ($attributes as $eachAttr) {
            if (in_array($eachAttr['code'], $proceedArray[$newAttrCode])) {
                $count++;
                $origAttributeCode = $eachAttr['code'];
                $origAttributeValue = $product->getData($origAttributeCode);
                $origAttributeValueFromOption = getAttributeValueFromOptions('attributeName', $origAttributeCode, $product->getData($origAttributeCode));
                echo $origAttributeValueFromOption . PHP_EOL;
            }
        }

        if ($origAttributeValue) {
            echo "    " . $origAttributeCode . PHP_EOL;
            setAttributeValueToOptions($eachProduct, 'attributeName', $newAttrCode, $origAttributeValueFromOption, $debug);
        }

    }

}


echo "Total: " . $count . " records.". PHP_EOL;