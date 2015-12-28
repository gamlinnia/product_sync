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
Mage::app('admin');

$app->post('/api/updateReviewStatus', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }
    updateReviewStatus($input['reviews'], $input['status']);
});

$app->post('/api/writeReviewToLocal', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }
    $productObject = getProductObject($input['product']['sku'], 'sku');
    $entity_id = $productObject->getId();
    $customerId = createCustomerNotExist($input['customer']);
    file_put_contents('review.log', 'customer id: ' . $customerId . PHP_EOL);
    createReviewAndRating($input['review'], $input['rating'], $entity_id, $customerId);
    file_put_contents('review.log', json_encode($input), FILE_APPEND);
    echo json_encode($input);
});

$app->post('/api/writeContactusFormToLocal', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }
//    $contactusFormObject = getProductObject($input['product']['sku'], 'sku');
//    $entity_id = $productObject->getId();
//    $customerId = createCustomerNotExist($input['customer']);
    file_put_contents('contactus.log', 'id: ' . $input['id'] . PHP_EOL);
    createContactusForm($input);
    file_put_contents('contactus.log', json_encode($input), FILE_APPEND);
    echo json_encode($input);
});

$app->post('/api/massDeleteContactusFormFromLocal', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }
   // file_put_contents('contactus.log', 'id: ' . $input['id'] . PHP_EOL);
    massDeleteContactusForm($input);
    //file_put_contents('contactus.log', json_encode($input), FILE_APPEND);
    //echo json_encode($input);
});

$app->post('/api/syncWithNeIm', function () {
    global $input;
    $parsedProduct = array();
    foreach ($input as $attrKey => $attrValue) {
        if (!is_array($attrValue)) {
            echo $attrKey . ' ' . $attrValue . PHP_EOL;
            $parsedProduct[$attrKey] = getAttributeValueFromOptions('attributeName', $attrKey, $attrValue);
            echo getAttributeValueFromOptions('attributeName', $attrKey, $attrValue) . PHP_EOL;
        }
    }
    $response = array(
        'parsedProduct' => $parsedProduct,
        'originalInput' => $input
    );
    echo json_encode($response);
});

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
        $downloadableResponse[] = array(
            'sku' => $productInfo['sku'],
            'files' => $downloadableInfo
        );
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
        'downloadables' => $downloadableResponse
    ));
});

$app->get('/api/getAttributePropertyMappingTable', function () {
    $filePath = '../rel/property_attribute_mapping_table.xlsx';
    $excelDataArray = parseXlsxIntoArray($filePath, 0, 3);
    echo json_encode($excelDataArray);
});

$app->get('/api/getAttributeSetAndSubcategoryMappingTable', function () {
    echo json_encode(getAttributeSetAndSubcategoryMappingTable('../rel/property_attribute_mapping_table.xlsx'));
});

$app->get('/api/getMappedAttributeSetOrSubcategory', function () use ($app) {
    $params = $app->request()->params();
    if (!isset($params['inputValue']) || !isset($params['inputType'])) {
        echo json_encode(array(
            'message' => 'params are missing'
        ));
        return;
    }
    $response = getMappedAttributeSetOrSubcategory('../rel/property_attribute_mapping_table.xlsx', $params['inputValue'], $params['inputType']);
    echo json_encode($response);
});

$app->get('/api/getAllVideoGalleryInfos', function () {
    $response = getVideoGalleryColletcion();
    echo json_encode(array(
        'status' => 'success',
        'count' => count($response),
        'dataCollection' => $response
    ));
});

$app->run();
