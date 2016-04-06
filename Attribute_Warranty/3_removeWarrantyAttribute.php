<?php
/*
 * for color attribute now
 *
 * */
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection');

$prepareToRemove = array();
foreach($attributeSetCollection as $each) {
    $attributes = Mage::getModel('catalog/product_attribute_api')->items($each->getId());
    $attributeCode = array();
    foreach ($attributes as $eachAttr) {
        preg_match('/_brand$/', $eachAttr['code'], $matchBrand);
        if (count($matchBrand) >= 1) {
            $prepareToRemove[] = array(
                'attribute_set_id' => $each->getId(),
                'attribute_set_name' => $each->getAttributeSetName(),
                'attribute_code' => $eachAttr['code'],
                'attribute_id' => $eachAttr['attribute_id']
            );
            echo "=============================================================================" . PHP_EOL;
            echo "Attrbiute set name: " . $each->getAttributeSetName() . PHP_EOL;
            echo "Attrbiute set ID: " . $each->getId() . PHP_EOL;
            echo "Attrbiute name: " . $eachAttr['code'] . PHP_EOL;
            echo "Attrbiute ID: " . $eachAttr['attribute_id'] . PHP_EOL;
            //delete attribute from database
            $setup = Mage::getResourceModel('catalog/setup','catalog_setup');
            $setup->removeAttribute('catalog_product', $eachAttr['code']);
        }
    }

}

var_dump($prepareToRemove);
file_put_contents('remove_brand_attribute.txt', json_encode($prepareToRemove));