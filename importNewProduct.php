#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

if (!isset($argv[1])) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}

preg_match('/[\d]{2}-[\d]{3}-[\d]{3}/', $argv[1] , $match);
if (count($match) < 1) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}
$sku = $match[0];

/* save product json to local files in dev environment. */
$dir = './rest/productJson/';
if (!file_get_contents($dir . 'mappingAttrs.json')) {
    echo 'Error getting mapping table file.' . PHP_EOL;
    return;
}
if (!file_get_contents($dir . 'categoryMapToAttributeSet.json')) {
    echo 'Error getting category mapping table file.' . PHP_EOL;
    return;
}
if (!file_get_contents($dir . $sku)) {
    echo 'Error getting product json file.' . PHP_EOL;
    return;
}

$productJson = json_decode(file_get_contents($dir . $sku), true);
$mapTable = json_decode(file_get_contents($dir . 'mappingAttrs.json'), true);
$categoryMapToAttributeSet = json_decode(file_get_contents($dir . 'categoryMapToAttributeSet.json'), true);
var_dump($mapTable);
var_dump($categoryMapToAttributeSet);

/*get SubcategoryName in baseinfo*/
$subcategoryName = $productJson['baseinfo']['SubcategoryName'];
echo 'SubcategoryName: ' . $subcategoryName . PHP_EOL;
$mappedAttrSets = $categoryMapToAttributeSet[$subcategoryName];
echo 'map to attribute set names: ' . $mappedAttrSets . PHP_EOL;

/* check existence */
$collection = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('sku', $sku);
$productExists = false;
if ($collection->count() < 1) {
    echo 'whole new product' . PHP_EOL;
    $model = Mage::getModel('catalog/product');

    echo 'map to attribute set name: ' . $mappedAttrSet . PHP_EOL;
    $attrSetInfo = attributeSetNameAndId('attributeSetName', $mappedAttrSet);
    echo $mappedAttrSet . 'map to attr set id: ' . $attrSetInfo['id'] . PHP_EOL;
} else {
    $productId = $collection->getFirstItem()->getId();
    $model = Mage::getModel('catalog/product')->load($productId);
//    $model = Mage::getModel('catalog/product');
    $productExists = true;
    echo 'product exists' . PHP_EOL;
}

if (!$productExists) {
    /* map attribute set */
    $mappedAttrSetsArray = explode(',', $mappedAttrSets);
    if ( count($mappedAttrSetsArray) > 1 ) {
        do {
            /*透過 標準輸出 印出要詢問的內容*/
            fwrite(STDOUT, 'Enter attribute set name to import new product: ');
            /*抓取 標準輸入 的 內容*/
            $mappedAttrSet = trim(fgets(STDIN));
        } while (empty($mappedAttrSet));
        echo $mappedAttrSet . PHP_EOL;
    } else if (count($mappedAttrSetsArray) == 1) {
        $mappedAttrSet = $mappedAttrSetsArray[0];
    } else {
        echo 'no attribute set name map to subcategory: ' . $subcategoryName . PHP_EOL;
        return;
    }

    $model->setAttributeSetId($attrSetInfo['id'])
        ->setData('type_id', 'simple')
        ->setData('Model', $productJson['Model'])
        ->setData('status', '1')
        ->setData('tax_class_id', '0')
        ->setData('enable_rma', '0')
        ->setData('visibility', '4');
}

foreach ($mapTable as $bigProductInfoItem => $bigItemObject) {
    switch ($bigProductInfoItem) {
        case 'property' :
            /* get all attributes belong to a attribute set id */
            $attributes = Mage::getModel('catalog/product_attribute_api')->items($attrSetInfo['id']);
            foreach ($productJson['property'] as $eachProductPropertyObject) {
                foreach ($bigItemObject as $propertyObject) {
                    /* search if $propertyObject['AttrToMap'] exist in $attributes[]['code'] */
                    if ($eachProductPropertyObject['PropertyCode'] == $propertyObject['PropertyCode']) {
                        echo 'find property code match' . $propertyObject['PropertyCode'] . PHP_EOL;
                        foreach ($attributes as $eachAttrObject) {
                            if (in_array($eachAttrObject['code'], $propertyObject['AttrToMap'])) {
                                if (isset($eachProductPropertyObject['UserInputted']) && !empty($eachProductPropertyObject['UserInputted'])) {
                                    $model->setData($propertyObject['AttrToMap'], $eachProductPropertyObject['UserInputted']);
                                } else {
                                    $model->setData($propertyObject['AttrToMap'], $eachProductPropertyObject['ValueName']);
                                }
                                break;
                            }
                        }
                        break;
                    }
                }
            }
            break;
        default :
            foreach ($bigItemObject as $toBeMappedKey => $mapToAttr) {
                if ( !empty($productJson[$bigProductInfoItem][$toBeMappedKey]) ) {
                    $model->setData($mapToAttr, $productJson[$bigProductInfoItem][$toBeMappedKey]);
                }
            }
    }
    $specialBigItems = array('property');
}

Zend_Debug::dump($model->getData());

/*透過 標準輸出 印出要詢問的內容*/
fwrite(STDOUT, 'Are you sure to save this product information?');
/*抓取 標準輸入 的 內容*/
$sureToAction = trim(fgets(STDIN));

if (strtolower($sureToAction) == 'y') {
    $model->save();
}