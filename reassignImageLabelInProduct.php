
<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

$collection = Mage::getModel('catalog/product')->getCollection();
foreach($collection as $_product) {
    $id = $_product->getId();
    $product = Mage::getModel('catalog/product')->load($id);
    $media_gallery = $product->getMediaGallery();
    $images = $media_gallery['images'];
    $new_images = array();
    foreach($images as $each) {
        $file = $each['file'];
        if(strpos($file, '.jpg.jpg')) {
            $file = str_replace('.jpg.jpg', '', $file);
        }
        else if(strpos($file, '.jpg')) {
            $file = str_replace('.jpg', '', $file);
        }
        $label = $each['label'];
        if(empty($label)) {
            preg_match('/.+\/.+\/(.+)/', $file, $match);
            if($match[1]) {
                $new_label = $match[1];
                $each['label'] = $new_label;
                $new_images[] = $each;
            }
        }
    }
    echo $id . PHP_EOL;
    if($new_images) {
        $media_gallery['images'] = $new_images;
        try {
            $product->setMediaGallery($media_gallery)->save();
        }
        catch(Exception $e) {
            echo "Exception" . PHP_EOL;
            var_dump($e->getMessage());
        }
    }
}
