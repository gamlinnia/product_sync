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
$attributesNeedToAssign = array('_feature' => 'features');

foreach($attributesNeedToAssign as $regularEx => $eachNeedToAssign) {
    //new attribute data and id
    $attributeDataArray = $model->getAttribute('catalog_product', $eachNeedToAssign);
    $attributeId = $attributeDataArray['attribute_id'];

    // attribute set collection
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

    foreach ($attributeSetCollection as $each) {
        $attributeSetName = $each->getAttributeSetName();
        echo $attributeSetName . PHP_EOL;
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
        foreach ($attributes as $eachAttr) {
//            echo "    " . $eachAttr['code'] . PHP_EOL;
            preg_match('/' . $regularEx . '/', $eachAttr['code'], $matchArray);
            if (count($matchArray) >= 1) {
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