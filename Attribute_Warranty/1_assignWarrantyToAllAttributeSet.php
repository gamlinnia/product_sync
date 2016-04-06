<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$model=Mage::getModel('eav/entity_setup','core_setup');

$debug = true;

$attributesNeedToAssign = array('_warranty_p' => 'manufacturer_warranty_parts', '_warranty_l' => 'manufacturer_warranty_labor');

foreach($attributesNeedToAssign as $regularEx => $eachNeedToAssign) {
    $attributeDataArray = $model->getAttribute('catalog_product', $eachNeedToAssign);
    $attributeId = $attributeDataArray['attribute_id'];

    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

    foreach ($attributeSetCollection as $each) {
        $attribute_set_name = $each->getAttributeSetName();
        echo $attribute_set_name . PHP_EOL;
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
        foreach ($attributes as $eachAttr) {
//            echo "    " . $eachAttr['code'] . PHP_EOL;
            preg_match('/' . $regularEx . '/', $eachAttr['code'], $matchWarranty);
            if (count($matchWarranty) >= 1) {
                echo "    " . $eachAttr['code'] . PHP_EOL;
                $attributeSetId = $each->getAttributeSetId();
                //$attributeSetId = $model->getAttributeSetId('catalog_product', $attribute_set_name);
                $attributeGroupDataArray = $model->getAttributeGroup('catalog_product', $attributeSetId, $attribute_set_name);
                if(!debug){
                    $model->addAttributeToSet('catalog_product', $attributeSetId, $attributeGroupDataArray["attribute_group_id"], $attributeId);
                }
                echo $attributeSetId . PHP_EOL;
                var_dump($attributeGroupDataArray);
                echo $attributeId . PHP_EOL;
                die();
            }
        }
    }
}