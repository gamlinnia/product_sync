<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
require_once '../lib/ganon.php';
require_once '../lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

$modeArray = array(
    '1' => '1. export all result to excel',
    '2' => '2. dump all result on screen',
    '3' => '3. search specify attribute',
    '4' => '4. get all attribute with the same label, assign all related products to new attribute'
);

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
    $attributes = Mage::getModel('eav/entity_attribute')->getCollection()
        ->addFieldToFilter('entity_type_id', 4);
    $result = [];
    foreach ($attributes as $_attribute) {
        $attribute = Mage::getModel('eav/entity_attribute')->load(
            $_attribute->getId()
        );

        if ( !$attribute->getData('is_user_defined') ) {
            continue;
        }

        $type = $attribute->getFrontendInput();
        if(empty(trim($type))) {
            continue;
        }
        $attribute_name = $attribute->getAttributeCode();

        $label = $attribute->getFrontendLabel();

        $output_string = '';
        if ($type == 'select' || $type == 'multiselect') {
            $output_string = getOptionsFromAttributeName($attribute_name);
            if(empty($output_string)) {
                continue;
            }
        }

        $attr_collection = Mage::getModel('eav/entity_attribute')->getCollection()
        ->addFieldToFilter('frontend_label', $label);

        $result[] = array(
            'name' => $attribute_name,
            'count' => $attr_collection->count(),
            'type' => $type,
            'label' => $label,
            'options' => $output_string
        );
    }
    return $result;
}

function main() {
    global $modeArray;
    $mode = '';
    while (empty($mode)) {
        echo "Please select mode [" . implode(' / ', $modeArray) . ']:';
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
        exportArrayToXlsx($result, array(
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

    switch ($mode) {
        case '4' :
            $keyword_to_search = promptMessageForInput('enter keyword to search for related attributes');
            $attr_collection = getAttributeCollection();
            $attr_collection->addFieldToFilter('frontend_label', array('like' => '%' . $keyword_to_search . '%'));
            if ($attr_collection->count() < 1) {
                echo 'found no attributes' . PHP_EOL;
                return;
            }

            $optionList = array();
            foreach ($attr_collection as $_attr) {
                $attr = Mage::getModel('eav/entity_attribute')->load(
                    $_attr->getId()
                );
                $options = getAttributeOptions('attributeId', $attr->getId());
                if (isset($options['options'])) {
                    foreach ($options['options'] as $option) {
                        $optionList[] = $option;
                    }
                }

                Zend_Debug::dump(array(
                    'id' => $attr->getId(),
                    'attribute_code' => $attr->getData('attribute_code'),
                    'frontend_label' => $attr->getData('frontend_label'),
                    'frontend_input' => $attr->getData('frontend_input'),
                    'options' => isset($options['options']) ? $options['options'] : null
                ));
            }
            echo 'similar attr count: ' . $attr_collection->count() . PHP_EOL;

            var_dump($optionList);



//            $new_attr_label = promptMessageForInput('enter new attr label to create');
//            $new_attr_id = createNewAttribute($new_attr_label);

            break;
    }

}

main();

function promptMessageForInput ($message) {
    $input = '';
    while (empty($input)) {
        echo $message . PHP_EOL;
        $input = trim(fgets(STDIN));
    }
    return $input;
}