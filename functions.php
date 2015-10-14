<?php

function attributeSetNameAndId ($nameOrId, $value) {
    /*$nameOrId = 'attributeSetName' or 'attributeSetId'*/
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection') ->load();
    foreach ($attributeSetCollection as $id => $attributeSet) {
        $entityTypeId = $attributeSet->getEntityTypeId();
        $name = $attributeSet->getAttributeSetName();
        switch ($nameOrId) {
            case 'attributeSetName' :
                if ($name == $value) {
                    return array(
                        'name' => $name,
                        'id' => $id,
                        'entityTypeId' => $entityTypeId
                    );
                }
                break;
            case 'attributeSetId' :
                if ((int)$id == (int)$value) {
                    return array(
                        'name' => $name,
                        'id' => $id,
                        'entityTypeId' => $entityTypeId
                    );
                }
                break;
        }
    }
    return null;
}

function attributeNameAndId () {

}

function getAttributeOptions ($nameOrId, $value) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    switch ($nameOrId) {
        case 'attributeName' :
            $attributeCode = $value;
            $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', $value);
            break;
        case 'attributeId' :
            $attributeCode = Mage::getModel('eav/entity_attribute')->load($value)->getAttributeCode();
            $attributeId = $value;
            break;
    }

    if (isset($attributeCode)) {
        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
        $attributeData = $attribute->getData();
        $rs = array(
            'attributeCode' => $attributeCode,
            'attributeId' => $attributeId,
            'frontend_input' => $attributeData['frontend_input'],
            'backend_type' => $attributeData['backend_type']
        );
        if ($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions(false);
            $rs['options'] = $options;
        }
        return $rs;
    }

    return null;
}

function getAttributeValueFromOptions ($nameOrId, $attrCodeOrId, $valueToBeMapped) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    file_put_contents('log.txt', $attrCodeOrId . ': ' . $valueToBeMapped . PHP_EOL, FILE_APPEND);
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);
    switch ($optionsArray['frontend_input']) {
        case 'select' :
        case 'boolean' :
            foreach ($optionsArray['options'] as $optionObject) {
                if ((int)$optionObject['value'] == (int)$valueToBeMapped) {
                    return $optionObject['label'];
                }
            }
            break;
        case 'multiselect' :
            /*multiselect : a02030_headsets_connector,
            "a02030_headsets_connector": "147,148,149,150"*/
            file_put_contents('log.txt', $attrCodeOrId . ': ' . $valueToBeMapped . PHP_EOL, FILE_APPEND);
            $valueToBeMappedArray = explode(',', $valueToBeMapped);
            file_put_contents('log.txt', 'count($valueToBeMappedArray)' . ': ' . count($valueToBeMappedArray) . PHP_EOL, FILE_APPEND);
            if (count($valueToBeMappedArray) < 2) {
                foreach ($optionsArray['options'] as $optionObject) {
                    if ((int)$optionObject['value'] == (int)$valueToBeMapped) {
                        return $optionObject['label'];
                    }
                }
            } else {
                $mappedArray = array();
                foreach ($optionsArray['options'] as $optionObject) {
                    if (in_array((int)$optionObject['value'], $valueToBeMappedArray)) {
                        file_put_contents('log.txt', 'mapped value' . ': ' . $optionObject['label'] . PHP_EOL, FILE_APPEND);
                        $mappedArray[] = $optionObject['label'];
                    }
                }
                return $mappedArray;
            }
            break;
        case 'text' :
        case 'textarea' :
            return $valueToBeMapped;
            break;
        default :
            return '******** no mapping value ********';
    }
    return null;
}

function classifyProductAttributes ($productInfo) {
    $keyWord = array(
        'direct' => array(
            "weight",
            "length",
            "price",
            "status",
            "visibility",
            "enable_rma",
            "is_salable",
            "entity_type_id",
            "has_options",
            "upc_number",
            "required_options"
        ),
        'dontCare' => array(
            "created_at",
            "updated_at",
            "url_key",
            "image",
            "small_image",
            "thumbnail",
            "url_path",
            "stock_item",
            "entity_id"
        )
    );

    $response = array(
        'direct' => array(),
        'dontCare' => array(),
        'needToBeParsed' => array()
    );

    foreach ($productInfo as $attrKey => $attrValue) {
        # dontCare class -> in pre-defined dontCare_keyWord array
        if( preg_in_array($attrKey, $keyWord['dontCare']) ){
            $response['dontCare'][$attrKey] = $attrValue;
        }
        # direct class -> the value is not numeric or in pre-defined direct_keyWord array
        else if ( isFloat($attrValue) || !is_numeric($attrValue) || preg_in_array($attrKey, $keyWord['direct'])){
            /*determine the multiselect case 147,148,149,150*/
            preg_match('/^([\d]+[,]{1})+[\d]+$/', $attrValue, $match);
            if ($match) {
                $response['needToBeParsed'][$attrKey] = $attrValue;
            } else {
                $response['direct'][$attrKey] = $attrValue;
            }
        }
        # needToBeParsed -> others
        else{
            $response['needToBeParsed'][$attrKey] = $attrValue;
        }
    }

    return $response;
}

