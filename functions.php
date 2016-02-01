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
            'attributeId' => $attributeId
        );
        if (isset($attributeData['frontend_input'])) {
            $rs['frontend_input'] = $attributeData['frontend_input'];
        }
        if (isset($attributeData['backend_type'])) {
            $rs['backend_type'] = $attributeData['backend_type'];
        }
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
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);
    if (!isset($optionsArray['frontend_input'])) {
        return $valueToBeMapped;
    }
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
        case 'price' :
        case 'weight' :
        case 'media_image' :
        case 'date' :
            return $valueToBeMapped;
            break;
        default :
            return $optionsArray['frontend_input'];
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
            "required_options",
            "width",
            "height",
            "url_key"
        ),
        'dontCare' => array(
            "created_at",
            "updated_at",
            "image",
            "small_image",
            "thumbnail",
            "url_path",
            "stock_item",
            "entity_id",
            "is_returnable",
            'category'
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
                $parsedProductInfo['needToBeParsed'][$attrKey] = getAttributeValueFromOptions('attributeName', $attrKey, $attrValue);
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

function getNextProductInfoFromMagento ($filterParam, $pageSize) {
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
    $productCollection->setOrder('updated_at', 'ASC')->setPageSize($pageSize+1);

    foreach ($productCollection as $product) {
        $productDataArray = $product->debug();
        $productDataArray['category'] = getProductCategorysInfo($product->getId());
        if ( count($productCollection) == 1 && $product->getUpdatedAt() == $filterParam['updated_at']['from'] ) {
            $response['productsInfo'] = array();
        } else {
            $response['productsInfo'][] = $productDataArray;
        }
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
                $parsedProductInfo[$attrKey] = $attrIdName['id'];
                break;
            default :
                $parsedProductInfo[$attrKey] = getAttributeValueIdFromOptions('attributeName', $attrKey, $attrValue);;
        }
    }
    foreach ($parsedClassifiedProductInfo['direct'] as $attrKey => $attrValue) {
        switch ($attrKey) {
            case 'news_from_date' :
            case 'news_to_date' :
                $parsedProductInfo[$attrKey] = strtotime($attrValue);
                break;
            default :
                $parsedProductInfo[$attrKey] = $attrValue;
                break;
        }
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
                if ($optionObject['label'] == $valueToBeMapped) {
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
                    if ($optionObject['label'] == $valueToBeMapped) {
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

function getImagesUrlOfProduct ($valueToFilter, $type='entity_id') {
    $product = getProductObject($valueToFilter, $type);
    $mediaType = array(
        'image' => Mage::getModel('catalog/product_media_config')
            ->getMediaUrl( $product->getImage() ),
        'small_image' => Mage::getModel('catalog/product_media_config')
            ->getMediaUrl( $product->getSmallImage() ),
        'thumbnail' => Mage::getModel('catalog/product_media_config')
            ->getMediaUrl( $product->getThumbnail() )
    );

    $response = array();
    foreach ($product->getMediaGalleryImages() as $image) {
        $response[] = getImageResponse($mediaType, $image);
    }

    return $response;
}

function getImageResponse ($mediaTypesContent, $imageObject) {
    $imageMediaType = null;
    $imageUrl = $imageObject->getUrl();
    $imageId = $imageObject->getId();
    $pathInfo = pathinfo($imageUrl);
    $parseUrl = parse_url($imageUrl);
    foreach ($mediaTypesContent as $mediaTypeContent => $mediaTypeContentUrl) {
        if ($imageUrl == $mediaTypeContentUrl) {
            if (!$imageMediaType) {
                $imageMediaType = array($mediaTypeContent);
            } else {
                $imageMediaType[] = $mediaTypeContent;
            }
        }
    }

    $response = array(
        'id' => $imageId,
        'position' => $imageObject->getPosition(),
        'url' => $imageUrl,
        'basename' => $pathInfo['basename'],
        'host' => $parseUrl['host'],
        'mediaType' => $imageMediaType
    );

    return $response;
}

function getFileNameFromUrl ($url) {
    preg_match('/[\/]([a-z0-9\-_]+\.[a-z]{3,4})$/i', $url, $match);
    if (is_array($match) && count($match) > 1) {
        return $match[1];
    }
    return null;
}

function getProductObject ($valueToFilter, $filterType='entity_id') {
    switch ($filterType) {
        case 'sku' :
            $product = Mage::getModel('catalog/product');
            $productObject = $product->load($product->getIdBySku($valueToFilter));
            break;
        default :
            /* filter by entity id */
            $productObject = Mage::getModel('catalog/product')->load($valueToFilter);
    }
    return $productObject;
}

function compareImageWithRemoteIncludeDelete ($localImages, $remoteImages) {
    $response = array(
        'add' => array(),
        'delete' => array()
    );
    foreach ($remoteImages as $remote) {
        $match = false;
        foreach ($localImages as $local) {
            if (strtolower(substr($local['basename'], 0, 2)) == 'cs' && strtolower(substr($local['basename'], 0, 2)) == strtolower(substr($remote['basename'], 0, 2))) {
                $match = true;
                break;
            } else {
                preg_match('/[0-9\-]{13}/', $remote['basename'], $remoteMatch);
                preg_match('/[0-9\-]{13}/', $local['basename'], $localMatch);
                if ($remoteMatch[0] == $localMatch[0]) {
                    $match = true;
                    break;
                }
            }
        }
        if (!$match) {
            $response['add'][] = $remote;
        }
    }

    foreach ($localImages as $local) {
        $match = false;
        foreach ($remoteImages as $remote) {
            if (strtolower(substr($remote['basename'], 0, 2)) == 'cs' && strtolower(substr($local['basename'], 0, 2)) == strtolower(substr($remote['basename'], 0, 2))) {
                $match = true;
                break;
            } else {
                preg_match('/[0-9\-]{13}/', $remote['basename'], $remoteMatch);
                preg_match('/[0-9\-]{13}/', $local['basename'], $localMatch);
                if ( $remoteMatch[0] == $localMatch[0] ) {
                    $match = true;
                    break;
                }
            }
        }
        if (!$match) {
            $response['delete'][] = $local;
        }
    }

    return $response;
}

function compareImageWithRemote ($localImages, $remoteImages) {
    $response = array();
    foreach ($remoteImages as $remote) {
        $match = false;
        foreach ($localImages as $local) {
            if (strtolower(substr($local['basename'], 0, 2)) == 'cs' && count($remoteImages) == 1) {
                $match = true;
                break;
            }
            preg_match('/[0-9\-]{13}/', $remote['basename'], $remoteMatch);
            preg_match('/[0-9\-]{13}/', $local['basename'], $localMatch);
            if ($remoteMatch[0] == $localMatch[0]) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            $response[] = $remote;
        }
    }
    return $response;
}

function uploadImages ($imageObjectList, $valueToFilter, $filterType='entity_id', $config) {
    $product = getProductObject($valueToFilter, $filterType);

    $importDir = Mage::getBaseDir('media') . DS . 'import/';
    if (!file_exists($importDir)) {
        mkdir($importDir);
    }

    $username = 'rosewill';
    $password = 'rosewillPIM';
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));
    foreach ($imageObjectList as $key => $imageObject) {
        if (isset($config['internalHost'])) {
            $imageObject['url'] = str_replace($imageObject['host'], $config['internalHost'], $imageObject['url']);
        }
        $data = file_get_contents($imageObject['url'], false, $context);
        if (!$data) {
            return false;
        }
        $filePath = $importDir . $imageObject['basename'];
        file_put_contents($filePath, $data);

        $websiteId = Mage::app()->getWebsite()->getWebsiteId();
        $product->setWebsiteIds(array($websiteId));

//        unlink(Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . substr($imageObject['basename'], 0, 1) . DS . substr($imageObject['basename'], 1, 1) . DS . $imageObject['basename']);
//        echo 'delete file in ' . Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . substr($imageObject['basename'], 0, 1) . DS . substr($imageObject['basename'], 1, 1) . DS . $imageObject['basename'] . PHP_EOL;

        /* public function addImageToMediaGallery($file, $mediaAttribute=null, $move=false, $exclude=true) */
        $product->addImageToMediaGallery($filePath, $imageObject['mediaType'], true, false);
//        $attributes = $product->getTypeInstance(true)->getSetAttributes($product);
//        $attributes['media_gallery']->getBackend()->updateImage($product, $filePath, array(
//            'postion' => $key+1
//        ));
    }
    $product->save();
    return true;
}

function uploadAndDeleteImagesWithPositionAndLabel ($imageObjectList, $valueToFilter, $filterType='entity_id', $config) {
    $product = getProductObject($valueToFilter, $filterType);
    $sku = $product->getSku();
    $media = Mage::getModel('catalog/product_attribute_media_api');

    $importDir = Mage::getBaseDir('media') . DS . 'import/';
    if (!file_exists($importDir)) {
        mkdir($importDir);
    }

    $username = 'rosewill';
    $password = 'rosewillPIM';
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));

    /* delete images */
    $mediaGalleryAttribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($product->getEntityTypeId(), 'media_gallery');
    foreach ($imageObjectList['delete'] as $key => $imageObject) {
        $gallery = $product->getMediaGalleryImages();
        foreach ($gallery as $each) {
            if ($each->getId() == $imageObject['id']) {
                unlink( $each->getPath() );
                $mediaGalleryAttribute->getBackend()->removeImage($product, $each->getFile());
                $product->save();
            }
        }
    }
    /* upload images */
    foreach ($imageObjectList['add'] as $key => $imageObject) {
        if (isset($config['internalHost'])) {
            $imageObject['url'] = str_replace($imageObject['host'], $config['internalHost'], $imageObject['url']);
        }
        $url = $imageObject['url'];

        // get array of dirname, basename, extension, filename
        $pathInfo = pathinfo($url);
        switch($pathInfo['extension']){
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'jpg':
                $mimeType = 'image/jpeg';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            default :
                return false;
        }
        $fileName = $imageObject['basename'];
        $tmpFile = file_get_contents($url, false, $context);    // get file with base auth
        file_put_contents($importDir . $fileName, $tmpFile);
        $filePath = $importDir . $fileName;

        $newImage = array(
            'file' => array(
                'content' => base64_encode($filePath),
                'mime' => $mimeType,
                'name' => getFileNameWithoutExtension($imageObject['basename'])         // 不要給extension
            ),
            'label' => getFileNameWithoutExtension($imageObject['basename']),
            'position' => $imageObject['position'],
            'types' => $imageObject['mediaType'],
            'exclude' => 0,
        );

        unlink(Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . substr($imageObject['basename'], 0, 1) . DS . substr($imageObject['basename'], 1, 1) . DS . getFileNameWithoutExtension($imageObject['basename']) . '.' . $pathInfo['extension']);
        echo 'delete file in ' . Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . substr($imageObject['basename'], 0, 1) . DS . substr($imageObject['basename'], 1, 1) . DS . $imageObject['basename'] . PHP_EOL;

        $media->create($sku, $newImage);
    }
    return true;
}

