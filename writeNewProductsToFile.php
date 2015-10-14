#!/usr/bin/php -q
<?php
date_default_timezone_set('Asia/Taipei');
file_put_contents('log.txt', 'run at : ' . strtotime('now') . PHP_EOL, FILE_APPEND);
$config = json_decode(file_get_contents('config.json'), true);
$setting = json_decode(file_get_contents('setting.json'), true);
require_once $config['magentoDir'] . DS . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

/* 判斷dest server是否已add to product, 並更改setting.json的頁數 */
foreach ($setting['hostsInfo'] as $hostIndex => $hostInfo) {
    /* 建立each host存放json的資料夾 */
    if ( !file_exists($hostInfo['hostName'] . DS) ) {
        mkdir($hostInfo['hostName']);
    }
    $complete = false;
    if ( file_exists($hostInfo['hostName'] . DS . 'done') ) {
        /* delete host files */
        unlink($hostInfo['hostName'] . DS . 'done');
        $complete = true;
    }
    if ( !file_exists($hostInfo['hostName'] . DS . $hostInfo['hostName']) || $complete ) {

        /* if file has been cloned. */
        $productInfoList = getProductInfoFromMagento(array(
            'updated_at' => array(
                'from'  => $hostInfo['updated_at']
            )
        ), $setting['pageSize']);

        /* 分類成3類 */
        $classifiedProductList = array();
        foreach ($productInfoList['productsInfo'] as $key => $productInfo) {
            file_put_contents('log.txt', 'dealed SKU: ' . $productInfo['sku'] . PHP_EOL, FILE_APPEND);
            $classifiedProductList[] = classifyProductAttributes($productInfo);
            $setting['hostsInfo'][$hostIndex]['updated_at'] = $productInfo['updated_at'];
        }

        file_put_contents('setting.json', json_encode($setting));

        /* 將needToBeParsed的attr從id轉換成string value */
        $parsedClassfiedProductList = array();
        foreach ($classifiedProductList as $classifiedProductInfo) {
            $parsedClassfiedProductList[] = parseClassifiedProductAttributes($classifiedProductInfo);
        }
        echo json_encode($parsedClassfiedProductList) . PHP_EOL . PHP_EOL;

        /* 將檔案儲存到檔案內 */
        file_put_contents($hostInfo['hostName'] . DS . $hostInfo['hostName'], json_encode($parsedClassfiedProductList));
    }
}


