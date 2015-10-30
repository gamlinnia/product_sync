#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* set the store id to 0, to change the attr by default */
Mage::app('admin');

$productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToselect('visibility')->addAttributeToselect('name');

$valueIdOfInvisible = getAttributeValueIdFromOptions('attributeName', 'visibility', 'Not Visible Individually');

$noImageList = array();
foreach($productCollection as $product) {
    $mediaGalleryImages = Mage::getModel('catalog/product')->load($product->getId())->getMediaGalleryImages();
    if (count($mediaGalleryImages) < 2) {
        if (count($mediaGalleryImages) < 1) {
            echo '***************************' . $product->getSku() . 'has no image ***************************' . PHP_EOL;
        }
        foreach ($mediaGalleryImages as $image) {
            $pathinfo = pathinfo($image['url']);
            preg_match('/cs/', $pathinfo['basename'], $match);
            if ($match) {
                echo $pathinfo['basename'] . ' ' . $product->getSku() . PHP_EOL;
                $noImageList[] = array(
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'cs_name' => $pathinfo['basename']
                );
                $product->setVisibility($valueIdOfInvisible);
                $product->setUrlKey(false);
                $product->save();
                sleep(rand(2,4));
            }
        }
    }
}

require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
exportArrayToXlsx($noImageList, array("filename"=>"noImageProductList.xls", "title"=>"No Image Product List"));