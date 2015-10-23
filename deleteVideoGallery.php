<?php
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$videoGalleryId = '100';

$model = Mage::getModel('videogallery/videogallery')->load($videoGalleryId, 'videogallery_id');
$image = $model->getImage();
$filepath = Mage::getBaseDir('media').DS."videogallery\\".$image;
$filepath2 = Mage::getBaseDir('media').DS."videogallery".DS."resized".DS."small\\".$image;
$filepath3 = Mage::getBaseDir('media').DS."videogallery".DS."resized".DS."thumb\\".$image;
unlink($filepath);
unlink($filepath2);
unlink($filepath3);
if ($model->getVideogalleryId()) {
    $model->delete();
}