function uploadImagesWithPositionAndLabel ($imageObjectList, $valueToFilter, $filterType='entity_id', $config) {
    $product = getProductObject($valueToFilter, $filterType);
    $sku = $product->getSku();
    $media = Mage::getModel('catalog/product_attribute_media_api');

    $importDir = Mage::getBaseDir('media') . DS . 'import/';
    if (!file_exists($importDir)) {
        mkdir($importDir);
    }

    $username = 'rosewill';
    $password = 'rosewillPIM';
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));

    foreach ($imageObjectList as $key => $imageObject) {
        if (isset($config['internalHost'])) {
            $imageObject['url'] = str_replace($imageObject['host'], $config['internalHost'], $imageObject['url']);
        }
        $url = $imageObject['url'];

        // get array of dirname, basename, extension, filename
        $pathInfo = pathinfo($url);
        switch($pathInfo['extension']){
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'jpg':
                $mimeType = 'image/jpeg';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            default :
                return false;
        }
        $fileName = $imageObject['basename'];
        $tmpFile = file_get_contents($url, false, $context);    // get file with base auth
        file_put_contents($importDir . $fileName, $tmpFile);
        $filePath = $importDir . $fileName;

        $newImage = array(
            'file' => array(
                'content' => base64_encode($filePath),
                'mime' => $mimeType,
                'name' => getFileNameWithoutExtension($imageObject['basename'])         // 不要給extension
            ),
            'label' => getFileNameWithoutExtension($imageObject['basename']),
            'position' => $imageObject['position'],
            'types' => $imageObject['mediaType'],
            'exclude' => 0,
        );

        unlink(Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . substr($imageObject['basename'], 0, 1) . DS . substr($imageObject['basename'], 1, 1) . DS . getFileNameWithoutExtension($imageObject['basename']) . '.' . $pathInfo['extension']);
        echo 'delete file in ' . Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . DS . substr($imageObject['basename'], 0, 1) . DS . substr($imageObject['basename'], 1, 1) . DS . $imageObject['basename'] . PHP_EOL;

        $media->create($sku, $newImage);
    }
    return true;
}

