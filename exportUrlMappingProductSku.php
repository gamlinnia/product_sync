<?php

/*log starting time*/
$now = new DateTime(null, new DateTimeZone('UTC'));
file_put_contents('crawlChannelReviews.log', "Process start at: " . $now->format('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

/*get config setting*/
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

/*product collection*/
$productCollection = Mage::getModel('catalog/product')
    ->getCollection()
    ->addAttributeToSelect('name')
    ->addAttributeToSelect('model_number');
$productCollection->setOrder('entity_id', 'desc');

/*channels array*/
$channels = array(
    'newegg' => 'http://www.newegg.com/Product/Product.aspx?Item=',
);

/*foreach channel*/
foreach($channels as $channel => $url) {
    /*each excel for each channel */
    $arrayToExcel = array();
    /*foreach product*/
    foreach($productCollection as $eachProduct){
        $sku = $eachProduct->getSku();
        $entity_id = $eachProduct->getId();
        $productName = $eachProduct->getName();
        $modelNumber = $eachProduct->getModelNumber();
        echo '#' . $count . ' SKU: ' . $sku . PHP_EOL;
        echo 'ID: ' . $entity_id . PHP_EOL;

        echo $hyperlink . PHP_EOL;
        $arrayToExcel[] = array(
            'sku' => $sku,
            'id' => $entity_id,
            'url_path' => $eachProduct->getUrlPath(),
            'product_path' => 'http://www.rosewill.com/catalog/product/view/id/' . $entity_id .'/',
            'hyperlink' => $hyperlink
        );
        $count++;
    }
    /*export all reviews with 1 or 2 rate to excel by channel*/
    if(!empty($arrayToExcel)) {
        $now = date('Y-m-d');
        $fileName = 'official_hyperlinks_to_sku.xls';
        $sheetName = 'Sheet 1';
        exportArrayToXlsx($arrayToExcel, array(
            "filename" => $fileName,
            "title" => $sheetName
        ));
    }
}
