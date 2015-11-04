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
                if (strpos($filePath, ',') || strpos($filePath, '(') || strpos($filePath, ')')) {
                    $result[] = $baseUrl . $filePath;
                    $fileFullPath = Mage::getBaseDir('media') . DS . $filePath;
                    if (file_exists($fileFullPath)) {
                        echo 'oh ya!!! has file, don\'t worry.' . $filePath . PHP_EOL;
                    } else {
                        $newFilePath = preg_replace(
                            array(
                                '/__/',
                                '/[ (),+]/'
                            ),
                            array(
                                '_',
                                '_'
                            ),
                            $filePath
                        );
                        echo $object->getProductId() . PHP_EOL;
                        echo "org path" . $filePath . PHP_EOL;
                        echo $newFilePath . PHP_EOL;
                        if (file_exists(Mage::getBaseDir('media') . DS . $newFilePath)) {
                            echo 'new path works' . PHP_EOL;
                            echo 'Please correct path to: ' . $newFilePath;
//                            $object->setFile($newFilePath);
//                            $object->save();
                        } else {
                            echo 'oh shit!' . PHP_EOL;
                        }
                    }
                }
//
                //var_dump($object);
            }
        }
    }
}

//var_dump($response);
//var_dump($result);