function getFileNameWithoutExtension ($fileNameWithExtension) {
    preg_match('/[a-z0-9\-]+/', $fileNameWithExtension, $match);
    if (!$match) {
        return $fileNameWithExtension;
    }
    return $match[0];
}

function getAttributeSetCollection () {

    $entityType = Mage::getModel('catalog/product')->getResource()->getTypeId();
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')
        ->setEntityTypeFilter($entityType);

    $response = array();
    foreach ($attributeSetCollection as $id => $attributeSet) {
        $entityTypeId = $attributeSet->getEntityTypeId();
        $name = $attributeSet->getAttributeSetName();
        $response[] = array(
            'id' => $entityTypeId,
            'name' => $name
        );
    }
    return $response;
}

function getDownloadableUrls ($valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);

    $downloadables = array(
        'user_manual' => 'usermanuals/usermanuals',
        'Drivers' => 'drivers/drivers',
        'Firmware' => 'firmware/firmware'
    );

    $response = array();
    foreach ($downloadables as $downloadType => $relativeModel) {
        $objectArray = Mage::getModel($relativeModel)->getCollection()->addFieldToFilter('product_id',$product->getId());
        if(count($objectArray) > 0) {
            foreach($objectArray as $object) {
                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $object->getFile();
                $parseUrl = parse_url($url);
                preg_match('/(.+[\/]{1})([^\/]+)/', $object->getFile(), $match);

                $response[] = array(
                    'id' => $object->getId(),
                    'type' => $downloadType,
                    'baseUrl' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
                    'dir' => $match[1],
                    'basename' => $match[2],
                    'host' => $parseUrl['host'],
                    'model' => $relativeModel
                );
            }
        }
    }

    return $response;
}

