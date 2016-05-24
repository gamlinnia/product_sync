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

$app->post('/api/deleteAwsReview', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }
    $reviewId = getSpecificReview($input['review']);
    if ($reviewId) {
        try {
            $model = Mage::getModel('review/review')->load($reviewId);
            $model->delete();
        } catch (Mage_Core_Exception $e) {
            echo json_encode(array('message' => $e->getMessage()));
        } catch (Exception $e){
            var_dump($e);
        }
        echo json_encode(array(
            'status' => 'success',
            'message' => 'success'
        ));
    } else {
        echo json_encode(array(
            'status' => 'success',
            'message' => 'no review deleted.'
        ));
    }
});

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
    echo json_encode($input);
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
    file_put_contents('review.log', json_encode($input), FILE_APPEND);
    createReviewAndRating($input['review'], $input['rating'], $entity_id, $customerId);
    echo json_encode($input);
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
    if(!empty($input['contactus'])) {
        createContactusForm($input['contactus']);
        echo json_encode($input);
    }
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
    if(!empty($input['contactus'])) {
        massDeleteContactusForm($input['contactus']);
        echo json_encode($input);
    }
});

$app->post('/api/getCookies', function  () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }
    $channel_sku = $input['channel_sku'];
    $product_url = $input['product_url'];

    $html = CallAPI('GET', $product_url);

    preg_match('/<script type=\"text\/javascript\" src=\"\/(wf-[^>]+.js)\"/', trim($html), $match);
    $js_file = $match[1];
    $base_url = parse_url($product_url);
    $second_url = $base_url['scheme'] . "://" .  $base_url['host'] . "/" . $js_file;
    $js_headers = get_headers($second_url, 1);
    $third_url = $base_url['scheme'] . "://" .  $base_url['host'] . $js_headers['X-JU'];
    $X_AH = $js_headers['X-AH'];

    $data = 'p=%7B%22appName%22%3A%22Netscape%22%2C%22platform%22%3A%22Win32%22%2C%22cookies%22%3A1%2C%22syslang%22%3A%22zh-TW%22%2C%22userlang%22%3A%22zh-TW%22%2C%22cpu%22%3A%22%22%2C%22productSub%22%3A%2220030107%22%2C%22setTimeout%22%3A0%2C%22setInterval%22%3A0%2C%22plugins%22%3A%7B%220%22%3A%22ShockwaveFlash%22%2C%221%22%3A%22WidevineContentDecryptionModule%22%2C%222%22%3A%22ChromePDFViewer%22%2C%223%22%3A%22NativeClient%22%2C%224%22%3A%22ChromePDFViewer%22%7D%2C%22mimeTypes%22%3A%7B%220%22%3A%22ShockwaveFlashapplication%2Fx-shockwave-flash%22%2C%221%22%3A%22ShockwaveFlashapplication%2Ffuturesplash%22%2C%222%22%3A%22WidevineContentDecryptionModuleapplication%2Fx-ppapi-widevine-cdm%22%2C%223%22%3A%22application%2Fpdf%22%2C%224%22%3A%22NativeClientExecutableapplication%2Fx-nacl%22%2C%225%22%3A%22PortableNativeClientExecutableapplication%2Fx-pnacl%22%2C%226%22%3A%22PortableDocumentFormatapplication%2Fx-google-chrome-pdf%22%7D%2C%22screen%22%3A%7B%22width%22%3A1440%2C%22height%22%3A900%2C%22colorDepth%22%3A24%7D%2C%22fonts%22%3A%7B%220%22%3A%22Calibri%22%2C%221%22%3A%22Cambria%22%2C%222%22%3A%22Constantia%22%2C%223%22%3A%22LucidaBright%22%2C%224%22%3A%22Georgia%22%2C%225%22%3A%22SegoeUI%22%2C%226%22%3A%22Candara%22%2C%227%22%3A%22TrebuchetMS%22%2C%228%22%3A%22Verdana%22%2C%229%22%3A%22Consolas%22%2C%2210%22%3A%22LucidaConsole%22%2C%2211%22%3A%22LucidaSansTypewriter%22%2C%2212%22%3A%22CourierNew%22%2C%2213%22%3A%22Courier%22%7D%7D';

    $ch = curl_init($third_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
    $result = curl_exec($ch);
    curl_close($ch);

    //get cookie
    $cookie_file = file_get_contents('cookie.txt');

    $data = explode("\n", $cookie_file);
    $cookies = array();
    foreach($data as $index => $line) {
        if($index >= 4 && $index < 9){
            $str = explode("\t", $line);
            $cookies[] = $str[5] . "=" . $str[6];
            //var_dump($str);
        }
    }
    $cookie = implode(';', $cookies);

    echo json_encode(array(
        'cookie' => $cookie
    ));
});

$app->post('/api/postProductJsonToLocal', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }

    /* save product json to local files in dev environment. */
    $dir = './productJson/';
    if ($headers['Host'] == 'rwdev.buyabs.corp') {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                echo 'Error creating Directory.' . PHP_EOL;
                return;
            }
        }
        file_put_contents($dir . $input['ItemNumber'], json_encode($input));
    }

    echo json_encode(array(
        'message' => 'Success'
    ));

});

$app->post('/api/writeReviewCommentToLocal', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }

    $data = $input['data'];
    $result = writeReviewCommentToLocal($data);
    echo json_encode($result);
});

$app->post('/api/removeReviewCommentFromLocal', function () {
    global $input;
    global $app;
    $headers = $app->request()->headers();
    if (!isset($headers['Token']) || $headers['Token'] != 'rosewill') {
        echo json_encode(array(
            'message' => 'auth error.'
        ));
        return;
    }

    $data = $input['data'];
    $result = removeReviewCommentFromLocal($data);
    echo json_encode($result);
});

$app->run();
