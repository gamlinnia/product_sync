<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

$restUrls = array(
    'dev' => 'http://rwdev.buyabs.corp/rest/route.php/api/',
    'pre-prd' => 'http://rwdev.buyabs.corp:8001/rest/route.php/api/',
    'aws' => 'http://www.rosewill.com/rest/route.php/api/'
);

$localBaseUrl = Mage::getBaseUrl();
$parsedUrl = parse_url($localBaseUrl);
$remoteUrl = '';
switch ($parsedUrl['host']) {
    case 'www.rosewill.com' :
        $remoteUrl = $restUrls['dev'];
        break;
}

if (isset($remoteUrl) && !empty($remoteUrl)) {
    $remoteAPIName = 'syncDownloadableFileList';
    $remoteAPI = $remoteUrl . $remoteAPIName;
    $header = array('Token: rosewill');
    $response = CallAPI(
        'GET',
        $remoteAPI,
        $header,
        null,
        null
    );
//    var_dump($response);
    $remoteMediaUrl = $response['media_url'];

    $remoteFileList = $response['data'];
    $localFileList = getDownloadableFileList();

    $localNeedToAdd = arrayRecursiveDiff($remoteFileList, $localFileList);
    $localNeedToDelete = arrayRecursiveDiff($localFileList, $remoteFileList);

//    $localNeedToAdd = $response['local_need_to_add'];
//    $localNeedToDelete = $response['local_need_to_delete'];
    updateLocalFileList($localNeedToAdd, $remoteMediaUrl);
    updateLocalFileList($localNeedToDelete);
}