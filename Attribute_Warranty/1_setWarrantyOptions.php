<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

require_once 'getAllWarrantyOptions.php';

//$allBrandOptions = getAllBrandOption();
//$allBrandOptions = array('1 years', '2 Years', '3 Years', '4 Years', '5 Years', '6 Years', '7 Years', '10 Years', 'Lifetime');

var_dump($allBrandOptions);

die();

//attribute which need to be added options
$arg_attribute = 'brand';
$arg_value = 'value to be added';

//$attr_model = Mage::getModel('catalog/resource_eav_attribute');
//$attr = $attr_model->loadByCode('catalog_product', $arg_attribute);
//$attr_id = $attr->getAttributeId();
//
//$count = 0;
//foreach($allBrandOptions as $index => $eachBrand){
//    $option['attribute_id'] = $attr_id;
//    $option['value']['test_brand_' . $index][0] = $eachBrand;
//    $option['order']['test_brand_' . $index] = $count;
//    $count++;
//}
//
//$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
//$setup->addAttributeOption($option);