function parseClassifiedProductAttributes ($classifiedProductInfo) {
    $parsedProductInfo = array(
        'direct' => $classifiedProductInfo['direct'],
        'dontCare' => $classifiedProductInfo['dontCare']
    );
    foreach ($classifiedProductInfo['needToBeParsed'] as $attrKey => $attrValue) {
        switch ($attrKey) {
            case 'attribute_set_id' :
                $attrIdName = attributeSetNameAndId('attributeSetId', $attrValue);
                $parsedProductInfo['needToBeParsed'][$attrKey] = $attrIdName['name'];
                break;
            default :
                $parsedProductInfo['needToBeParsed'][$attrKey] = getAttributeValueFromOptions('attributeName', $attrKey, $attrValue);;
        }
    }
    return $parsedProductInfo;
}

function getProductInfoFromMagento ($filterParam, $pageSize) {
    $response = array(
        'productsInfo' => array()
    );

    $productCollection = Mage::getModel('catalog/product')->getCollection();
    foreach ($filterParam as $filterAttr => $filterAttrParam) {
        $productCollection->addAttributeToFilter($filterAttr, $filterAttrParam);
    }
    $response['count'] = count($productCollection);
    $response['pageSize'] = $pageSize;

    $productCollection = Mage::getModel('catalog/product')
        ->getCollection()
        ->addAttributeToSelect('*');
    foreach ($filterParam as $filterAttr => $filterAttrParam) {
        $productCollection->addAttributeToFilter($filterAttr, $filterAttrParam);
    }
    $productCollection->setOrder('updated_at', 'ASC')->setPageSize($pageSize);

    foreach ($productCollection as $product) {
        $response['productsInfo'][] = $product->debug();
    }
    return $response;
}

function preg_in_array ($needle, $haystack) {
    foreach ($haystack as $value) {
        $subject = $needle;
        $pattern = '/^' . $value . '/';
        preg_match($pattern, $subject, $matches);
        if ($matches) {
            return true;
        }
    }
    return false;
}

function getJsonFile ($setting) {
    $productJson = file_get_contents($setting['storeJsonDir'] . $setting['storeJsonFile']);
    if ($productJson) {
        return $productJson;
    }
    return null;
}

function isFloat ($element) {
    preg_match('/[\d]+[\.]{1}[\d]+/', $element, $match);
    if ($match) {
        return true;
    }
    return false;
}

function getLastEntityId () {
    $productCollection = Mage::getModel('catalog/product')
        ->getCollection()->setOrder('entity_id')->setPageSize(1);
    foreach ($productCollection as $product) {
        $response = $product->debug();
    }
    return $response['entity_id'];
}

/* ********************* used by clone host ********************* */
function parseBackClassifiedProductAttributes ($parsedClassifiedProductInfo) {
    $parsedProductInfo = array();
    foreach ($parsedClassifiedProductInfo['needToBeParsed'] as $attrKey => $attrValue) {
        switch ($attrKey) {
            case 'attribute_set_id' :
                $attrIdName = attributeSetNameAndId('attributeSetName', $attrValue);
                $parsedProductInfo[$attrKey] = $attrIdName['name'];
                break;
            default :
                $parsedProductInfo[$attrKey] = getAttributeValueIdFromOptions('attributeName', $attrKey, $attrValue);;
        }
    }
    foreach ($parsedClassifiedProductInfo['direct'] as $attrKey => $attrValue) {
        $parsedProductInfo[$attrKey] = $attrValue;
    }
    return $parsedProductInfo;
}

function getAttributeValueIdFromOptions ($nameOrId, $attrCodeOrId, $valueToBeMapped) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    file_put_contents('log.txt', $attrCodeOrId . ': ' . $valueToBeMapped . PHP_EOL, FILE_APPEND);
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);
    switch ($optionsArray['frontend_input']) {
        case 'select' :
        case 'boolean' :
            foreach ($optionsArray['options'] as $optionObject) {
                if ((int)$optionObject['label'] == (int)$valueToBeMapped) {
                    return $optionObject['value'];
                }
            }
            break;
        case 'multiselect' :
            /*multiselect : a02030_headsets_connector,
                       "a02030_headsets_connector": "147,148,149,150"*/
            $valueToBeMappedArray = explode(',', $valueToBeMapped);
            if (count($valueToBeMappedArray) < 2) {
                foreach ($optionsArray['options'] as $optionObject) {
                    if ((int)$optionObject['label'] == (int)$valueToBeMapped) {
                        return join(',', $optionObject['value']);
                    }
                }
            } else {
                $mappedArray = array();
                foreach ($optionsArray['options'] as $optionObject) {
                    if (in_array((int)$optionObject['label'], $valueToBeMappedArray)) {
                        file_put_contents('log.txt', 'mapped value' . ': ' . $optionObject['label'] . PHP_EOL, FILE_APPEND);
                        $mappedArray[] = $optionObject['value'];
                    }
                }
                return join(',', $mappedArray);
            }
            break;
        case 'text' :
        case 'textarea' :
            return $valueToBeMapped;
            break;
        default :
            return '******** no mapping value ********';
    }
    return null;
}

function CallAPI($method, $url, $header = null, $data = false) {
    $curl = curl_init();

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    /*Custom Header*/
    if (!empty($header)) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($curl);

    curl_close($curl);

    return json_decode($result, true);
}

function getImagesUrlOfProduct ($productObject) {
    $id = $productObject->getId();
    $product=Mage::getModel('catalog/product')->load($id);

    $response = array();
    foreach ($product->getMediaGalleryImages() as $image) {
        $response[] = $image->getUrl();
//        echo $image->getUrl();
    }
    return $response;
}