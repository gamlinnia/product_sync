<?php
ini_set('memory_limit', '512M');

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
/* if use admin, then websiteId will get 0 */
Mage::app('admin');



$categoryIdToDelete = array(4, 7, 8, 49);

$categoryCollection = Mage::getModel('catalog/category')->getCollection();

foreach ($categoryCollection as $_category) {
    $category = Mage::getModel('catalog/category')->load($_category->getId());

    $path = explode('/', $category->getPath());

    if (count($path) > 4) {
        Zend_Debug::dump($path);
        Zend_Debug::dump($category->getData());
        $category->delete();
    }

    foreach ($categoryIdToDelete as $id) {
        if (in_array((string)$id, $path)) {
            Zend_Debug::dump($path);
            Zend_Debug::dump($category->getData());
            $category->delete();
        }
    }
//      $category->delete();
}
