<?php
/*
 * for color attribute now
 *
 * */
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

$proceedArray = array('c09140_fryers_power', 'c27150_rice_cookers_power', 'c30150_thermo_pot_power', 'c31230_toaster_oven_power', 'c03290_led_power_consumption','c11090_power_supply', 'c13100_power_supply');

foreach ($proceedArray as $eachDelete) {
            if(!$debug) {
                //delete attribute from database
                $setup = Mage::getResourceModel('catalog/setup', 'catalog_setup');
                $setup->removeAttribute('catalog_product', $eachDelete);
            }
}