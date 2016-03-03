#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

$collection = Mage::getModel('catalog/product')->getCollection();
$collection->addAttributeToFilter('description', array(
    array('like' => '% '. 'This product contains a chemical known to the State of California to cause cancer' .' %')
));

foreach ($collection as $product) {
    echo $product->getDescription();
}

echo $collection->count();

