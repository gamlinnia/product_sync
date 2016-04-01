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

//$product = Mage::getModel('catalog/product')->load(1770);
$productCollection = Mage::getModel('catalog/product')->getCollection();
foreach($productCollection as $each) {
    $product = Mage::getModel('catalog/product')->load($each->getId());
    echo "Prodcut ID: " . $product->getId() . PHP_EOL;
    $attribute_set_id = $product->getAttributeSetId();
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');
    $attributes = Mage::getModel('catalog/product_attribute_api')->items($attribute_set_id);

    $attributeCode = array();
    foreach ($attributes as $eachAttr) {
        preg_match('/color$/', $eachAttr['code'], $matchColor);
        if (count($matchColor) >= 1) {
            if (strlen($eachAttr['code']) > 5) {
                if (isset($attributeCode[0])) {
                    echo "    Alert~~" . PHP_EOL;
                    return;
                }
                $attributeCode[0] = $eachAttr;
            } else {
                $attributeCode[1] = $eachAttr;
            }
        }
    }

    if (count($attributeCode) == 2) {
        if ($attributeCode[0]['type'] == 'select') {
            $attribute_value = getAttributeValueFromOptions('attributeId', $attributeCode[0]['attribute_id'], $product->getData($attributeCode[0]['code']));
        } else if ($attributeCode[0]['type'] == 'text') {
            $attribute_value = $product->getData($attributeCode[0]['code']);
        } else {
            echo "    Multi-select." . PHP_EOL;
        }
    }

    if(!empty($attribute_value)){
        $new_attribute_code = $attributeCode[1]['code'];
        if($attributeCode[1]['type'] == 'select'){
            $new_attribute_options = getAttributeOptions('attributeId', $attributeCode[1]['attribute_id']);
            //var_dump($new_attribute_options);
            foreach($new_attribute_options['options'] as $option){
                if(strtolower($option['label']) == strtolower($attribute_value)){
                    $new_attribute_value = $option['value'];
                }
            }
        }
        else if ($attributeCode[1]['type'] == 'text'){
            $new_attribute_value = $attribute_value;
        }
        else{
            return;
        }
        echo '    New color attribute value: ' . $new_attribute_value . PHP_EOL;
        //$new_attribute_value =
        $product->setData($new_attribute_code, $new_attribute_value);
        $product->save();
    }
    else{
        echo "    No color attribute value." . PHP_EOL;
    }

}
