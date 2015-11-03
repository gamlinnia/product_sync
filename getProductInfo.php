<?php

require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app();

$product = Mage::getModel('catalog/product')->load(1961);
var_dump($product);