function uploadAndDeleteDownloadFiles ($downloadableObjectList, $valueToFilter, $filterType='entity_id', $config) {
    $product = getProductObject($valueToFilter, $filterType);
    $productId = $product->getId();

    $username = 'rosewill';
    $password = 'rosewillPIM';
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));
    foreach ($downloadableObjectList['delete'] as $downloadableObject) {
        var_dump($downloadableObject);
        unlink(Mage::getBaseDir('media') . DS . $downloadableObject['dir'] . $downloadableObject['basename']);
        echo Mage::getBaseDir('media') . DS . $downloadableObject['dir'] . $downloadableObject['basename'];
        Mage::getModel($downloadableObject['model'])
            ->load($downloadableObject['id'])
            ->delete();
//        echo "$filterType: $valueToFilter deleted $filePath" . PHP_EOL;
    }
    foreach ($downloadableObjectList['add'] as $downloadableObject) {
        $url = $downloadableObject['baseUrl'] . $downloadableObject['dir'] . $downloadableObject['basename'];
        if (isset($config['internalHost'])) {
            $url = str_replace($downloadableObject['host'], $config['internalHost'], $url);
        }
        $tmpFile = file_get_contents($url, false, $context);    // get file with base auth
        $filePath = $downloadableObject['dir'] . $downloadableObject['basename'];
        file_put_contents(Mage::getBaseDir('media') . DS . $filePath, $tmpFile);
        Mage::getModel($downloadableObject['model'])
            ->setFile($filePath)
            ->setProductId($productId)
            ->setId(null)
            ->save();
        echo "$filterType: $valueToFilter uploaded $filePath" . PHP_EOL;
    }
    return true;
}

function uploadDownloadFiles ($downloadableObjectList, $valueToFilter, $filterType='entity_id', $config) {
    $product = getProductObject($valueToFilter, $filterType);
    $productId = $product->getId();

    $username = 'rosewill';
    $password = 'rosewillPIM';
    $context = stream_context_create(array(
        'http' => array(
            'header'  => "Authorization: Basic " . base64_encode("$username:$password")
        )
    ));
    foreach ($downloadableObjectList as $downloadableObject) {
        $url = $downloadableObject['baseUrl'] . $downloadableObject['dir'] . $downloadableObject['basename'];
        if (isset($config['internalHost'])) {
            $url = str_replace($downloadableObject['host'], $config['internalHost'], $url);
        }
        $tmpFile = file_get_contents($url, false, $context);    // get file with base auth
        $filePath = $downloadableObject['dir'] . $downloadableObject['basename'];
        file_put_contents(Mage::getBaseDir('media') . DS . $filePath, $tmpFile);
        Mage::getModel($downloadableObject['model'])
            ->setFile($filePath)
            ->setProductId($productId)
            ->setId(null)
            ->save();
        echo "$filterType: $valueToFilter uploaded $filePath" . PHP_EOL;
    }
    return true;
}

function compareDownloadableWithRemoteIncludeDelete ($localDownloadable, $remoteDownloadable) {
    $response = array(
        'add' => array(),
        'delete' => array()
    );

    foreach ($remoteDownloadable as $remote) {
        $match = false;
        foreach ($localDownloadable as $local) {
            if ($remote['basename'] == $local['basename']) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            $response['add'][] = $remote;
        }
    }
    foreach ($localDownloadable as $local) {
        $match = false;
        foreach ($remoteDownloadable as $remote) {
            if ($remote['basename'] == $local['basename']) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            $response['delete'][] = $local;
        }
    }
    return $response;
}

function compareDownloadableWithRemote ($localDownloadable, $remoteDownloadable) {
    if (count($localDownloadable) < 1) {
        return $remoteDownloadable;
    }

    $response = array();
    foreach ($remoteDownloadable as $remote) {
        $match = false;
        foreach ($localDownloadable as $local) {
            if ($remote['basename'] == $local['basename']) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            $response[] = $remote;
        }
    }
    return $response;
}

function getVideoGalleryColletcion () {
    $videoGalleryCollection = Mage::getModel("videogallery/videogallery")->getCollection();
    $productvideos_collection=Mage::getModel('productvideos/productvideos')->getCollection();
    $response = array();

    foreach ($videoGalleryCollection as $videoGallery) {
        $tmpArray = $videoGallery->debug();
        $tmpArray["sku"] = array();
        foreach ($productvideos_collection as $productvideo) {
            if($tmpArray["videogallery_id"] == $productvideo->getData("videogallery_id")){
                $product_id = $productvideo->getData("product_id");
                $product = Mage::getModel('catalog/product')->load($product_id);
                $sku = $product->getSku();
                $tmpArray["sku"][] = $sku;
            }
        }
        $response[] = $tmpArray;
    }
    return $response;
}

function getVideoGalleryInfo($valueToFilter, $filterType='entity_id'){
    $product = getProductObject($valueToFilter, $filterType);
    $sku = $product->getSku();
    $videoGalleryCollection = getVideoGalleryColletcion();
    $tmpArray = array();

    foreach($videoGalleryCollection as $videoGallery){
        $skuArray = $videoGallery['sku'];
        if (in_array($sku, $skuArray)){
            unset($videoGallery['sku']);
            unset($videoGallery['gallery_image']);
            $tmpArray[] = $videoGallery;
        }
    }
    return $tmpArray;
}

function importVideoToVideoGallery ($videoGalleryObject) {
    unset($videoGalleryObject['videogallery_id']);
    unset($videoGalleryObject['created']);
    unset($videoGalleryObject['sku']);
    $queryString = parse_url( $videoGalleryObject['videogallery_url'], PHP_URL_QUERY );
    preg_match('/[=]([^&]+)/', $queryString, $match);
    $v = $match[1];
    $imageUrl = 'http://img.youtube.com/vi/'.$v.'/0.jpg';
    $videoImage = $v;

    $tmpFile = file_get_contents($imageUrl);
    file_put_contents(Mage::getBaseDir('media').DS."videogallery".DS.'videogallery_'.$videoImage.'.jpg', $tmpFile);

    $model = Mage::getModel('videogallery/videogallery');
    $model -> setData($videoGalleryObject);
    $model -> save();
    return true;
}

