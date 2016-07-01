#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');


$collection = Mage::getModel('eav/entity_attribute')->getCollection()->addFieldToFilter('entity_type_id', 4);
foreach ($collection as $eachAttribute) {
    $attributeModel = Mage::getModel('eav/entity_attribute')->load($eachAttribute->getId());
    if ($attributeModel->getData('is_searchable') == 1) {
        Zend_Debug::dump($attributeModel->getData());
    }
}

