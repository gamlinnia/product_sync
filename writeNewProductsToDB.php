<?php

$setting = json_decode(file_get_contents('setting.json'), true);
require_once '../public_html/app/Mage.php';
require_once 'functions.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$filePath = $setting['storeJsonDir'] . $setting['storeJsonFile'];
$command = 'scp -P 22222 ll5p@192.168.4.15:/home/ll5p/html/community/cron/' . $filePath . ' ' . $setting['storeJsonDir'];
echo $command . PHP_EOL . PHP_EOL;
exec($command);

$productInfoArray = json_decode(getJsonFile($setting), true);
$product = Mage::getModel('catalog/product');

try{


//    foreach ($productInfoArray as $key => $productInfo) {
//        if ($key < 1) {
//            echo json_encode($productInfo) . PHP_EOL . PHP_EOL;
//            foreach ($productInfo['direct'] as $directAttrKey => $directAttrValue) {
//                echo $directAttrKey . $directAttrValue . PHP_EOL;
//                $product->setData($directAttrKey, $directAttrValue);
//            }
//
//            $product->setCreatedAt(strtotime('now')) //product creation time
//            ->setAttributeSetId(9) //ID of a attribute set named 'default'
//            ->setUpdatedAt(strtotime('now')); //product update time
//
//        }
//    }

    $product->setSku("ABC123");
    $product->setName("Type 7 Widget");
    $product->setDescription("This widget will give you years of trouble-free widgeting.");
    $product->setShortDescription("High-end widget.");
    $product->setPrice(70.50);
    $product->setTypeId('simple');
    $product->setAttributeSetId(9); // need to look this up
    $product->setWeight(1.0);
    $product->setTaxClassId(2); // taxable goods
    $product->setVisibility(4); // catalog, search
    $product->setStatus(1); // enabled

    $product->save();

}catch(Exception $e){
    var_dump($e->getMessage());
}