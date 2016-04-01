<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

require_once 'getAllColorOptions.php';

$allColorOptions = getAllColorOption();

//$allColorOptions = json_decode(file_get_contents('attribute_color.txt'), true);

$newColorOptions = array();

foreach($allColorOptions as $each){
    $newColorOptions[] = strtolower($each);
}

$newColorOptions = array_unique($newColorOptions);
//var_dump($newColorOptions);
$allColorOptions = array();
foreach($newColorOptions as $each){
    $words =  explode(' ', $each);
    //var_dump($words);
    foreach($words as $index => $word){
       $words[$index] = ucfirst($word);
    }
    $newWord = implode(' ', $words);
    $allColorOptions[] = $newWord;
}

//var_dump($allColorOptions);

$arg_attribute = 'color';
$arg_value = 'value to be added';

$attr_model = Mage::getModel('catalog/resource_eav_attribute');
$attr = $attr_model->loadByCode('catalog_product', $arg_attribute);
$attr_id = $attr->getAttributeId();

//var_dump($attr);
//die();
$count = 0;
foreach($allColorOptions as $index => $eachColor){
    $option['attribute_id'] = $attr_id;
    $option['value']['test_color_' . $index][0] = $eachColor;
    $option['order']['test_color_' . $index] = $count;
    $count++;
}

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$setup->addAttributeOption($option);
