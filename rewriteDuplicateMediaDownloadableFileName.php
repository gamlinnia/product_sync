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
            foreach ($objectArray as $object) {
                $originFile = $object->getFile();
                preg_match('/(\/var\/www\/rosewill\/public_html\/media\/)+/', $originFile, $match);
                if ($match) {
                    echo 'origin file: ' . $originFile . PHP_EOL;
                    $correctedFile = preg_replace('/(\/var\/www\/rosewill\/public_html\/media\/)+/', '', $originFile);
                    echo 'correct to: ' . $correctedFile . PHP_EOL;
                    $object->setFile($correctedFile);
                    $object->save();
                }
            }
        }
    }
}

//var_dump($response);
//var_dump($result);