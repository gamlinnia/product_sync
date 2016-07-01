#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');


$collection = Mage::getModel('eav/entity_attribute')->getCollection();
foreach ($collection as $eachAttribute) {
    $attributeModel = Mage::getModel('eav/entity_attribute')->load($eachAttribute->getId());
    Zend_Debug::dump($attributeModel->getData());
    die();
//    $attributeModel =
}

