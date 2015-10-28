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
$videoGalleryListJson = CallAPI('GET', $setting['restUrl'][$host] . 'getAllVideoGalleryInfos', array(), $param);
$videoGalleryList = json_decode($videoGalleryListJson, true);