function linkVideoGalleryToProduct ($gallery_id, $valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);
    $productId = $product->getId();
    if (!$productId) {
        return false;
    }
    $productVideos=Mage::getModel('productvideos/productvideos');
    $productVideos->setProductId($productId);
    $productVideos->setVideogalleryId($gallery_id);
    $productVideos->save();
    return true;
}

function importVideoToGalleryAndLinkToProduct ($videoGalleryObject) {
    $skuArray = $videoGalleryObject['sku'];
    $videogallery_url = $videoGalleryObject['videogallery_url'];

    $modelGallery = Mage::getModel('videogallery/videogallery')->load($videogallery_url, 'videogallery_url');
    $gallery_id = $modelGallery->getVideogalleryId();
    if (!$gallery_id) {
        importVideoToVideoGallery($videoGalleryObject);
    }

    $modelGallery = Mage::getModel('videogallery/videogallery')->load($videogallery_url, 'videogallery_url');
    $gallery_id = $modelGallery->getVideogalleryId();
    foreach ($skuArray as $sku) {
        linkVideoGalleryToProduct($gallery_id, $sku, 'sku');
    }
    return true;
}

function compareVideoGalleryList ($localList, $remoteList) {
    $needToImportList = array();
    foreach ($remoteList as $remoteEach) {
        $flag = false;
        $missingSku = false;
        foreach ($localList as $localEach) {
            if ($remoteEach['videogallery_url'] == $localEach['videogallery_url']) {
                $flag = true;
                if (count($remoteEach['sku']) > count($localEach['sku'])) {
                    $missingSku = true;
                }
            }
        }
        if (!$flag) {
            $needToImportList['gallery'][] = $remoteEach;
        }
        if ($missingSku) {
            $needToImportList['sku'][] = $remoteEach;
        }
    }
    return $needToImportList;
}

function getAttributeSetAndSubcategoryMappingTable ($filePath) {
    $excelDataArray = parseXlsxIntoArray($filePath, 1, 1);
    foreach ($excelDataArray as $index => $row) {
        $excelDataArray[$index]['Sub Category'] = explode(PHP_EOL, $row['Sub Category']);
    }
    return $excelDataArray;
}

/*
 * $inputType => 'attributeSet' or 'subCategory'
 * response of subCategory will be an array
 * */
function getMappedAttributeSetOrSubcategory ($filePath, $inputValue, $inputType) {
    $attributeSetAndSubcategoryMappingTable = getAttributeSetAndSubcategoryMappingTable($filePath);
    $attrSetArray = array();
    foreach ($attributeSetAndSubcategoryMappingTable as $eachMapping) {
        switch ($inputType) {
            case 'attributeSet' :
                if (strtolower($eachMapping['Attribute Set Name']) == strtolower($inputValue)) {
                    return $eachMapping;
                }
                break;
            case 'subCategory' :
                /* run strtolower to an array */
                $eachMapping['Sub Category'] = array_map('strtolower', $eachMapping['Sub Category']);
                if (in_array(strtolower($inputValue), $eachMapping['Sub Category'])) {
                    $attrSetArray[] = $eachMapping['Attribute Set Name'];
                }
                break;
            default :
                return null;
        }
    }

    /*
     * return response for case subCategory
     * */
    return array(
        'Sub Category' => $inputValue,
        'Attribute Set Name' => $attrSetArray
    );
}

function exportArrayToXlsx ($exportArray, $exportParam) {

    PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

    $objPHPExcel = new PHPExcel();

    // Set properties
    $objPHPExcel->getProperties()->setCreator($exportParam['title'])
        ->setLastModifiedBy($exportParam['title'])
        ->setTitle($exportParam['title'])
        ->setSubject($exportParam['title'])
        ->setDescription($exportParam['title'])
        ->setKeywords($exportParam['title'])
        ->setCategory($exportParam['title']);

    // Set active sheet
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->setTitle($exportParam['title']);

    // Set cell value
    //rows are 1-based whereas columns are 0-based, so “A1″ becomes (0,1).
    //$objPHPExcel->setCellValueByColumnAndRow($column, $row, $value);
    //$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, "This is A1");
    for($row = 0; $row < count($exportArray); $row++){
        ksort($exportArray[$row]);  // sort by key
        foreach ($exportArray[$row] AS $key => $value){
            // Find key index from first row
            $key_index = -1;
            if (array_key_exists($key, $exportArray[0])){
                $key_index = array_search($key, array_keys($exportArray[0]));
            }

            // Set key(column name)
            if($row==0){
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key_index, 1, $key);
            }

            //   var_dump($key);

            if($key_index != -1){

                switch ($key) {

                    case 'createDate' :
                    case 'mtime' :
                        if($value!=null && $value> 25569){
                            $value=(($value/86400)+25569); //  change  database  timestamp to date for excel .
                        }

                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key_index, $row+2, $value);
                        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($key_index, $row+2)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD);
                        //  var_dump($key.$value);
                        break;

                    default:
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key_index, $row+2, $value);
                    //    var_dump($key.$value);

                }
                // Set Value (each row)


            }else{
                // Can not find $key in $row
            }

        }
    }

    // Browser download
    if (strcmp("php://output", $exportParam['filename'])==0){
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="FixedAssets.xls"');
        header('Cache-Control: max-age=0');
    }

    // Write to file
    // If you want to output e.g. a PDF file, simply do:
    //$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save($exportParam['filename']); // Excel2007 : '.xlsx'   Excel5 : '.xls'

    echo json_encode(array('message' => 'success'));
}

