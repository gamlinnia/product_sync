#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$model = Mage::getModel('videogallery/videogallery');
$model->setData($data)->setImage($videoimage)->setName($videoname)->setVideogalleryUrl($data['videogallery_url'])->setVideogalleryCategory($data['videogallery_category']);
