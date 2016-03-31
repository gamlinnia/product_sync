<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

require_once 'lib/ganon.php';

function getAllColorOption(){
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

    $allColorOptions = array();
    foreach($attributeSetCollection as $each){
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
        foreach($attributes as $eachAttr){
            //echo $eachAttr['code'] . PHP_EOL;
            preg_match('/_color$/', $eachAttr['code'], $matchColor);
            //var_dump($matchColor);
            if(count($matchColor) >= 1){
                //echo $each->getAttributeSetName() . PHP_EOL;
                $attributeOptions = getAttributeOptions('attributeId', $eachAttr['attribute_id']);
                if(isset($attributeOptions['options'])){
                    foreach($attributeOptions['options'] as $eachOption){
                        $allColorOptions[] = $eachOption['label'];
                    }
                }
            }
        }
    }

    return array_unique($allColorOptions);
}
