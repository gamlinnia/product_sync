<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

$attributesNeedToAssign = array('_manufacturer_warranty_p' => 'manufacturer_warranty_parts', '_manufacturer_warranty_l' => 'manufacturer_warranty_labor');

foreach($attributesNeedToAssign as $regularEx => $eachNeedToAssign){
    if($debug) {
        $productCollection = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('entity_id', array('gt'=>'1344'));
    }
    else{
        $productCollection = Mage::getModel('catalog/product')->getCollection()->setOrder('entity_id', 'desc');
    }
    foreach($productCollection as $each) {
        $product = Mage::getModel('catalog/product')->load($each->getId());
        echo "Prodcut ID: " . $product->getId() . PHP_EOL;

        $existWarrantyAttribute = $product->getData($eachNeedToAssign);
        if(!empty($existWarrantyAttribute)) {
            continue;
        }

        $attributeSetId = $product->getAttributeSetId();
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);

        $attributeCode = array();
        foreach ($attributes as $eachAttr) {
            preg_match('/'. $regularEx . '/', $eachAttr['code'], $matchWarranty);
            if (count($matchWarranty) >= 1) {
                $attributeCode = $eachAttr;
            }
        }

        $attribute_label = null;
        if ($attributeCode) {
            var_dump($attributeCode);
            if ($attributeCode['type'] == 'select') {
                $product_attribute_value = $product->getData($attributeCode['code']);
                if(!empty ($product_attribute_value)) {
                    $attribute_label = getAttributeValueFromOptions('attributeId', $attributeCode['attribute_id'], $product_attribute_value);
                }
            } else if ($attributeCode['type'] == 'text' || $attributeCode['type'] == 'textarea') {
                continue;
            } else {
                echo "    "  . $attributeCode['type'] . PHP_EOL;
            }

            if(!empty($attribute_label)) {
                echo "Attribute Label: " . $attribute_label;

                if($attributeCode['type'] == 'select'){
                    $new_attribute_options = getAttributeOptions('attributeName', $eachNeedToAssign);
                    foreach($new_attribute_options['options'] as $option){
                        if(strtolower($option['label']) == strtolower($attribute_label)){
                            $new_attribute_value = $option['value'];
                        }
                    }
                }
                else{
                    echo "    "  . $attributeCode['type'] . PHP_EOL;
                }
                echo '    New warranty attribute value: ' . $new_attribute_value . PHP_EOL;

                if(!$debug) {
                    try {
                        $product->setData($eachNeedToAssign, $new_attribute_value);
                        $product->setUrlKey(false);
                        $product->save();
                    } catch (exception $e) {
                        echo $e->getMessage() . PHP_EOL;
                    }
                }
                else{
                    echo $eachNeedToAssign . ": " . $new_attribute_value . PHP_EOL;
                }
            }
            else{
                echo "    No warranty attribute value." . PHP_EOL;
            }
        }
    }
}