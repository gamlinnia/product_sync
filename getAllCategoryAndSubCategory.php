<?php
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');


$categoryCollection = Mage::getModel('catalog/category')
    ->getCollection()
    ->addAttributeToSelect('level')
    ->addAttributeToSelect('name')
    ->addAttributeToSelect('is_active');

foreach($categoryCollection as $eachCategory){
    $data = $eachCategory->getData();
    $level = $data['level'];
    $isActive = $data['is_active'];
    $id = $data['entity_id'];
    if(($level == '2' && $isActive)){
        echo 'Level 2: ';
        echo $data['name'] . ', ';
        echo 'Entity ID: ' . $data['entity_id'] . PHP_EOL;
        $subCategoryCollection = Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('parent_id', $id)
            ->addFieldToFilter('is_active', 1);
        foreach($subCategoryCollection as $eachSubCategory){
            $subData = $eachSubCategory->getData();
            echo ' └- Level 3: ';
            echo $subData['name'] . ', ';
            echo 'Parent ID: ' . $subData['parent_id'] . PHP_EOL;
        }
    }
}
