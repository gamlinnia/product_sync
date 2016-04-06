<?php
/*
 * for color attribute now
 *
 * */
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

$attributesNeedToRemove = array('_manufacturer_warranty_p', '_manufacturer_warranty_l');

$attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection');

$prepareToRemove = array();
foreach($attributeCollection as $eachAttr) {
    foreach($attributesNeedToRemove as $eachAttrNeedToRemove) {
        $attributeCode  = $eachAttr->getAttributeCode();
        $attributeId = $eachAttr->getId();
        preg_match('/' . $eachAttrNeedToRemove .'/', $attributeCode, $matchWarranty);
        if (count($matchWarranty) >= 1) {
            $prepareToRemove[] = array(
                'attribute_code' => $attributeCode,
                'attribute_id' => $attributeId
            );
            echo "=============================================================================" . PHP_EOL;
            echo "    Attrbiute name: " . $attributeCode . PHP_EOL;
            echo "    Attrbiute ID: " . $attributeId . PHP_EOL;
            if(!$debug) {
                //delete attribute from database
                $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
                $setup->removeAttribute('catalog_product', $eachAttr['code']);
            }
        }
    }

}

var_dump($prepareToRemove);
//file_put_contents('remove_warranty_attribute.txt', json_encode($prepareToRemove));