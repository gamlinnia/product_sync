<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

function getAllWarrantyOption(){
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

    $allWarrantyOptions = array();
    foreach($attributeSetCollection as $each){
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
        foreach($attributes as $eachAttr){
//            echo $eachAttr['code'] . PHP_EOL;
            preg_match('/_warranty_/', $eachAttr['code'], $matchWarranty);
            //var_dump($matchWarranty);
            if(count($matchWarranty) >= 1){
//                echo $each->getAttributeSetName() . PHP_EOL;
                $attributeOptions = getAttributeOptions('attributeId', $eachAttr['attribute_id']);
                echo $eachAttr['code'] . PHP_EOL;
                if(isset($attributeOptions['options'])){
                    foreach($attributeOptions['options'] as $eachOption){
                        echo "    " . $eachOption['label'] . PHP_EOL;
                        $allWarrantyOptions[] = $eachOption['label'];
                    }
                }
            }
        }
    }

    return array_unique($allWarrantyOptions);
}
