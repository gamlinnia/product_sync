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

//$attributesNeedToAssign = array('_manufacturer_warranty_p' => 'manufacturer_warranty_parts', '_manufacturer_warranty_l' => 'manufacturer_warranty_labor');

//exception case
$attributesNeedToAssign = array(
    'a01470_case_manufacturer_warra' => 'manufacturer_warranty_parts',
    'a01460_case_manufacturer_warra' => 'manufacturer_warranty_labor',
    'b01470_case_manufacturer_warra' => 'manufacturer_warranty_parts',
    'b01460_case_manufacturer_warra' => 'manufacturer_warranty_labor'
);

$exceptionArray = array(
    'a01_Gaming_Case',
    'b01_Computer_Case'
);

foreach($attributesNeedToAssign as $regularEx => $eachNeedToAssign) {
    $attributeDataArray = $model->getAttribute('catalog_product', $eachNeedToAssign);
    $attributeId = $attributeDataArray['attribute_id'];

    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

    foreach ($attributeSetCollection as $each) {
        $attributeSetName = $each->getAttributeSetName();
        if(!in_array($attributeSetName, $exceptionArray)){
            continue;
        }
        echo $attributeSetName . PHP_EOL;
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
        foreach ($attributes as $eachAttr) {
//            echo "    " . $eachAttr['code'] . PHP_EOL;
            preg_match('/' . $regularEx . '/', $eachAttr['code'], $matchWarranty);
            if (count($matchWarranty) >= 1) {
                echo "    " . $eachAttr['code'] . PHP_EOL;
                $attributeSetId = $each->getAttributeSetId();
                $attributeGroupDataArray = $model->getAttributeGroup('catalog_product', $attributeSetId, $attributeSetName);
                if(!$debug){
                    $model->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupDataArray["attribute_group_id"], $attributeId);
                }
            }
        }
    }
}