/*
 * use for export whole items
 * */
function getCountNumberOfProducts () {
    $productCollection = Mage::getModel('catalog/product')->getCollection();
    return count($productCollection);
}

function getProductInfoFromMagentoForExport ($pageSize, $pageNumber = 1, $noOutputAttr) {
    $productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')
        ->setOrder('entity_id', 'ASC')->setPageSize($pageSize)->setCurPage($pageNumber);

    $response = array();
    foreach ($productCollection as $product) {
        $tempArray = array();
        foreach ($product->debug() as $attr => $attrValue) {
            if (!in_array($attr, $noOutputAttr)) {
                $tempArray[$attr] = $attrValue;
            }
        }

        $tempArray['category'] = getProductCategoryNames($product->getId());

        $withDownloadableFile = false;
        $user_manuals=Mage::getModel('usermanuals/usermanuals')->getCollection()->addFieldToFilter('product_id',$product['entity_id']);
        if ( count($user_manuals) > 0 ) {
            $withDownloadableFile = true;
        }
        $drivers=Mage::getModel('drivers/drivers')->getCollection()->addFieldToFilter('product_id',$product['entity_id']);
        if ( count($drivers) > 0 ) {
            $withDownloadableFile = true;
        }
        $firmwares=Mage::getModel('firmware/firmware')->getCollection()->addFieldToFilter('product_id',$product['entity_id']);
        if ( count($firmwares) > 0 ) {
            $withDownloadableFile = true;
        }
        if ($withDownloadableFile) {
            $tempArray['attachment'] = 'yes';
        }

        $response[] = $tempArray;
    }
    return $response;
}

function parseProductAttributesForExport ($magentoProductInfo) {
    $response = array();
    foreach ($magentoProductInfo as $attrKey => $attrValue) {
        switch ($attrKey) {
            case 'attribute_set_id' :
                $attrIdName = attributeSetNameAndId('attributeSetId', $attrValue);
                $response[$attrKey] = $attrIdName['name'];
                break;
            default :
                $response[$attrKey] = getAttributeValueFromOptionsForExport('attributeName', $attrKey, $attrValue);;
        }
    }
    return $response;
}

function getAttributeValueFromOptionsForExport ($nameOrId, $attrCodeOrId, $valueToBeMapped) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    file_put_contents('log.txt', $attrCodeOrId . ': ' . $valueToBeMapped . PHP_EOL, FILE_APPEND);
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);
    if ($optionsArray && isset($optionsArray['frontend_input']) ) {
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
                    return join(',', $mappedArray);
                }
                break;
            case 'text' :
            case 'textarea' :
            case 'price' :
            case 'date' :
            case 'weight' :
            case 'media_image' :
                return $valueToBeMapped;
                break;
            default :
                return "******** " . $optionsArray['frontend_input'] . " ********";
        }
    }
    return $valueToBeMapped;
}

function getProductCategoryNames ($valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);
    $categoryCollection = $product->getCategoryCollection()->addAttributeToSelect('name');
    $categoryNamesArray = array();
    foreach ($categoryCollection as $each) {
        $categoryNamesArray[] = $each->getName();
    }
    return implode(PHP_EOL, $categoryNamesArray);
}

function getProductCategorysInfo ($valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);
    $categoryCollection = $product->getCategoryCollection()->addAttributeToSelect('name');
    $categoryNamesArray = array();
    foreach ($categoryCollection as $each) {
        $categoryNamesArray[] = array(
            'name' => $each->getName(),
            'level' => $each->getLevel()
        );
    }
    return $categoryNamesArray;
}

function changeToInStockAndSetQty ($valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);
    $product_id = $product->getId();

    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    if (!$stockItem->getData('manage_stock')) {
        echo 'not managed by stock' . PHP_EOL;
        $stockItem->setData('product_id', $product_id);
        $stockItem->setData('stock_id', 1);
        $stockItem->save();

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
    }
    echo json_encode($stockItem->getData(), JSON_PRETTY_PRINT) . PHP_EOL;
    $stockItem->setData('manage_stock', 1);
    $stockItem->setData('is_in_stock', 1);
    $stockItem->setData('qty', 100);
    $stockItem->save();
    return true;
}

function getAllCategorysInfo () {
    $categoryCollection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('name');
    $response = array();
    foreach ($categoryCollection as $category) {
        $response[] = array(
            'entity_id' => $category['entity_id'],
            'name' => $category['name'],
            'path' => $category['path'],
            'level' => $category['level']
        );
    }
    return $response;
}

