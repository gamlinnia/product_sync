#!/usr/bin/php -q
<?php

ini_set("memory_limit","2048M");

$config = json_decode(file_get_contents('config.json'), true);
$setting = json_decode(file_get_contents('setting.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');

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
$productInfoArray = json_decode(json_encode($productInfoJson), true);

if (!(isset($productInfoArray['status']) && $productInfoArray['status'] == 'success')) {
    echo json_encode(array(
        'param' => $param,
        'url' => $setting['restUrl'][$host] . 'getProductInfosToSync'
    ));
    file_put_contents('log.txt', 'Get Json File Error', FILE_APPEND);
    return;
}

try{
    /* debug == false，才執行product sync  */
    $count = 0;
    foreach ($productInfoArray['data'] as $key => $productInfo) {

        $productObject = Mage::getModel('catalog/product')->load(
            Mage::getModel('catalog/product')->getIdBySku( $productInfo['direct']['sku'] )
        );

        $productExists = true;
        if (!$productObject->getId()) {
            $productExists = false;
        }
        $readyToImportProductInfo = parseBackClassifiedProductAttributes($productInfo);

        foreach ($readyToImportProductInfo as $attrKey => $attrValue) {
            if (!empty($attrValue)) {
                if (is_array($attrValue)) {
                    echo "array key: $attrKey" . PHP_EOL;
                    var_dump($attrValue);
                } else {
                    echo "Set attr key: $attrKey to $attrValue" . PHP_EOL;
                }
            }
            $productObject->setData($attrKey, $attrValue);
        }

        echo 'product saving...' . $productInfo['direct']['sku'] . PHP_EOL;
        $productObject->setWebsiteIds(getAllWebisteIds())
            ->setCreatedAt(strtotime('now')) //product creation time
            ->setUpdatedAt(strtotime('now')) //product update time
            ->save();
        echo 'product saved' . PHP_EOL;

        changeToInStockAndSetQty($productInfo['direct']['sku'], 'sku');
        setProductCategoryIds($productInfo['direct']['sku'], 'sku', $productInfo['dontCare']['category']);
        $setting['clonedParam']['updated_at'] = $productInfo['dontCare']['updated_at'];
        $count++;
        sleep(rand(2, 4));
    }
    if (isset($config['debug']) && $config['debug']) {
        var_dump($productInfoArray);
    }

    /* deal with image uploading */
    foreach ($productInfoArray['imgs'] as $imageObject) {
        $sku = $imageObject['sku'];
        $product = Mage::getModel('catalog/product')->load(
            Mage::getModel('catalog/product')->getIdBySku($sku)
        );
        $productId = $product->getId();
        if (!$productId) {
            echo 'no product id get' . PHP_EOL;
            exit(0);
        }
        $imagesInfoArray = $imageObject['images'];
        $localImages = getImagesUrlOfProduct($sku, 'sku');

        $imagesToBeUploadOrDelete = compareImageWithRemoteIncludeDelete($localImages, $imagesInfoArray);
        echo 'sku: ' . $sku . 'processing images now' . PHP_EOL;
        var_dump($imagesToBeUploadOrDelete);

        $uploadStatus = uploadAndDeleteImagesWithPositionAndLabel($imagesToBeUploadOrDelete, $sku, 'sku', $config);

    }

    /* deal with downloadable files */
    foreach ($productInfoArray['downloadables'] as $downloadableObject) {
        $sku = $downloadableObject['sku'];
        echo 'sku: ' . $sku . 'processing downloadable files now' . PHP_EOL;
        file_put_contents('downloadablesync.log', 'sku: ' . $sku . 'processing downloadable files now', FILE_APPEND);
        $downloadableInfoArray = $downloadableObject['files'];
        $localDownloadables = getDownloadableUrls($sku, 'sku');
        $downloadableToBeUploadOrDelete = compareDownloadableWithRemoteIncludeDelete($localDownloadables, $downloadableInfoArray);
        file_put_contents('downloadablesync.log', json_encode($downloadableToBeUploadOrDelete), FILE_APPEND);

        $count = count($localDownloadables);
        echo "$count local downloadable files $sku" . PHP_EOL;
        foreach ($localDownloadables as $each) {
            echo $each['basename'] . PHP_EOL;
        }
        $count = count($downloadableInfoArray);
        echo "$count remote downloadable files $sku" . PHP_EOL;
        foreach ($downloadableInfoArray as $each) {
            echo $each['basename'] . PHP_EOL;
        }
        $count = count($downloadableToBeUploadOrDelete['add']) + count($downloadableToBeUploadOrDelete['edit']) + count($downloadableToBeUploadOrDelete['delete']);
        echo "$count to be uploaded downloadable files $sku" . PHP_EOL;

        if ($count > 0) {
            var_dump($downloadableToBeUploadOrDelete);
            file_put_contents('downloadablesync.log', "$count to be uploaded downloadable files $sku", FILE_APPEND);
        }
        $uploadDownloadableStatus = uploadAndDeleteDownloadFiles($downloadableToBeUploadOrDelete, $sku, 'sku', $config);
        if (!$uploadDownloadableStatus) {
            echo json_encode(array('message' => 'something wrong'));
        }
    }


    $response = array(
        'message' => 'Product info sync success'
    );
    if (isset($config['debug']) && $config['debug']) {
        $response['debug'] = true;
    }
    file_put_contents('setting.json', json_encode($setting));
    echo json_encode($response) . PHP_EOL;


    echo 'Last Product updated_at is ' . $productInfoArray['data'][count($productInfoArray['data'])-1]['dontCare']['updated_at'] . PHP_EOL;

} catch (Exception $e) {
    sendMailWithDownloadUrl('product sync exception', null, array(
        'to' => array('gamlinnia@hotmail.com', 'Tim.H.Huang@newegg.com')
    ));
    var_dump($e->getMessage());
}
