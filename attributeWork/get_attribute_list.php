<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
require_once '../lib/ganon.php';
require_once '../lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

$mappingTable = array();

$modeArray = array(
    '1' => '1. export all result to excel',
    '2' => '2. dump all result on screen',
    '3' => '3. search specify attribute',
    '4' => '4. get all attribute with the same label, assign all related products to new attribute',
    '5' => '5. get all attribute with label or attribute code, list all options or text values.'
);

function setMappingTable($mappingTable) {
    $mappingTable = json_encode($mappingTable);
    file_put_contents('mappingTable.json', $mappingTable);
}

function getMappingTable() {
    return json_decode(file_get_contents('mappingTable.json'), true);
}

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
    global $mappingTable;
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
            $searchType = promptMessageForInput('search for attribute_code(like search) or attribute_label(non-like search)', array('code', 'label'));
            $keyword_to_search = promptMessageForInput('enter keyword to search for related attributes');
            $attr_collection = getAttributeCollection();

            switch ($searchType) {
                case 'code' :
                    $attr_collection->addFieldToFilter('attribute_code', array('like' => '%' . $keyword_to_search . '%'));
                    break;
                case 'label' :
                    $attr_collection->addFieldToFilter('frontend_label', $keyword_to_search);
                    $attr_collection   ->addFieldToFilter('attribute_code', array('neq' => generateAttributeCodeByLabel($keyword_to_search)));
                    break;
            }

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
                        if (!in_array(ucwords($option['label']), $optionList)) {
                            $optionList[] = ucwords($option['label']);
                        }
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
            echo 'similar attr count: ' . $attr_collection->count() . PHP_EOL . PHP_EOL;

            //add ignore list to attribute collection
            $prompt = promptMessageForInput('add ignore list ?(Y/n)');
            if($prompt == 'y') {
                $ignoreList = promptMessageForInput('input attribute name list separate by ","');
                $ignoreList = explode(',', $ignoreList);
                if(!empty($ignoreList)) {
                    $attr_collection->addFieldToFilter('attribute_code', array('nin' => $ignoreList));
                }
            }

            $new_attr_label = promptMessageForInput('enter new attr label to create or empty to delete above found attributes', null, true);
            if (empty($new_attr_label)) {
                $new_attr_label = $keyword_to_search;
                echo 'empty input, $new_attr_label: ' . $new_attr_label . PHP_EOL;
                foreach($attr_collection as $_attr) {
                    $prompt = promptMessageForInput('sure to delete attribute: ' . $attr->getData('attribute_code') . ' (Y/n)?');
                    if($prompt == 'y') {
                        Mage::getModel('eav/entity_attribute')->load($_attr->getId())->delete();
                    }
                }
                exit(0);
            }

            $new_frontend_input = promptMessageForInput('enter frontend_input type to create', array(
                'multiselect', 'boolean', 'select', 'text', 'textarea'
            ));
            $new_attr_id = createNewAttribute($new_attr_label, $new_frontend_input);
            if (!$new_attr_id) {
                echo 'no new attr id returned' . PHP_EOL;
                exit(0);
            }
            $new_attr = Mage::getModel('eav/entity_attribute')->load($new_attr_id);
            echo 'label: ' . $new_attr_label . ' created, id: ' . $new_attr_id . PHP_EOL;
            echo 'option list: ' . implode(' / ', $optionList) . ' count: ' . count($optionList) . PHP_EOL . PHP_EOL;

            $oldAttributeOptions = getAttributeOptions('attributeId', $new_attr_id);
            Zend_Debug::dump($oldAttributeOptions);

            $toAddArray = compareAttributeOptionArray($oldAttributeOptions['options'], $optionList);
            echo 'to be added option list: ' . implode(' , ', $toAddArray) . ' count: ' . count($toAddArray) . PHP_EOL . PHP_EOL;

            if (count($toAddArray) > 0) {
                $prompt = strtolower(promptMessageForInput('add below options ?(Y/n) or modify these options(M/m): ' . implode(' | ', $toAddArray) ));
                if ($prompt == 'y'|| $prompt == 'yes') {
                    setAttributeOptions($new_attr_id, $toAddArray);
                }
                elseif ($prompt == 'm') {
                    $prompt = promptMessageForInput('enter the string of all options(separate by "|")');
                    $newOptionsArray = explode('|', $prompt);
                    $newOptionsArray = array_map('trim', $newOptionsArray);
//                    Zend_Debug::dump($newOptionsArray);
                    $prompt = strtolower(promptMessageForInput('sure to add these new options above ?(Y/n)'));
                    if($prompt =='y') {
                        setAttributeOptions($new_attr_id, $newOptionsArray);
                    }
                }
            }
            else {
                $prompt = promptMessageForInput('enter the string of all options(separate by "|")');
                $newOptionsArray = explode('|', $prompt);
                $newOptionsArray = array_map('trim', $newOptionsArray);
//                Zend_Debug::dump($newOptionsArray);
                $prompt = strtolower(promptMessageForInput('sure to add these new options above ?(Y/n)'));
                if($prompt =='y') {
                    setAttributeOptions($new_attr_id, $newOptionsArray);
                }
            }

            if(!empty($newOptionsArray)) {
                var_dump($newOptionsArray);
            }
            elseif(!empty($toAddArray)) {
                var_dump($toAddArray);
            }
            else {
                var_dump($oldAttributeOptions['options']);
            }

            $new_attribute_code = $new_attr->getAttributeCode();

            //get new attribute collection
            $attr_collection = getAttributeCollection();
            //exclude the attribute just created
            switch ($searchType) {
                case 'code' :
                    $attr_collection->addFieldToFilter('attribute_code', array('like' => '%' . $keyword_to_search . '%'));
                    break;
                case 'label' :
                    $attr_collection->addFieldToFilter('frontend_label', $keyword_to_search);
                    $attr_collection->addFieldToFilter('attribute_code', array('neq' => generateAttributeCodeByLabel($new_attr_label)));
                    break;
            }

            if(!empty($ignoreList)) {
                $attr_collection->addFieldToFilter('attribute_code', array('nin' => $ignoreList));
            }

            checkAllAttributeSetToSetAttributeGroup($new_attribute_code, $attr_collection);

            echo "Start scan attributes of products." . PHP_EOL;

            $attrCount = 0;
            $productCollection = Mage::getModel('catalog/product')->getCollection();
            foreach ($attr_collection as $_attr) {
                $attrCount++;
                $frontend_input = $_attr->getData('frontend_input');
                $old_attr_code = $_attr->getData('attribute_code');

                foreach ($productCollection as $_product) {
                    $product = Mage::getModel('catalog/product')->load(
                        $_product->getId()
                    );

                    if (!empty($product->getData($old_attr_code))) {
                        if (!checkAttributeInProductAttributeSet($new_attribute_code, $product)) {
                            echo 'new attribute NOT exist in product' . PHP_EOL;
                            $attribute_set_name = Mage::getModel('eav/entity_attribute_set')->load($product->getAttributeSetId())->getAttributeSetName();
                            echo 'attribute set name = ' . $attribute_set_name . PHP_EOL;
                        }
                        $old_attr_value = getAttributeLabelFromOptions(
                            'attributeName',
                            $old_attr_code,
                            $product->getData($old_attr_code)
                        );
                    } else {
                        $old_attr_value = null;
                    }
//                    Zend_Debug::dump(array(
//                        'sku' => $product->getSku(),
//                        'attribute_set_id' => $product->getAttributeSetId(),
//                        'old attribute code' => $old_attr_code,
//                        'old attribute value' => $old_attr_value,
//                        'frontend_input' => $frontend_input
//                    ));
                    if (!empty($old_attr_value)) {
                        /* set old value to new attribute */
                        if ( empty($product->getData($new_attribute_code)) ) {
                            echo PHP_EOL . '"' . $old_attr_code . '" : old_attr_value: ' . $old_attr_value . PHP_EOL;
                            $promptOptionArray = getOptionsFromAttributeName($new_attribute_code);
                            $promptOptionArray = explode(',', $promptOptionArray);
                            if(in_array(strtolower($old_attr_value), array_map('strtolower', $promptOptionArray))){
                                setProductValue($product, $new_attribute_code, $new_frontend_input, $old_attr_value);
                            }
                            elseif($mappingTable[$old_attr_value]) {
                                setProductValue($product, $new_attribute_code, $new_frontend_input, $mappingTable[$old_attr_value]);
                            }
                            else {
                                var_dump($promptOptionArray);
                                echo PHP_EOL . '"' . $old_attr_value . '" need to mapping to one of above values' . PHP_EOL;
                                $prompt = promptMessageForInput('create a mapping table?(Y/n)');
                                if($prompt == 'y') {
                                    $prompt = promptMessageForInput('old_attribute_value');
                                    $temp1 = $prompt;
                                    $prompt = promptMessageForInput('new_attribute_value');
                                    $temp2 = $prompt;
                                    $mappingTable[$temp1] = $temp2;
                                    setMappingTable($mappingTable);
                                    if($mappingTable[$old_attr_value]) {
                                        setProductValue($product, $new_attribute_code, $new_frontend_input, $mappingTable[$old_attr_value]);
                                    }
                                }
//                                else {
//                                    setProductValue($product, $new_attribute_code, $new_frontend_input, $old_attr_value);
//                                }
                            }
                        }
                    }
                    echo '-';
                }
                /* each attr loop for product done  */
                echo PHP_EOL;
                echo 'looped attr: ' . $_attr->getAttributeCode() . ' index: ' . $attrCount . PHP_EOL;
                $prompt = strtolower(promptMessageForInput('delete ' . $old_attr_code . ' (Y/n) ?'));
                if($prompt == 'y') {
                    Mage::getModel('eav/entity_attribute')->load($_attr->getId())->delete();
                }
                sleep(3);
            }

            break;
        case '5' :
            /* search for attribute_code or attribute_label */
            $searchType = '';
            while (empty($searchType)) {
                $searchType = promptMessageForInput('search for attribute_code or attribute_label', array('code', 'label'));


                $keyword_to_search = promptMessageForInput('enter keyword to search for related attributes');
                $attr_collection = getAttributeCollection();

                switch ($searchType) {
                    case 'code' :
                        $attr_collection->addFieldToFilter('attribute_code', array('like' => '%' . $keyword_to_search . '%'));
                        break;
                    case 'label' :
                        $attr_collection->addFieldToFilter('frontend_label', array('like' => '%' . $keyword_to_search . '%'));
                        break;
                }

                if ($attr_collection->count() < 1) {
                    echo 'found no attributes' . PHP_EOL;
                    return;
                }

                $response = array();
                foreach ($attr_collection as $_attr) {
                    $optionList = array();
                    $tmpArray = array();
                    $attr = Mage::getModel('eav/entity_attribute')->load(
                        $_attr->getId()
                    );
                    $options = getAttributeOptions('attributeId', $attr->getId());
                    if (isset($options['options'])) {
                        $tmpArray = array(
                            'id' => $attr->getId(),
                            'attribute_code' => $attr->getData('attribute_code'),
                            'frontend_label' => $attr->getData('frontend_label'),
                            'frontend_input' => $attr->getData('frontend_input'),
                            'options' => $options['options']
                        );
                    } else {
                        $new_attribute_code = $_attr->getAttributeCode();
                        $productCollection = Mage::getModel('catalog/product')->getCollection();

                        $count = 0;
                        foreach ($productCollection as $_product) {
                            $count++;
                            if ($count % 100 == 0) {
                                echo $count . '.. ';
                            }
                            $product = Mage::getModel('catalog/product')->load($_product->getId());
                            if (!empty($textValue = $product->getData($_attr->getAttributeCode()))) {
                                echo 'found data' . $textValue . PHP_EOL;
                                if (!in_array($textValue, $optionList)) {
                                    $optionList[] = $textValue;
                                }
                            }
                        }

                        $tmpArray = array(
                            'id' => $attr->getId(),
                            'attribute_code' => $attr->getData('attribute_code'),
                            'frontend_label' => $attr->getData('frontend_label'),
                            'frontend_input' => $attr->getData('frontend_input'),
                            'options' => implode(', ', $optionList)
                        );
                        Zend_Debug::dump($tmpArray);
                    }

                    $response[] = $tmpArray;
                }
                echo 'similar attr count: ' . $attr_collection->count() . PHP_EOL . PHP_EOL;

                Zend_Debug::dump($response);
                $searchType = '';
            }
            break;
    }

}

main();
