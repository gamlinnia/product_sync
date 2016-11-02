<?php


$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';

require_once '../lib/ganon.php';
require_once '../lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

function getOptionsFromAttributeName($attribute_name){
        $details = getAttributeOptions('attributeName', $attribute_name);
        //var_dump($options);
        $options = $details['options'];
        $options_string = array();
        foreach ($options as $each) {
                //var_dump($each);
                $options_string[] = $each['label'];
        }
        return implode(",", $options_string);
}

function getAttributeDetailByAttributeName($attribute_name) {
        return Mage::getModel('eav/entity_setup','core_setup')->getAttribute('catalog_product', $attribute_name);
}

function getAttributeList() {
        $attributes = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
        $result = [];
        foreach ($attributes as $attribute) {
                $type = $attribute->getFrontendInput();
                if(empty(trim($type))) {
                        continue;
                }
                $attribute_name = $attribute->getAttributeCode();
                $output_string = '';
                if ($type == 'select' || $type == 'multiselect') {
                        $output_string = getOptionsFromAttributeName($attribute_name);
                        if(empty($output_string)) {
                                continue;
                        }
                }
                else {
                        $output_string = $attribute->getFrontendLabel();
                        if(empty($output_string)) {
                                continue;
                        }
                }
                $result[] = array(
                        'name' => $attribute_name,
                        'type' => $type,
                        'label/options' => $output_string
                );
        }
        return $result;
}

function main() {
        $modeArray = array(
                        '1' => 'export all result to excel',
                        '2' => 'dump all result on screen',
                        '3' => 'search specify attribute'
                );

        $mode = '';
        while (empty($mode)) {
                echo "Please select mode [1. export all result to excel / 2. dump all result on screen / 3. search specify attribute]: ";
                $mode = trim(fgets(STDIN));
                if(array_key_exists($mode, $modeArray)) {
                        if($mode == '3') {
                                $attribute_name = '';
                                while(empty($attribute_name)) {
                                        echo "Please input attribute name: " . PHP_EOL;
                                        $attribute_name = trim(fgets(STDIN));
                                }
                        }
                }
                else {
                        $mode = '';
                        echo "Error input, please select mode again" . PHP_EOL;
                }
        }

        if($mode == '1') {
                $result = getAttributeList();
                $filename = 'attribute_list.xls';
                $sheetname = 'attribute';
                $response = exportArrayToXlsx($result, array(
                    "filename" => $filename,
                    "title" => $sheetname
                ));
        }
        else if ($mode == '2') {
                $result = getAttributeList();
                var_dump($result);
        }
        else if ($mode == '3') {
                if(getAttributeDetailByAttributeName($attribute_name)) {
                        var_dump(getAttributeDetailByAttributeName($attribute_name));
                }
                else {
                        main();
                }
        }
}

main();
