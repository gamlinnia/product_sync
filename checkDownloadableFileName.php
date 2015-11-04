<?php
#!/usr/bin/php -q

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$productCollection=Mage::getModel('catalog/product')->getCollection();

$response = array(
    'user_manual' => null,
    'driver' => null,
    'firmware' => null
);

$downloadables = array(
    'user_manual' => 'usermanuals/usermanuals',
    'driver' => 'drivers/drivers',
    'firmware' => 'firmware/firmware'
);
$result = array();

foreach($productCollection as $eachProduct) {
    foreach ($downloadables as $downloadType => $relativeModel) {
        $objectArray = Mage::getModel($relativeModel)->getCollection()->addFieldToFilter('product_id', $eachProduct->getId());
        if (count($objectArray) > 0) {
            $response[$downloadType] = array();
            foreach ($objectArray as $object) {
                //var_dump($object);
                $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
                $filePath = $object->getFile();
                if (strpos($filePath, ',') || strpos($filePath, '(') || strpos($filePath, ')'))
                {
                    $result[] = $baseUrl.$filePath;
                    $filePath = Mage::getBaseDir('media') . DS . $filePath;
                    die($filePath);
                }
//                $object->setFile($filePath);
//                $object->save();
                var_dump($object);
                die();
//                $response[$downloadType][] = array(
//                    'base' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
//                    'file' => $object->getFile()
//                );
            }
        }
    }
}

//var_dump($response);
var_dump($result);