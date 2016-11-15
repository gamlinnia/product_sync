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
    case 'rwdev.buyabs.corp' :
        $remoteUrl = $restUrls['aws'];
        break;
}
$remoteAPIName = 'syncDownloadableFileList';
$remoteAPI = $remoteUrl . $remoteAPIName;
//Call API
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
    $fileList,
    null
);
//response contain lost file in local
/*
 * {
  "media_url": "http://rwdev.buyabs.corp/enterprise/public_html/media/",
  "local_need_to_add": {
    "downloadable/user_manuals/96-268-093_RHAF-15003_A_UM_0728_ol.pdf": [
      "Rosewill RHAF-15003 - 1400W Oil-Less Low Fat Air Fryer - 3.3 Quart (3.2 L), Black"
    ]
  }
}
 */
$data = json_decode($response, true);
$remoteMediaUl = $data['media_url'];
$localNeedToAdd = $data['local_need_to_add'];
var_dump($remoteMediaUl);
var_dump($localNeedToAdd);
//getRemoreDownloadableFileAndSaveToLocal($localNeedToAdd, $remoteMediaUl);