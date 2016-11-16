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
    $fileList = getDownloadableFileList();
    $header = array('Token: rosewill');
    $data = array(
        'media_url' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
        'file_list' => $fileList
    );
    $response = CallAPI(
        'POST',
        $remoteAPI,
        $header,
        $data,
        null
    );
    var_dump($response);
    $remoteMediaUrl = $response['media_url'];
    $localNeedToAdd = $response['local_need_to_add'];
    getRemoteDownloadableFileAndSaveToLocal($localNeedToAdd, $remoteMediaUrl);
}