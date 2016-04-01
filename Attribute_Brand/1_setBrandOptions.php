<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

require_once 'getAllBrandOptions.php';

$allBrandOptions = getAllBrandOption();

var_dump($allBrandOptions);

die();

$newBrandOptions = array();

foreach($allBrandOptions as $each){
    $newBrandOptions[] = strtolower($each);
}

$newBrandOptions = array_unique($newBrandOptions);
//var_dump($newBrandOptions);
$allBrandOptions = array();
foreach($newBrandOptions as $each){
    $words =  explode(' ', $each);
    //var_dump($words);
    foreach($words as $index => $word){
       $words[$index] = ucfirst($word);
    }
    $newWord = implode(' ', $words);
    $allBrandOptions[] = $newWord;
}

//var_dump($allBrandOptions);

$arg_attribute = 'brand';
$arg_value = 'value to be added';

$attr_model = Mage::getModel('catalog/resource_eav_attribute');
$attr = $attr_model->loadByCode('catalog_product', $arg_attribute);
$attr_id = $attr->getAttributeId();

//var_dump($attr);
//die();
$count = 0;
foreach($allBrandOptions as $index => $eachBrand){
    $option['attribute_id'] = $attr_id;
    $option['value']['test_brand_' . $index][0] = $eachBrand;
    $option['order']['test_brand_' . $index] = $count;
    $count++;
}

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->addAttributeOption($option);