function getSingleCategoryInfo ($valueToFilter, $filterType, $level) {
    $allCategoryInfo = getAllCategorysInfo();
    foreach ($allCategoryInfo as $categoryInfo) {
        if ((string)$categoryInfo[$filterType] == (string)$valueToFilter) {
            echo 'category name mapped' . PHP_EOL;
            if ((string)$categoryInfo['level'] == (string)$level) {
                echo 'category level mapped' . PHP_EOL;
                return $categoryInfo;
            }
        }
    }
    return null;
}

function setProductCategoryIds ($valueToFilter, $filterType='entity_id', $categoryArray) {
    echo "set category for $filterType : $valueToFilter" . PHP_EOL;
    $product = getProductObject($valueToFilter, $filterType);
    $product_id = $product->getId();
    $categoryIds = array();
    foreach ($categoryArray as $category) {
        $categoryInfo = getSingleCategoryInfo($category['name'], 'name', $category['level']);
        if ($categoryInfo) {
            $categoryIds[] = $categoryInfo['entity_id'];
            echo $category['name'] . " map to id: " . $categoryInfo['entity_id']. PHP_EOL;
        }
    }
    $product->setCategoryIds($categoryIds);
    $product->setUrlKey(false);
    $product->save();
}

function getAllWebisteIds () {
    $_websites = Mage::app()->getWebsites();
    $websiteIds = array();
    foreach ($_websites as $website) {
        $websiteIds[] = $website->getId();
    }
    return $websiteIds;
}

function getAllStoreIds () {
    $allStores = Mage::app()->getStores();
    $storeIds = array();
    foreach ($allStores as $_eachStoreId => $val) {
        $_storeCode = Mage::app()->getStore($_eachStoreId)->getCode();
        $_storeName = Mage::app()->getStore($_eachStoreId)->getName();
        $_storeId = Mage::app()->getStore($_eachStoreId)->getId();
        $storeIds[] = $_storeId;
    }
    return $storeIds;
}

function getStoreCodeById ($storeId) {
    return $_storeCode = Mage::app()->getStore($storeId)->getCode();
}

function getStoreIdByCode ($storeCode) {
    $allStores = Mage::app()->getStores();
    foreach ($allStores as $_eachStoreId => $val) {
        $eachStoreCode = Mage::app()->getStore($_eachStoreId)->getCode();
        if ($storeCode ==  $eachStoreCode) {
            return $_eachStoreId;
        }
    }
    return null;
}

function createCustomerNotExist ($customerInfo) {
    $customerCollection = Mage::getModel('customer/customer')->getCollection()
        ->addFieldToFilter('email', $customerInfo['email']);
    if (count($customerCollection) > 0) {
        foreach ($customerCollection as $eachCustomer) {
            return $eachCustomer->getEntityId();
        }
    }

    $customerModel = Mage::getModel('customer/customer');
    foreach ($customerInfo as $attr => $value) {
        switch ($attr) {
            case 'email' :
            case 'is_active' :
            case 'firstname' :
            case 'lastname' :
            case 'group_id' :
                echo 'set ' . $attr . ' value: ' . $value . PHP_EOL;
                $customerModel->setData($attr, $value);
        }
    }

    try {
        $customerModel->save();
        return $customerModel->getEntityId();
    } catch (Exception $e) {
        Zend_Debug::dump($e->getMessage());
    }
}

function createReviewAndRating ($reviewData, $ratingData, $entity_id, $customer_id) {
    $reviewModel = Mage::getModel('review/review');
    try {
        foreach ($reviewData as $attr => $value) {
            switch ($attr) {
                //case 'review_id' :
                case 'title' :
                case 'entity_id' :
                case 'detail' :
                case 'status_id' :
                case 'nickname' :
                    $reviewModel->setData($attr, $value);
                    break;
                case 'customer_id' :
                    $reviewModel->setCustomerId($customer_id);
                    break;
                case 'created_at' :
                    $reviewModel->setData($attr, strtotime($value));
                    break;
                case 'entity_pk_value' :
                    $reviewModel->setData($attr, $entity_id);
                    break;
                case 'storeCode' :
                    $storeId = getStoreIdByCode($value);
                    $reviewModel->setStoreId($storeId);
                    break;

            }
        }
        if(isset($reviewData['stores'])) {
            $reviewModel->setData('stores', $reviewData['stores']);
        }
        else{
            $reviewModel->setData('stores', getStoreIdByCode($reviewData['storeCode']));
        }
        $reviewModel->save();

        Mage::getModel('rating/rating')
            ->setRatingId($ratingData['rating_id'])
            ->setReviewId($reviewModel->getId())
            ->addOptionVote($ratingData['value'], $entity_id);

        $reviewModel->aggregate();
    } catch (Mage_Core_Exception $e) {
        var_dump($e->getMessage());
    }
}

function updateReviewStatus ($reviews, $status) {
    foreach ($reviews as $reviewData) {
        $reviewCollection = Mage::getModel('review/review')->getCollection()
            ->addFieldToFilter('title', $reviewData['title'])
            ->addFieldToFilter('detail', $reviewData['detail'])
            ->addFieldToFilter('nickname', $reviewData['nickname']);
        foreach ($reviewCollection as $each) {
            $each->setStatusId($status);
            $each->setStores($reviewData['stores']);
            $each->save();
            echo json_encode($each->getData());
        }
    }
}


