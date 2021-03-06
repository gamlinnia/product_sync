<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$model=Mage::getModel('eav/entity_setup','core_setup');

$attributeDataArray=$model->getAttribute('catalog_product','brand');
$attributeId = $attributeDataArray['attribute_id'];

$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

foreach($attributeSetCollection as $each){
    $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
    foreach($attributes as $eachAttr){
        preg_match('/_brand$/', $eachAttr['code'], $matchBrand);
        if(count($matchBrand) >= 1){
            //remove 'brand' attribute from 'General' group
            Mage::getModel('catalog/product_attribute_set_api')->attributeRemove($attributeId, $each->getId());

            //assign 'brand' attribute to specific attribute set
            $attribute_set_name = $each->getAttributeSetName();
            //echo $attribute_set_name . PHP_EOL;
            $attributeSetId=$model->getAttributeSetId('catalog_product',$attribute_set_name);
            $attributeGroupDataArray=$model->getAttributeGroup('catalog_product',$attributeSetId,$attribute_set_name);
            $model->addAttributeToSet('catalog_product',$attributeSetId,$attributeGroupDataArray["attribute_group_id"],$attributeId);
        }
    }
}
