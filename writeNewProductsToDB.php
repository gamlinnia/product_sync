#!/usr/bin/php -q
<?php

$setting = json_decode(file_get_contents('setting.json'), true);
require_once '../public_html/app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app();

$host = $setting['hostName'];
$param = array(
    'pageSize' => $setting['clonedParam']['pageSize'],
    'filterParams' => array(
        'updated_at' => array(
            'from' => $setting['clonedParam']['updated_at']
        )
    )
);
$productInfoJson = CallAPI('POST', $setting['restUrl'][$host] . 'getProductInfosToSync', array(), $param);
file_put_contents('log.txt', json_encode($param) . PHP_EOL, FILE_APPEND);
$productInfoArray = json_decode(json_encode($productInfoJson), true);

if (!(isset($productInfoArray['status']) && $productInfoArray['status'] == 'success')) {
    echo json_encode(array(
        'param' => $param
    ));
    file_put_contents('log.txt', 'Get Json File Error', FILE_APPEND);
    return;
}

try{
    $count = 0;
    foreach ($productInfoArray['data'] as $key => $productInfo) {
        $product = Mage::getModel('catalog/product');
        file_put_contents('log.txt', "************* " . $productInfo['direct']['sku'] . ' ' . $productInfo['dontCare']['updated_at'] . " *************" . PHP_EOL, FILE_APPEND);
        $readyToImportProductInfo = parseBackClassifiedProductAttributes($productInfo);

        foreach ($readyToImportProductInfo as $attrKey => $attrValue) {
//            file_put_contents('log.txt', $attrKey . ': ' . $attrValue . PHP_EOL, FILE_APPEND);
            $product->setData($attrKey, $attrValue);
        }

        $websiteId = Mage::app()->getWebsite()->getWebsiteId();
        $product->setWebsiteIds(array($websiteId))
            ->setCreatedAt(strtotime('now')) //product creation time
            ->setUpdatedAt(strtotime('now')); //product update time

        $product->save();
        $setting['clonedParam']['updated_at'] = $productInfo['dontCare']['updated_at'];
        $count++;
    }

//    $product->setSku("ABC123");
//    $product->setName("Type 7 Widget");
//    $product->setDescription("This widget will give you years of trouble-free widgeting.");
//    $product->setShortDescription("High-end widget.");
//    $product->setPrice(70.50);
//    $product->setTypeId('simple');
//    $product->setAttributeSetId(9); // need to look this up
//    $product->setWeight(1.0);
//    $product->setTaxClassId(2); // taxable goods
//    $product->setVisibility(4); // catalog, search
//    $product->setStatus(1); // enabled

    if ($count == count($productInfoArray['data']) && count($productInfoArray['data']) > 0) {
        file_put_contents('setting.json', json_encode($setting));
        echo json_encode(array(
            'message' => 'success'
        ));
    }

} catch (Exception $e) {
    var_dump($e->getMessage());
}
