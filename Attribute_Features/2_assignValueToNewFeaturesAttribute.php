<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

// regular expression => new attribute code
$attributesNeedToAssign = array('_feature[s]?$' => 'features');

$count = 0;
foreach($attributesNeedToAssign as $regularEx => $eachNeedToAssign){

    $productCollection = Mage::getModel('catalog/product')->getCollection()->setOrder('entity_id', 'desc');

    foreach($productCollection as $each) {
        $product = Mage::getModel('catalog/product')->load($each->getId());
        $productId = $product->getId();
        $existFeaturesAttribute = $product->getData($eachNeedToAssign);
        if(!empty($existFeaturesAttribute)) {
            continue;
        }

        $attributeSetId = $product->getAttributeSetId();
        $attributes = Mage::getModel('catalog/product_attribute_api')->items($attributeSetId);

        $origAttributeValue = '';
        $origAttributeCode = '';
        foreach ($attributes as $eachAttr) {
            $origAttributeCode = $eachAttr['code'];
            preg_match('/'. $regularEx . '/', $origAttributeCode, $matchArray);
            if (count($matchArray) >= 1) {
                $count++;
                $origAttributeValue = $product->getData($origAttributeCode);
            }
        }

        if ($origAttributeValue) {
            if(!$debug) {
                try {
                    $product->setData($eachNeedToAssign, $origAttributeValue);
                    $product->setUrlKey(false);
                    $product->save();
                } catch (exception $e) {
                    echo $e->getMessage() . PHP_EOL;
                }
            }
            else{
                echo "Prodcut ID: " . $productId . PHP_EOL;
                echo "    " . $origAttributeCode . ": " . $origAttributeValue . PHP_EOL;
            }
        }

    }
}

echo "Total: " . $count . " records.". PHP_EOL;