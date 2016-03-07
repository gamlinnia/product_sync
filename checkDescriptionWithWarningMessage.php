#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

$productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
/*$collection->addAttributeToFilter('description', array(
    array('like' => '% '. 'This product contains a chemical known to the State of California to cause cancer' .' %')
));*/

/*channels array*/
$channels = array(
    'newegg' => 'http://www.newegg.com/Product/Product.aspx?Item=',
);

/*foreach channel*/
foreach($channels as $channel => $url) {
    /*each excel for each channel */
    $arrayToExcel = array();
    $categoryArray = array();
    /*foreach product*/
    foreach($productCollection as $eachProduct){
        if ($eachProduct->getWarning()) {
            $sku = $eachProduct->getSku();
            $entity_id = $eachProduct->getId();
            $productName = $eachProduct->getName();
            $modelNumber = $eachProduct->getModelNumber();
            $attribute_set_id = $eachProduct->getAttributeSetId();
            $productCategorys = getProductCategoryNames($entity_id, 'entity_id', ' - ');
            $attrInfo = attributeSetNameAndId('attributeSetId', $attribute_set_id);
            echo 'SKU: ' . $sku . PHP_EOL;
            echo 'ID: ' . $entity_id . PHP_EOL;
            echo $eachProduct->getWarning() . PHP_EOL;

            if (!in_array($productCategorys, $categoryArray)) {
                $categoryArray[] = $productCategorys;
                $arrayToExcel[] = array(
                    'category' => $productCategorys,
                    'attribute_set' => $attrInfo['name']
                );
            }
        }
    }
    /*export all reviews with 1 or 2 rate to excel by channel*/
//    if(!empty($arrayToExcel)) {
//        $now = date('Y-m-d');
//        $fileName = $channel . '_' . $now . '.xls';
//        $sheetName = 'Sheet 1';
//        /*push file into fileList*/
//        $fileList[] = $fileName;
//        exportArrayToXlsx($arrayToExcel, array(
//            "filename" => $fileName,
//            "title" => $sheetName
//        ));
//    }
}

Zend_Debug::dump($arrayToExcel);

function getHylinkLinkOfEachProduct ($channel, $sku) {
    /* need to include ganon.php */
    switch ($channel) {
        case 'newegg' :
            $html = file_get_dom('http://www.newegg.com/Product/Product.aspx?Item=' . $sku);
            if(!empty($html)) {
                foreach ($html('#MfrContact') as $element) {
                    return $element->getPlainText();
                }
            }
            break;
    }
}