/* getSpecificReview */
function getSpecificReview ($reviewData) {
    $reviewCollection = Mage::getModel('review/review')->getCollection()
        ->addFieldToFilter('title', $reviewData['title'])
        ->addFieldToFilter('detail', $reviewData['detail'])
        ->addFieldToFilter('nickname', $reviewData['nickname']);
    foreach ($reviewCollection as $each) {
        $deletedReviewId = $each->getReviewId();
        return $deletedReviewId;
    }
    return null;
}

function createContactusForm($contactusFormData){
    $contactusModel = Mage::getModel('contactus/contactusform');
    try{
        $contactusModel->setFormType($contactusFormData['form_type'])
            ->setCreatedAt($contactusFormData['created_at'])
            ->setUpdatedAt($contactusFormData['updated_at'])
            ->setContent($contactusFormData['content'])
            ->setPurposeForContact($contactusFormData['purpose_for_contact'])
            ->save();
    }
    catch (Exception $e){
        var_dump($e->getMessage());
    }
}

function massDeleteContactusForm($contactusFormData){
    foreach ($contactusFormData as $eachData) {
        $contactusCollection = Mage::getModel('contactus/contactusform')->getCollection();
        if(isset($eachData['content'])){
            $contactusCollection->addFieldToFilter('content', $eachData['content']);
            if(count($contactusCollection) > 1 && isset($eachData['form_type'])){
                $contactusCollection->addFieldToFilter('form_type', $eachData['form_type']);
                if(count($contactusCollection) > 1 && isset($eachData['purpose_for_contact'])) {
                    $contactusCollection->addFieldToFilter('purpose_for_contact', $eachData['purpose_for_contact']);
                    if(count($contactusCollection) > 1 && isset($eachData['created_at'])) {
                        $contactusCollection->addFieldToFilter('created_at', $eachData['created_at']);
                        if(count($contactusCollection) > 1 && isset($eachData['updated_at'])) {
                            $contactusCollection->addFieldToFilter('updated_at', $eachData['updated_at']);
                        }
                    }
                }
            }
        }
        if (count($contactusCollection) > 0) {
            $id = $contactusCollection->getData()[0]['id'];
            try {
                Mage::getModel('contactus/contactusform')->load($id)->delete();
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }
        }
    }
}

function getLatestChannelsProductReviews ($channel, $sku) {
    /* need to include ganon.php */
    $response = array();
    switch ($channel) {
        case 'newegg' :
            $html = file_get_dom('http://www.newegg.com/Product/Product.aspx?Item='.$sku.'&Pagesize=100');
            foreach($html('#Community_Content .grpReviews tr td .details') as $element) {
                $nickname = $element->parent->parent->getChild(1)->getChild(1)->getChild(1)->getPlainText();
                $created = $element->parent->parent->getChild(1)->getChild(1)->getChild(3)->getPlainText();

                /* ratingText => 'Rating: 4/5' */
                $ratingText = $element->parent->parent->getChild(3)->getChild(3)->getChild(0)->getPlainText();
                preg_match('/(\d).?\/.?\d/', $ratingText, $match);
                if(count($match) == 2){
                    $rating = $match[1];
                }

                if($element->parent->parent->getChild(3)->getChild(3)->getChild(1)) {
                    $subject = $element->parent->parent->getChild(3)->getChild(3)->getChild(1)->getPlainText();
                }
                else{
                    $subject = null;
                }

                $response[] = array(
                    'detail' => htmlentities(trim($element->getPlainText())),
                    'nickname' => htmlentities($nickname),
                    'subject' => htmlentities($subject),
                    'created_at' => $created,
                    'rating' => $rating
                );
            }
            break;
    }
    return $response;
}

/*
 *'\', '/', '?' make magento addAttributeToFilter equal function inactive, so replace them with sql wildcard character '_' and use 'like' search
 * '\' => '\\\\'
 * '/' => '\/'
 * '?' => '?'
*/
function replaceSpecialCharacters($input){
    return preg_replace('/[\\\\\/?]/', '_', $input);
}

function getInformationFromIntelligence ($itemNumber, $returnResponse = false) {
    global $app;
    global $intelligenceBaseUrl;
    $restPostfix = '/itemservice/detail';
    $data = array(
        "CompanyCode" => 1003,
        "CountryCode" => "USA",
        "Fields" => null,
        "Items" => array(
            array("ItemNumber" => $itemNumber)
        ),
        "RequestModel" => "RW"
    );
    $header = array('Content-Type: application/json', 'Accept: application/json');
    $response = CallAPI('POST', $intelligenceBaseUrl . $restPostfix, $header, $data);
    if ($returnResponse) {
        return $response;
    }
    echo json_encode(array(
        'status' => 'success',
        'DataCollection' => array(
            'ItemNumber' => $response['detailinfo'][0]['ItemNumber'],
            'DetailSpecification' => $response['detailinfo'][0]['DetailSpecification'],
            'Introduction' => $response['detailinfo'][0]['Introduction'],
            'IntroductionImage' => $response['detailinfo'][0]['IntroductionImage'],
            'Intelligence' => $response['detailinfo'][0]['Intelligence']
        )
    ));
}
