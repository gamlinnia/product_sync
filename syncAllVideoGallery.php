#!/usr/bin/php -q
<?php

$config = json_decode(file_get_contents('config.json'), true);
$setting = json_decode(file_get_contents('setting.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('default');

$host = $setting['hostName'];
$param = array(
    'pageSize' => $setting['clonedParam']['pageSize'],
    'filterParams' => array(
        'updated_at' => array(
            'from' => $setting['clonedParam']['updated_at']
        )
    )
);
$remoteResponse = CallAPI('GET', $setting['restUrl'][$host] . 'getAllVideoGalleryInfos', array(), $param);
$videoGalleryList = $remoteResponse['dataCollection'];
$localVideoGalleryList = getVideoGalleryColletcion();
$needToImportList = compareVideoGalleryList($localVideoGalleryList, $videoGalleryList);

if (isset($needToImportList['gallery']) && count($needToImportList['gallery']) > 0) {
    foreach ($needToImportList['gallery'] as $eachVideoGallery) {
        importVideoToGalleryAndLinkToProduct($eachVideoGallery);
    }
}

if (isset($needToImportList['sku']) && count($needToImportList['sku']) > 0) {
    foreach ($needToImportList['sku'] as $eachMissingSkuVideoGallery) {
        $videogallery_url = $eachMissingSkuVideoGallery['videogallery_id'];
        $modelGallery = Mage::getModel('videogallery/videogallery')->load($videogallery_url, 'videogallery_url');
        $gallery_id = $modelGallery->getVideogalleryId();
        linkVideoGalleryToProduct ($gallery_id, $valueToFilter, $filterType='entity_id');
    }
}

