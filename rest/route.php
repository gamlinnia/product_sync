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

require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
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

    /* if file has been cloned. */
    $productInfoList = getNextProductInfoFromMagento($filterParams, $pageSize);

    /* 分類成3類 */
    $classifiedProductList = array();
    $imgResponse = array();
    $downloadableResponse = array();
    $videoGalleryList = array();
    foreach ($productInfoList['productsInfo'] as $key => $productInfo) {
        $classifiedProductList[] = classifyProductAttributes($productInfo);

        $imagesArray = getImagesUrlOfProduct($productInfo['entity_id']);
        $imgResponse[] = array(
            'sku' => $productInfo['sku'],
            'images' => $imagesArray
        );

        $downloadableInfo = getDownloadableUrls($productInfo['sku'], 'sku');
        if (count($downloadableInfo) > 0) {
            $downloadableResponse[] = array(
                'sku' => $productInfo['sku'],
                'files' => $downloadableInfo
            );
        }

        $videoGalleryInfo = getVideoGalleryInfo($productInfo['sku'], 'sku');
        if (count($videoGalleryInfo) > 0) {
            $videoGalleryList[] = array(
                'sku' => $productInfo['sku'],
                'galleryInfo' => $videoGalleryInfo
            );
        }
    }

    /* 將needToBeParsed的attr從id轉換成string value */
    $parsedClassfiedProductList = array();
    foreach ($classifiedProductList as $classifiedProductInfo) {
        $parsedClassfiedProductList[] = parseClassifiedProductAttributes($classifiedProductInfo);
    }
    echo json_encode(array(
        'status' => 'success',
        'data' => $parsedClassfiedProductList,
        'imgs' => $imgResponse,
        'downloadables' => $downloadableResponse,
        'videoGalleryList' => $videoGalleryList
    ));
});

$app->get('/api/getAttributePropertyMappingTable', function () {
    $filePath = '../rel/property_attribute_mapping_table.xlsx';
    $excelDataArray = parseXlsxIntoArray($filePath, 0, 3);
    echo json_encode($excelDataArray);
});

$app->get('/api/getAttributeSetAndSubcategoryMappingTable', function () {
    $filePath = '../rel/property_attribute_mapping_table.xlsx';
    $excelDataArray = parseXlsxIntoArray($filePath, 1, 1);
    echo json_encode($excelDataArray);
});

$app->run();
