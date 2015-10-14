<?php

require 'Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app -> contentType('application/json');
$input = json_decode($app->request->getBody(), true);
$config = json_decode(file_get_contents('../config.json'), true);

/*CORS*/
require_once 'CORS.php';
/*常用 function*/
require_once ('tools.php');

require_once '../functions.php';
require_once '../' . $config['magentoDir'] . DS . 'app/Mage.php';
Mage::app();

$app->post('/api/getProductInfosToSync', function () {
    global $input;

    $paramsChecking = array('pageSize', 'filterParams');
    foreach ($paramsChecking as $paramCheck) {
        if ( !isset($input[$paramCheck]) ) {
            echo json_encode(array('message' => "param: $paramCheck is missing"));
            return;
        }
    }

    $pageSize = (int)$input['pageSize'];
    $filterParams = $input['filterParams'];
    /*    array(
            'updated_at' => array(
                'from'  => $hostInfo['updated_at']
            )) */

    /* if file has been cloned. */
    $productInfoList = getProductInfoFromMagento($filterParams, $pageSize);

    /* 分類成3類 */
    $classifiedProductList = array();
    foreach ($productInfoList['productsInfo'] as $key => $productInfo) {
        file_put_contents('log.txt', 'dealed SKU: ' . $productInfo['sku'] . PHP_EOL, FILE_APPEND);
        $classifiedProductList[] = classifyProductAttributes($productInfo);
    }

    /* 將needToBeParsed的attr從id轉換成string value */
    $parsedClassfiedProductList = array();
    foreach ($classifiedProductList as $classifiedProductInfo) {
        $parsedClassfiedProductList[] = parseClassifiedProductAttributes($classifiedProductInfo);
    }
    echo json_encode(array(
        'status' => 'success',
        'data' => $parsedClassfiedProductList
    ));
});

$app->run();
