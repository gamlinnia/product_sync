<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$model=Mage::getModel('eav/entity_setup','core_setup');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

// regular expression => new attribute code
$attributesNeedToAssign = array(
    '_power$' => 'power_watts',
    'power_consumption$' => 'power_watts',
    '_power_supply$' => 'power_voltage',
    '_over_volta' => 'psu_ovp',
    '_over_power' => 'psu_ovp'
);

$count = 0;
foreach($attributesNeedToAssign as $regularEx => $eachNeedToAssign) {
    echo 'ATTRIBUTE: ' . $eachNeedToAssign . PHP_EOL;
    //new attribute data and id
    $attributeDataArray = $model->getAttribute('catalog_product', $eachNeedToAssign);
    $attributeId = $attributeDataArray['attribute_id'];

    // attribute set collection
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

    foreach ($attributeSetCollection as $each) {
        $attributeSetName = $each->getAttributeSetName();
        $attributeSetId = $each->getAttributeSetId();
        //get all attributes for this attribute set
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
        foreach ($attributes as $eachAttr) {
            /* exclude lists */
            $excludeArray = array(
                'power_watts' => array('a04320_power_supply_over_power')
            );

            if ($eachAttr['type'] != 'select') {
                continue;
            }

            preg_match('/' . $regularEx . '/', $eachAttr['code'], $matchArray);
            if (count($matchArray) >= 1) {
                if (isset($excludeArray[$eachNeedToAssign]) && in_array($eachAttr['code'], $excludeArray[$eachNeedToAssign])) {
                    echo 'excluded the following attribute' . $eachAttr['code'] . PHP_EOL;
                } else {
                    $count++;
                    echo $attributeSetName. PHP_EOL;
                    echo "    " . $eachAttr['code'] . 'type: ' . $eachAttr['type'] . PHP_EOL;
                    $attributeGroupDataArray = $model->getAttributeGroup('catalog_product', $attributeSetId, $attributeSetName);
                    if(!$debug){
                        $model->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupDataArray["attribute_group_id"], $attributeId);
                    }
                }
            }
        }
    }
}

echo "Total: " . $count . " records.". PHP_EOL;