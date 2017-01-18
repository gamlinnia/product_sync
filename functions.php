<?php

function isJson($string) {
    json_decode(trim($string));
    return (json_last_error() == JSON_ERROR_NONE);
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
        $productId = $product->getId();
        $productDataArray = Mage::getModel('catalog/product')->load($productId)->getData();
        $productDataArray['category'] = getProductCategorysInfo($productId);
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
    if (is_array($element)) {
        return false;
    }
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
            case 'special_from_date' :
            case 'special_to_date' :
                if ($attrValue) {
                    $parsedProductInfo[$attrKey] = strtotime($attrValue);
                } else {
                    $parsedProductInfo[$attrKey] = null;
                }
                break;
            default :
                $parsedProductInfo[$attrKey] = $attrValue;
                break;
        }
    }
    return $parsedProductInfo;
}

function getAttributeValueIdFromOptions ($nameOrId, $attrCodeOrId, $valueToBeMapped) {
    echo 'processing getAttributeValueIdFromOptions: ' . $nameOrId . ' ' . $attrCodeOrId . ' value: ' . json_encode($valueToBeMapped) . PHP_EOL;

    /*$nameOrId = 'attributeName' or 'attributeId'*/
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);

    var_dump($optionsArray);

    switch ($optionsArray['frontend_input']) {
        case 'select' :
        case 'boolean' :
            foreach ($optionsArray['options'] as $optionObject) {
                echo 'getAttributeValueIdFromOptions - ' . strtolower(trim($optionObject['label'])) . ' : ' . strtolower(trim($valueToBeMapped)) . PHP_EOL;
                if (strtolower(trim($optionObject['label'])) == strtolower(trim($valueToBeMapped))) {
                    return $optionObject['value'];
                }
            }
            break;
        case 'multiselect' :
            /*multiselect : a02030_headsets_connector,
                       "a02030_headsets_connector": "147,148,149,150"*/

            if (!is_array($valueToBeMapped)) {

                $dividers = array('\/', '&', ',');
                $valueToBeMappedArray = preg_split("/(" . join('|', $dividers) . ")/", $valueToBeMapped);

                $function_to_map = array('trim', 'ucwords');
                foreach ($function_to_map as $function) {
                    $valueToBeMappedArray = array_map($function, $valueToBeMappedArray);
                }

                echo '$valueToBeMappedArray: ' . json_encode($valueToBeMappedArray) . PHP_EOL . PHP_EOL;
            } else {
                $valueToBeMappedArray = $valueToBeMapped;
            }

            $newOptions = array();
            foreach ($optionsArray['options'] as $optionObject) {
                $newOptions[$optionObject['value']] = $optionObject['label'];
            }

            $mappedArray = array();
            foreach ($valueToBeMappedArray as $eachToBeMapped) {
                $pregResponse = preg_grep( '/^' . $eachToBeMapped . '$/i' ,  $newOptions) ;

                if (count($pregResponse) < 1) {
                    return null;
                }

                foreach ($pregResponse as $value => $label) {
                    $mappedArray[] = $value;
                }
            }

            return join(',', $mappedArray);
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

function CallAPI($method, $url, $header = null, $data = false, $timeout = null) {
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
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
    curl_setopt($curl, CURLOPT_USERAGENT, $agent);

    $result = curl_exec($curl);

    curl_close($curl);

    if (isJson($result)) {
        return json_decode($result, true);
    }
    return $result;
}

function getImagesUrlOfProduct ($valueToFilter, $type='entity_id') {
    switch ($type) {
        case 'entity_id' :
            $product = Mage::getModel('catalog/product')->load($valueToFilter);
            break;
        case 'sku' :
            $productModel = Mage::getModel('catalog/product');
            $product = $productModel->load($productModel->getIdBySku($valueToFilter));
            break;
        default :
            echo 'need to write code in getImagesUrlOfProduct' . PHP_EOL;
            exit(0);
    }

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
        'label' => $imageObject->getLabel(),
        'url' => $imageUrl,
        'basename' => $pathInfo['basename'],
        'host' => $parseUrl['host'],
        'mediaType' => $imageMediaType
    );

    return $response;
}

function getFileNameFromUrl ($url) {
    preg_match('/[\/]([a-z0-9\-_.]+\.[a-z]{3,4})$/i', $url, $match);
    if (is_array($match) && count($match) > 1) {
        return $match[1];
    }
    return false;
}

function getProductObject ($valueToFilter, $filterType='entity_id') {
    switch ($filterType) {
        case 'sku' :
            $product = Mage::getSingleton('catalog/product');
            $productObject = $product->load($product->getIdBySku($valueToFilter));
            break;
        default :
            /* filter by entity id */
            $productObject = Mage::getSingleton('catalog/product')->load($valueToFilter);
    }
    return $productObject;
}

function compareImageWithRemoteIncludeDelete ($localImages, $remoteImages) {
    Mage::log('local image:', null, 'sync.log', true);
    Mage::log($localImages, null, 'sync.log', true);
    Mage::log('remote image:', null, 'sync.log', true);
    Mage::log($remoteImages, null, 'sync.log', true);

    $response = array(
        'add' => array(),
        'edit' => array(),
        'delete' => array()
    );
    foreach ($remoteImages as $remote) {
        $match = false;
        $edit = false;
        foreach ($localImages as $local) {
            if (strtolower(substr($local['basename'], 0, 2)) == 'cs' && strtolower(substr($local['basename'], 0, 2)) == strtolower(substr($remote['basename'], 0, 2))) {
                $match = true;
                break;
            } else {

                preg_match('/[\d]{2}-[\d]{3}-[\d]{3}-[A-Za-z]?[\d]{2}/', $remote['basename'], $remoteMatch);
                preg_match('/[\d]{2}-[\d]{3}-[\d]{3}-[A-Za-z]?[\d]{2}/', $local['basename'], $localMatch);
                if (isset($remoteMatch[0]) && isset($localMatch[0]) && $remoteMatch[0] == $localMatch[0]) {

                    if ( count($remote['mediaType']) > count($local['mediaType'])) {
                        $edit = true;
                    }
                    if ($local['position'] != $remote['position']) {
                        $edit = true;
                    }
                    if ($local['label'] != $remote['label']) {
                        $edit = true;
                    }
                    if ($edit) {
                        $response['edit'][] = $remote;
                    }

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

        if ( strtolower(substr($local['basename'], 0, 2)) == 'cs' ) {
            $response['delete'][] = $local;
        }

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

    Mage::log($response, null, 'sync.log', true);
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
    switch ($filterType) {
        case 'entity_id' :
            $product = Mage::getModel('catalog/product')->load($valueToFilter);
            break;
        case 'sku' :
            $product = Mage::getModel('catalog/product')->load(
                Mage::getModel('catalog/product')->getIdBySku($valueToFilter)
            );
            break;
        default :
            echo 'need to write code in uploadAndDeleteImagesWithPositionAndLabel';
            exit(0);
    }

    $importDir = Mage::getBaseDir('media') . DS . 'import/';
    if (!file_exists($importDir)) {
        mkdir($importDir);
    }

    Mage::log($imageObjectList, null, 'sync.log', true);

    /* upload images */
    foreach ($imageObjectList['add'] as $key => $imageObject) {
        if (isset($config['internalHost'])) {
            $imageObject['url'] = str_replace($imageObject['host'], $config['internalHost'], $imageObject['url']);
        }
        uploadProductImageByNewModule($product, $imageObject['url'], $imageObject['position'], $imageObject['label'], $imageObject['mediaType']);
    }

    /* edit image, change priority and mediaType */
    switch ($filterType) {
        case 'entity_id' :
            $product = Mage::getModel('catalog/product')->load($valueToFilter);
            break;
        case 'sku' :
            $product = Mage::getModel('catalog/product')->load(
                Mage::getModel('catalog/product')->getIdBySku($valueToFilter)
            );
            break;
    }
    $productImages = array();
    foreach ($product->getMediaGalleryImages() as $image) {
        preg_match('/[\d]+-[\d]+-[\d]+-[A-Za-z]?[\d]+/', $image->getFile(), $skuMatch);
        if (isset($skuMatch[0])) {
            $productImages[] = array(
                'match' => $skuMatch[0],
                'file' => $image->getFile()
            );
        }
    }

    foreach ($imageObjectList['edit'] as $imageObject) {
        if (!empty($imageObject['mediaType'])) {
            $url = $imageObject['url'];
            preg_match('/media\/catalog\/product(.+)/', $url, $match);
            if (isset($match[1])) {
                preg_match('/[\d]+-[\d]+-[\d]+-[A-Za-z]?[\d]+/', $match[1], $oriSkuMatch);
                $skuToSet = '';
                if (isset($oriSkuMatch[0])) {
                    $newMediaTypeToMatch = $oriSkuMatch[0];

                    /* 比對產品圖片中是否有對應的檔名 */
                    foreach ($productImages as $imageObj) {
                        if ($newMediaTypeToMatch==$imageObj['match']) {
                            $skuToSet = $imageObj['file'];
                        }
                    }
                }

                if (in_array('image', $imageObject['mediaType'])) {
                    if (!empty($skuToSet)) {
                        $product->setImage($skuToSet);
                    } else {
                        $product->setImage($match[1]);
                    }
                }
                if (in_array('small_image', $imageObject['mediaType'])) {
                    if (!empty($skuToSet)) {
                        $product->setSmallImage($skuToSet);
                    } else {
                        $product->setSmallImage($match[1]);
                    }
                }
                if (in_array('thumbnail', $imageObject['mediaType'])) {
                    if (!empty($skuToSet)) {
                        $product->setThumbnail($skuToSet);
                    } else {
                        $product->setThumbnail($match[1]);
                    }
                }
                $product->save();
            }

        }

        preg_match('/[\d]+-[\d]+-[\d]+-[A-Za-z]?[\d]+/', $imageObject['basename'], $fileName);

        $mediagalleryCollection = Mage::getModel('coreproductmediagallery/mediagalleryvalue')
            ->getCollection()
            ->addFieldToFilter('store_id', 0)
            ->addFieldToFilter('value', array('like' => '%' . $fileName[0] . '%'))
            ->join(array('gallery' => 'coreproductmediagallery/mediagallery'),'main_table.value_id = gallery.value_id',array('gallery.value'));

        if ((int)$mediagalleryCollection->count() == 1) {
            $mediagalleryCollection->getFirstItem()
                ->setPosition($imageObject['position'])
                ->setLabel($imageObject['label'])
                ->save();
        }
    }

    /* delete images */
    $storeIds = array_merge(array('0'), getAllStoreIds());  // admin store id + store ids
    $mediaGalleryAttribute = Mage::getModel('catalog/resource_eav_attribute')->loadByCode($product->getEntityTypeId(), 'media_gallery');
    foreach ($imageObjectList['delete'] as $key => $imageObject) {
        $gallery = $product->getMediaGalleryImages();
        foreach ($gallery as $each) {
            if ($each->getId() == $imageObject['id']) {
                echo 'delete path: ' . $each->getPath() . ' ID: ' . $imageObject['id'] . PHP_EOL;
                unlink( $each->getPath() );
                Mage::getModel('coreproductmediagallery/mediagallery')->load($imageObject['id'])->delete();
            }
        }
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

function getDownloadableUrls ($valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);

    $collection = Mage::getModel('downloadablefile/associatedproduct')
        ->getCollection();

    $collection->getSelect()
        ->join(
            array('file' => 'downloadablefile_file_list'),
            'main_table.file_list_id = file.id',
            array('file' => 'file.file', 'type' => 'file.type', 'comment' => 'file.comment')
        );

    $collection->addFieldToFilter('product_id', $product->getId());

    $response = array();
    if ($collection->count() > 0) {
        foreach ($collection as $eachAssociated) {
            $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $eachAssociated->getFile();
            $parseUrl = parse_url($url);
            preg_match('/(.+[\/]{1})([^\/]+)/', $eachAssociated->getFile(), $match);

            $response[] = array(
                'id' => $eachAssociated->getId(),
                'type' => $eachAssociated->getType(),
                'baseUrl' => Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA),
                'dir' => $match[1],
                'basename' => $match[2],
                'comment' => $eachAssociated->getComment(),
                'host' => $parseUrl['host']
            );

        }
    }

    return $response;
}

function uploadAndDeleteDownloadFiles ($downloadableObjectList, $valueToFilter, $filterType='entity_id', $config) {
    switch ($filterType) {
        case 'entity_id' :
            $product = Mage::getSingleton('catalog/product')->load($valueToFilter);
            break;
        case 'sku' :
            $product = Mage::getSingleton('catalog/product')->load(Mage::getModel('catalog/product')->getIdBySku($valueToFilter));
            break;
        default :
            echo 'need to write code in uploadAndDeleteImagesWithPositionAndLabel';
            exit(0);
    }
    $productId = $product->getId();

    foreach ($downloadableObjectList['delete'] as $downloadableObject) {
        var_dump($downloadableObject);
//        unlink(Mage::getBaseDir('media') . DS . $downloadableObject['dir'] . $downloadableObject['basename']);
        echo Mage::getBaseDir('media') . DS . $downloadableObject['dir'] . $downloadableObject['basename'] . ' will be removed from db.' . PHP_EOL;
        Mage::getModel('downloadablefile/associatedproduct')
            ->load($downloadableObject['id'])
            ->delete();
//        echo "$filterType: $valueToFilter deleted $filePath" . PHP_EOL;
    }

    foreach ($downloadableObjectList['edit']as $downloadableObject) {
        Mage::log($downloadableObject, null, 'sync.log', true);
        $filePath = $downloadableObject['dir'] . $downloadableObject['basename'];
        $file_list_collection = Mage::getModel('downloadablefile/filelist')->getCollection()
            ->addFieldToFilter('file', $filePath)
            ->addFieldToFilter('type', $downloadableObject['type']);
        if ($file_list_collection->count() > 0) {
            $file_list_collection->getFirstItem()
                ->setData('comment', $downloadableObject['comment'])
                ->save();
            Mage::log($file_list_collection->getFirstItem()->getData(), null, 'sync.log', true);
        }
        echo "edit comment: " . $downloadableObject['basename'] . " change comment to: " . $downloadableObject['comment'] . PHP_EOL;
    }

    /* downloadable files need to be added */
    foreach ($downloadableObjectList['add'] as $downloadableObject) {
        $url = $downloadableObject['baseUrl'] . $downloadableObject['dir'] . $downloadableObject['basename'];
        if (isset($config['internalHost'])) {
            $url = str_replace($downloadableObject['host'], $config['internalHost'], $url);
        }
        $tmpFile = file_get_contents($url);
        $filePath = $downloadableObject['dir'] . $downloadableObject['basename'];
        file_put_contents(Mage::getBaseDir('media') . DS . $filePath, $tmpFile);
        $file_list_collection = Mage::getModel('downloadablefile/filelist')->getCollection()
            ->addFieldToFilter('file', $filePath)
            ->addFieldToFilter('type', $downloadableObject['type']);
        if ($file_list_collection->count() < 1) {
            $model = Mage::getModel('downloadablefile/filelist')
                ->setData('file', $filePath)
                ->setData('type', $downloadableObject['type'])
                ->setData('comment', $downloadableObject['comment'])
                ->save();
            $file_list_id = $model->getId();
        } else {
            $file_list_id = $file_list_collection->getFirstItem()->getId();
        }

        Mage::getModel('downloadablefile/associatedproduct')
            ->setData('file_list_id', $file_list_id)
            ->setProductId($productId)
            ->save();
        echo "$filterType: $valueToFilter uploaded $filePath" . PHP_EOL;
    }

    foreach ($downloadableObjectList['edit'] as $downloadableObject) {

    }
    return true;
}

/*function uploadDownloadFiles ($downloadableObjectList, $valueToFilter, $filterType='entity_id', $config) {
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
}*/

function compareDownloadableWithRemoteIncludeDelete ($localDownloadable, $remoteDownloadable) {
    $response = array(
        'add' => array(),
        'edit' => array(),
        'delete' => array()
    );

    foreach ($remoteDownloadable as $remote) {
        $match = false;
        foreach ($localDownloadable as $local) {
            if ($remote['basename'] == $local['basename']) {
                $match = true;

                if ($remote['comment'] != $local['comment']) {
                    $response['edit'][] = $remote;
                }

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

    Mage::getModel('videogallery/videogallery')
        ->setData($videoGalleryObject)
        ->setCreated(strtotime('now'))
        ->save();
    return true;
}

function linkVideoGalleryToProduct ($gallery_id, $valueToFilter, $filterType='entity_id') {
    $product = getProductObject($valueToFilter, $filterType);
    $productId = $product->getId();
    if (!$productId) {
        return false;
    }
    /*de-duplicate*/
    $collection = Mage::getModel('productvideos/productvideos')
        ->getCollection()
        ->addFieldToFilter('product_id', $productId)
        ->addFieldToFilter('videogallery_id', $gallery_id);

    if($collection->count() > 0){
        return false;
    }
    $productVideos = Mage::getModel('productvideos/productvideos');
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
        //ksort($exportArray[$row]);  // sort by key
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

    echo json_encode(array('message' => 'success')) . PHP_EOL;
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

function getCategoryByName ($category_name) {
    $category_collection = Mage::getModel('catalog/category')->getCollection()
        ->addAttributeToFilter('name', $category_name);
    if ($category_collection->count() < 1) {
        return null;
    }
    return Mage::getModel('catalog/category')->load(
        $category_collection->getFirstItem()->getId()
    );
}

function getCategoryIdArrayByCategoryName ($category_name) {
    $category_collection = Mage::getModel('catalog/category')->getCollection()
        ->addAttributeToFilter('name', $category_name);
    if ($category_collection->count() < 1) {
        return null;
    }
    $path = Mage::getModel('catalog/category')->load(
        $category_collection->getFirstItem()->getId()
    )->getPath();
    /* shift root category id away */
    $pathArray =  explode('/', $path);

    return $pathArray;
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

    if ($filterType == 'sku') {
        $product = Mage::getModel('catalog/product')->load(
            Mage::getModel('catalog/product')->getIdBySku($valueToFilter)
        );
    } else {
        echo 'check setProductCategoryIds' . PHP_EOL;
        exit(0);
        /* getProductObject when filter type = sku will cause problem. */
        $product = getProductObject($valueToFilter, $filterType);
    }

    $categoryIds = array();
    foreach ($categoryArray as $category) {
        $categoryInfo = getSingleCategoryInfo($category['name'], 'name', $category['level']);
        if ($categoryInfo) {
            $categoryIds[] = $categoryInfo['entity_id'];
            echo $category['name'] . " map to id: " . $categoryInfo['entity_id']. PHP_EOL;
        }
    }
    $product->setCategoryIds($categoryIds);
    $product->save();
}

function setProductCategoryIdsByCategoryIdArray ($product, $categoryIdArray) {
    echo "set category for product: " . $product->getName() . ' sku: ' . $product->getSku() . PHP_EOL;

    var_dump($categoryIdArray);

    $product->setCategoryIds($categoryIdArray);
    $product->save();
}

function setProductCategoryIdsByCategoryNameArray ($product, $categoryNameArray) {
    echo "set category for product: " . $product->getName() . PHP_EOL;

    $categoryIds = array();
    foreach ($categoryNameArray as $categoryName) {
        $category = getCategoryByName($categoryName);
        if (!$category->getId()) {
            echo 'get no category id' . PHP_EOL;
            exit(0);
        }
        $categoryIds[] = $category->getId();
    }

    var_dump($categoryIds);
    exit(0);

    $product->setCategoryIds($categoryIds);
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

        $isReviewExist = checkReviewExist(
            $entity_id,
            array(
                'nickname' => $reviewData['nickname'],
                'title' => $reviewData['title'],
                'detail' => $reviewData['detail']
            ),
            array(
                $ratingData['rating_id'] => $ratingData['value']
            )
        );
        if(!$isReviewExist){
            $reviewModel->save();

            Mage::getModel('rating/rating')
                ->setRatingId($ratingData['rating_id'])
                ->setReviewId($reviewModel->getId())
                ->addOptionVote($ratingData['value'], $entity_id);

            $reviewModel->aggregate();
        }
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

function getLatestChannelsProductReviews ($channel, $sku, $channelsinfo) {
    /* need to include ganon.php */
    $response = array();
    $review_limit = 50;
    switch ($channel) {
        case 'rakuten':
            $channel_title = 'Rakuten.com';
            //product_url is required for this channel
            $required_fields = array('product_url');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $review_url = $product_url = $channelsinfo['product_url'][$channel_title];

            $html = file_get_dom($review_url);

            if(!empty($html)){
                foreach($html('ul.list-reviews li div.rating-block') as $element){
                    //nickname could be empty in rakuten.com
                    $nickname = "None";
                    //rating
                    $ratingStr = $element->parent->getChild(3)->getChild(1)->getChild(1)->getInnerText();
                    preg_match_all('/class="s(.+)">/', $ratingStr, $matchRating);
                    $rating = trim($matchRating[1][0]);
                    if($rating > 10){
                        $rating = $rating/10;
                    }
                    $subjectAndCreatedatAndNicknameStr = $element->parent->getChild(5)->html();
                    if(strpos($subjectAndCreatedatAndNicknameStr, "</span><span")){
                        //if contain </span><span, means there has nickname
                        preg_match_all('/<b>(.+)<\/b>/', $subjectAndCreatedatAndNicknameStr, $matchSubjectAndCreatedatAndNickname);
                        $subjectAndCreatedatAndNicknameStr = $matchSubjectAndCreatedatAndNickname[1][0];

                        //title
                        preg_match_all('/(.+)<\/b>/', $subjectAndCreatedatAndNicknameStr, $matchSubject);
                        $subject = trim($matchSubject[1][0]);
                        //created at
                        preg_match_all('/<\/b>(.+)<br \/>/', $subjectAndCreatedatAndNicknameStr, $matchCreatedat);
                        $created_at = trim($matchCreatedat[1][0]);
                        //nickname
                        preg_match_all('/<b>(.+)/', $subjectAndCreatedatAndNicknameStr, $matchNickname);
                        $nickname = trim($matchNickname[1][0]);
                    }
                    else{
                        //no nickname, process title and created only
                        //title
                        preg_match_all('/<b>(.+)<\/b>/', $subjectAndCreatedatAndNicknameStr, $matchSubject);
                        $subject = trim($matchSubject[1][0]);
                        //created at
                        preg_match_all('/<\/b>(.+)<br \/>/', $subjectAndCreatedatAndNicknameStr, $matchCreatedat);
                        $created_at = trim($matchCreatedat[1][0]);
                    }
                    if(!moreThanSpecificDays($created_at, 'now', 2)) {
                        //detail
                        $detail = trim($element->parent->getChild(6)->getPlainText());

                        $data = array(
                            'detail' => $detail,
                            'nickname' => $nickname,
                            'subject' => $subject,
                            'created_at' => $created_at,
                            'rating' => (string)$rating,
                            'product_url' => $product_url
                        );
                        $response[] = $data;
                    }
                }
            }
            break;
        /*
         * //rosewill don't sale on bestbuy.com anymore,this case ignore
        case 'bestbuy':
            $url = "http://www.bestbuy.com/site/samsung-4-2-cu-ft-9-cycle-high-efficiency-steam-front-loading-washer-white/3460004.p?skuId=3460004";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url );
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            $html = curl_exec($ch);
            curl_close($ch);

            file_put_contents('bestbuy.html', $html);

            $dom = file_get_dom('bestbuy.html');

            if(!empty($dom)){
                $i = 1;
                foreach($dom('div[itemprop="reviews"]') as $element){
                    echo $i . ": " . PHP_EOL;
                    echo "    Subject: " . $element('span[itemprop="name"]', 0)->getPlainText() . PHP_EOL;
                    echo "    Rating: " . $element('span[itemprop="ratingValue"]', 0)->getPlainText() . PHP_EOL;
                    echo "    Post by: " . $element('span[itemprop="author"]', 0)->getPlainText() . PHP_EOL;
                    echo "    Deatil: " . $element('span[itemprop="description"]', 0)->getPlainText() . PHP_EOL;
                    foreach($element('meta[itemprop="datePublished"]') as $eachDate){
                        echo "    Created at: " . $eachDate->content . PHP_EOL;
                    }
                    $i++;
                }
            }
            break;
        */
        case 'walmart' :
            $channel_title = 'Walmart.com';
            //channel_sku is required for this channel
            $required_fields = array('channel_sku');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = 'http://www.walmart.com/ip/' . $channel_sku;

            $review_url = 'http://www.walmart.com/reviews/api/product/' . $channel_sku . '?limit=10&sort=submission-desc&filters=&showProduct=false';

            $html = CallAPI('GET', $review_url);
            $content = $html['reviewsHtml'];
            preg_match_all('/<h3 class=\"visuallyhidden\">Customer review by ([^>^<]+)/', $content, $matchNickname);
            preg_match_all('/<[^>]+customer-review-title">([^>^<]+)/', $content, $matchSubject);
            preg_match_all('/<p class=\"js-customer-review-text\"[^>]+>([^>^<]+)/', $content, $matchReviewText);
            preg_match_all('/<span class="Grid-col[^>]+customer-review-date[^>]+>([^<]+)/', $content, $matchPostDate);
            preg_match_all('/<span class="visuallyhidden">([^>^<]+) stars/', $content, $matchRating);
            if (!empty($matchNickname[1])) {
                foreach ($matchNickname[1] as $index => $nickname) {
                    $created_at = trim($matchPostDate[1][$index]);
                    if(!moreThanSpecificDays($created_at, 'now', 2)) {
                        $data = array(
                            'nickname' => trim($nickname),
                            'detail' => trim($matchReviewText[1][$index]),
                            'created_at' => trim($matchPostDate[1][$index]),
                            'subject' => trim($matchSubject[1][$index]),
                            'rating' => trim($matchRating[1][$index]),       // first one is overall rating
                            'product_url' => $product_url
                        );
                        $response[] = $data;
                    }
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'wayfair' :
            $channel_title = 'Wayfair.com';
            // both channel_sku and product_url are required
            $required_fields = array('channel_sku', 'product_url');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $product_url = $channelsinfo['product_url'][$channel_title];
            $channel_sku = $channelsinfo['channel_sku'][$channel_title];

            $review_url = "http://www.wayfair.com/a/product_review_page/get_update_reviews_json?_format=json&page_number=1&sort_order=date_desc&filter_rating=&filter_tag=&item_per_page=" . $review_limit. "&product_sku=" . $channel_sku;
//            //vaildate
//            $html = CallAPI('GET', $review_url);
//
//            preg_match('/<script type=\"text\/javascript\" src=\"\/(wf-[^>]+.js)\"/', trim($html), $match);
//            $js_file = $match[1];
//            $base_url = parse_url($review_url);
//            $second_url = $base_url['scheme'] . "://" .  $base_url['host'] . "/" . $js_file;
//            $js_headers = get_headers($second_url, 1);
//            $third_url = $base_url['scheme'] . "://" .  $base_url['host'] . $js_headers['X-JU'];
//
//            $data = 'p=%7B%22appName%22%3A%22Netscape%22%2C%22platform%22%3A%22Win32%22%2C%22cookies%22%3A1%2C%22syslang%22%3A%22zh-TW%22%2C%22userlang%22%3A%22zh-TW%22%2C%22cpu%22%3A%22%22%2C%22productSub%22%3A%2220030107%22%2C%22setTimeout%22%3A0%2C%22setInterval%22%3A0%2C%22plugins%22%3A%7B%220%22%3A%22ShockwaveFlash%22%2C%221%22%3A%22WidevineContentDecryptionModule%22%2C%222%22%3A%22ChromePDFViewer%22%2C%223%22%3A%22NativeClient%22%2C%224%22%3A%22ChromePDFViewer%22%7D%2C%22mimeTypes%22%3A%7B%220%22%3A%22ShockwaveFlashapplication%2Fx-shockwave-flash%22%2C%221%22%3A%22ShockwaveFlashapplication%2Ffuturesplash%22%2C%222%22%3A%22WidevineContentDecryptionModuleapplication%2Fx-ppapi-widevine-cdm%22%2C%223%22%3A%22application%2Fpdf%22%2C%224%22%3A%22NativeClientExecutableapplication%2Fx-nacl%22%2C%225%22%3A%22PortableNativeClientExecutableapplication%2Fx-pnacl%22%2C%226%22%3A%22PortableDocumentFormatapplication%2Fx-google-chrome-pdf%22%7D%2C%22screen%22%3A%7B%22width%22%3A1440%2C%22height%22%3A900%2C%22colorDepth%22%3A24%7D%2C%22fonts%22%3A%7B%220%22%3A%22Calibri%22%2C%221%22%3A%22Cambria%22%2C%222%22%3A%22Constantia%22%2C%223%22%3A%22LucidaBright%22%2C%224%22%3A%22Georgia%22%2C%225%22%3A%22SegoeUI%22%2C%226%22%3A%22Candara%22%2C%227%22%3A%22TrebuchetMS%22%2C%228%22%3A%22Verdana%22%2C%229%22%3A%22Consolas%22%2C%2210%22%3A%22LucidaConsole%22%2C%2211%22%3A%22LucidaSansTypewriter%22%2C%2212%22%3A%22CourierNew%22%2C%2213%22%3A%22Courier%22%7D%7D';
//
//            $ch = curl_init($third_url);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
//            $result = curl_exec($ch);
//            curl_close($ch);
//
//            //get cookie
//            $cookie_file = file_get_contents('cookie.txt');
//
//            $data = explode("\n", $cookie_file);
//            $cookies = array();
//            foreach($data as $index => $line) {
//                if($index >= 4 && $index < 9){
//                    $str = explode("\t", $line);
//                    $cookies[] = $str[5] . "=" . $str[6];
//                    //var_dump($str);
//                }
//            }
//            $cookie = implode(';', $cookies);

            $cookie = getCookieFromAws('wayfair', $channel_sku, $product_url);

            //get review data
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $review_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            //curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            $html = curl_exec($ch);
            curl_close($ch);
            $content = json_decode(trim($html), true);
            foreach($content['reviews'] as $each){
                $detail = trim($each['product_comments']);
                $subject = trim($each['headline']);
                $nickname = trim($each['reviewer_name']);
                $created_at = trim($each['date']);
                $rating = trim($each['rating']);
                if(!moreThanSpecificDays($created_at, 'now', 2)) {
                    $data = array(
                        'detail' => $detail,
                        'nickname' => $nickname,
                        'subject' => $subject,
                        'created_at' => $created_at,
                        'rating' => $rating,
                        'product_url' => $product_url
                    );
                    $response[] = $data;
                }
            }

            break;
        case 'sears' :
            $channel_title = 'Sears.com';
            // both channel_sku and product_url are required
            $required_fields = array('channel_sku', 'product_url');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = $channelsinfo['product_url'][$channel_title];

            $review_url = "http://www.sears.com/content/pdp/ratings/single/search/Sears/" . $channel_sku ."&targetType=product&limit=". $review_limit . "&offset=0";

            $html = file_get_contents($review_url);
            $html = json_decode($html,true);
            $review_data = $html['data']['reviews'];

            if(!empty($review_data)){
                foreach($review_data as $each_review){
                    $date = new Zend_Date(strtotime(trim($each_review['published_date'])));
                    if(!morethanDays($date, 2)) {
                        $data = array(
                            'detail' => trim($each_review['content']),
                            'nickname' => htmlentities(trim($each_review['author']['screenName'])),
                            'subject' => htmlentities(trim($each_review['summary'])),
                            'created_at' => $date->get('MMM dd, yyyy'),
                            'rating' => trim($each_review['attribute_rating'][0]['value']),
                            'product_url' => $product_url
                        );
                        $response[] = $data;
                    }
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'homedepot' :
            $channel_title = 'HomeDepot.com';
            // channel_sku is required
            $required_fields = array('channel_sku');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = 'http://www.homedepot.com/p/' . $channel_sku;

            $review_url = 'http://homedepot.ugc.bazaarvoice.com/1999aa/' . $channel_sku . '/reviews.djs?format=embeddedhtml&page=1&sort=submissionTime&scrollToTop=true';

            $content = stripslashes(file_get_contents($review_url));
            preg_match_all('/<span itemprop="author" class="BVRRNickname">([^>^<]+)<\/span>/', $content, $matchNickname);
            preg_match_all('/<span class="BVRRReviewText">([^>^<]+)<\/span>/', $content, $matchReviewText);
            preg_match_all('/<span class="BVRRValue BVRRReviewDate">[^>^<]+<meta itemprop="datePublished" content="([^\"]+)"\/><\/span>/', $content, $matchPostDate);
            preg_match_all('/<span itemprop="ratingValue" class="BVRRNumber BVRRRatingNumber">([^<>]+)<\/span>/', $content, $matchRating);
            preg_match_all('/<span itemprop="name" class="BVRRValue BVRRReviewTitle">([^<>]+)<\/span>/', $content, $matchSubject);

            if (!empty($matchNickname[1])) {
                foreach ($matchNickname[1] as $index => $nickname) {
                    $created_at = trim($matchPostDate[1][$index]);
                    if(!moreThanSpecificDays($created_at, 'now', 2)) {
                        $data = array(
                            'nickname' => trim($nickname),
                            'detail' => trim($matchReviewText[1][$index]),
                            'created_at' => trim($matchPostDate[1][$index]),
                            'subject' => trim($matchSubject[1][$index]),
                            'rating' => trim($matchRating[1][$index + 1]),       // first one is overall rating
                            'product_url' => $product_url
                        );
                        $response[] = $data;
                    }
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'amazon' :
            $channel_title = 'Amazon.com';

            $required_fields = array('channel_sku');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = 'http://www.amazon.com/gp/product/' . $channel_sku;
            $review_url = 'http://www.amazon.com/product-reviews/' . $channel_sku . '/ref=cm_cr_pr_viewopt_srt?ie=UTF8&showViewpoints=1&sortBy=recent&pageNumber=1';

            $html = file_get_dom($review_url);
            foreach ($html('#cm_cr-review_list > .a-section') as $index => $element) {
                echo $index . PHP_EOL;
                echo $element->getPlainText() . PHP_EOL;
                $created_at = $element->getChild(1)->lastChild()->getPlainText();
                if(!moreThanSpecificDays($created_at, 'now', 2)) {
                    preg_match('/(.+) out of/', $element->getChild(0)->getChild(0)->getPlainText(), $matchRating);
                    if (count($matchRating) == 2) {
                        $rating = $matchRating[1];
                        $response[] = array(
                            'detail' => $element->getChild(3)->getPlainText(),
                            'rating' => $rating,
                            'subject' => $element->getChild(0)->lastChild()->getPlainText(),
                            'created_at' => $element->getChild(1)->lastChild()->getPlainText(),
                            'nickname' => $element->getChild(1)->getChild(0)->getPlainText(),
                            'product_url' => $product_url
                        );
                    }
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'newegg' :
            $product_url = 'http://www.newegg.com/Product/Product.aspx?Item=' . $sku . '&Pagesize=' . $review_limit;
            $review_url  = 'http://content.newegg.com/Common/Ajax/ProductReview2016.aspx?' .
//                         . 'action=Biz.Product.ProductReview.switchReviewTabCallBack&callback=Biz.Product.ProductReview.switchReviewTabCallBack&&'
//                         . 'Item='. $sku.'&review=0&SummaryType=0&Pagesize='. $review_limit .'&PurchaseMark=false&SelectedRating=-1&'
//                         . 'VideoOnlyMark=false&VendorMark=false&IsFeedbackTab=true&ItemGroupId=0&Type=Newegg&ItemOnlyMark=true&'
//                             . 'chkItemOnlyMark=on&Keywords=(keywords)&SortField=0&DisplaySpecificReview=0'
                http_build_query(array(
                    'action' => 'Biz.Product.ProductReview.switchReviewTabCallBack',
                    'callback' => 'Biz.Product.ProductReview.switchReviewTabCallBack',
                    'Item' => $sku,
                    'review' => 0,
                    'SummaryType' => 0,
                    'Pagesize' => $review_limit,
                    'PurchaseMark' => false,
                    'SelectedRating' => -1,
                    'VideoOnlyMark' => false,
                    'VendorMark' => false,
                    'IsFeedbackTab' => true,
                    'ItemGroupId' => 0,
                    'Type' => 'Newegg',
                    'ItemOnlyMark' => true,
                    'chkItemOnlyMark' => 'on',
                    'Keywords' => '(keywords)',
                    'SortField' => 0,
                    'DisplaySpecificReview' => 0
                ));
//            $html = file_get_dom($review_url);
            $html = file_get_contents($review_url);
            $html = preg_replace('/\\\u[\d]{3}[\w]{1}/', '', $html);
            preg_match('/{.+}/', $html, $match);

            $data = $match[0];
            $data = json_decode(trim($data), true);
            $review_list = $data['ReviewList'];
            $html = str_get_dom($review_list);
            if(!empty($html)) {
                foreach ($html('.grpReviews tr td .details') as $element) {
                    $nickname = $element->parent->parent->getChild(0)->getChild(0)->getChild(0)->getPlainText();
                    $created_at = $element->parent->parent->getChild(0)->getChild(0)->getChild(1)->getPlainText();
                    if(!moreThanSpecificDays($created_at, 'now', 2)){
                        $ratingText = $element->parent->parent->getChild(1)->getChild(2)->getChild(0)->getPlainText();
                        preg_match('/(\d).?\/.?\d/', $ratingText, $match);
                        if (count($match) == 2) {
                            $rating = $match[1];
                        }
                        if ($element->parent->parent->getChild(1)->getChild(2)->getChild(1)) {
                            $subject = $element->parent->parent->getChild(1)->getChild(2)->getChild(1)->getPlainText();
                        } else {
                            $subject = null;
                        }
                        $detail = trim($element->getPlainText());
                        /*remove string before "Pros: " and add <br /> in front of "Crons:" and "Other Thoughts:"*/
                        $detail =  substr($detail, strpos($detail, 'Pros:'), strlen($detail));
                        $detail = str_replace('Cons:', '<br /><br />Cons:', $detail);
                        $detail = str_replace('Other Thoughts:', '<br /><br />Other Thoughts:', $detail);
                        $detail = trim($detail);

                        if(stripos($detail, 'Manufacturer Response:') !== false){
                            continue;
                        }

                        $data = array(
                            'detail' => $detail,
                            'nickname' => htmlentities($nickname),
                            'subject' => htmlentities($subject),
                            'created_at' => $created_at,
                            'rating' => $rating,
                            'product_url' => $product_url
                        );
//                    var_dump($data);
                        $response[] = $data;
                    }
                }
            }
            else {
                echo "Empty html...." . PHP_EOL;
            }
            break;
    }
    return $response;
}

function replaceSpecialCharacters($input){
    /*
    *'\', '/', '?' make magento addAttributeToFilter equal function inactive, so replace them with sql wildcard character '_' and use 'like' search
    * '\' => '\\\\'
    * '/' => '\/'
    * '?' => '?'
    */
    return preg_replace('/[\\\\\/?]/', '_', $input);
}

function getInformationFromIntelligence ($itemNumber, $returnResponse = false) {
    $intelligenceBaseUrl = 'http://172.16.16.77:8471';
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

function sendMailWithDownloadUrl ($subject, $file_list, $recipient_array) {
    require_once 'class/Email.class.php';
    require_once 'class/EmailFactory.class.php';
    require_once 'rest/tools.php';

    $smtp_host = Mage::getStoreConfig('smtppro/general/smtp_host');
    if (!$smtp_host) {
        $smtp_host = '210.208.105.59';
    }

    /* SMTP server name, port, user/passwd */
    $smtpInfo = array("host" => $smtp_host,
        "port" => "25",
        "auth" => false
    );
    $emailFactory = EmailFactory::getEmailFactory($smtpInfo);

    $attachments = array();
    if(!empty($file_list)) {
        foreach ($file_list as $each) {
            $fileName = $each;
            $excelFileType = 'application/vnd.ms-excel';
            $attachments[$fileName] = $excelFileType;
        }
    }

    switch ($subject) {
        case 'Crawler Report':
            $subject = 'NE.com and Amazon.com Daily Crawling Report';
            $title = 'NE.com and Amazon.com Daily Crawling Report';
            $description = 'Hi All: Data as attachments';
            break;
        case 'Bad product review alert - no bad review submitted':
            $subject = 'Bad product review alert';
            $title = 'Bad product review alert';
            $description = 'Hi All: No bad review submitted';
            break;
        case 'Bad product review alert':
            $subject = 'Bad product review alert';
            $title = 'Bad product review alert';
            $description = 'Hi All: Data as attachments';
            break;
        default:
            $subject = 'Default Subject';
            $title = 'Default Title';
            $description = 'Hi: This is default description.';
            break;
    }

    /* $email = class Email */
    $email = $emailFactory->getEmail($subject, $recipient_array);
    $content = templateReplace($title, $description);
    $email->setContent($content);
    $email->setAttachments($attachments);
    $email->sendMail();
    return true;
}

function templateReplace ($title, $description) {
    /*use ganon.php to parse html file*/
    $doc = file_get_dom('email/content/template.html');
    //set title
    $doc('.descriptionTitle p', 0)->setPlainText($title);
    //set description
    $doc('.description p', 0)->parent->setInnerText($description);
    //set logo image
    $doc('.logoImage', 0)->setAttribute('src', 'images/rosewilllogo.png');

    return $doc;
}

function checkReviewExist($productId, $review, $rating){
    $reviewCollection = Mage::getModel('review/review')->getCollection();
    $reviewCollection->addFieldToFilter('title', $review['title'])
        ->addFieldToFilter('nickname', $review['nickname'])
        ->addFieldToFilter('detail', $review['detail'])
        ->addFieldToFilter('entity_pk_value', $productId);

    $reviewCount = $reviewCollection->count();
    Mage::log('review count: ' . $reviewCount, null, 'contactus.log');
    if($reviewCount >= 1) {
        foreach ($reviewCollection as $eachReview) {
            $reviewId = $eachReview->getReviewId();
            foreach ($rating as $ratingId => $optionId) {
                $ratingCollection = Mage::getModel('rating/rating_option_vote')->getCollection();
                $ratingCollection->addFieldToFilter('review_id', $reviewId)
                    ->addFieldToFilter('rating_id', $ratingId)
                    ->addFieldToFilter('option_id', $optionId);
                if ($ratingCollection->count() >= 1) {
                    Mage::log('Exist', null, 'contactus.log');
                    return true;
                }
            }
        }
    }
    return false;
}

function getCookieFromAws($channel, $channel_sku, $product_url){
    $awsRestUrl = 'http://www.rosewill.com/rest/route.php/api/';
    $getCookieApiName = 'getCookies';
    $requestMethod = 'POST';
    $token = array('Token: rosewill');
    $data = array(
        'channel' => $channel,
        'channel_sku' => $channel_sku,
        'product_url' => $product_url
    );

    $response = CallAPI(
        $requestMethod,
        $awsRestUrl . $getCookieApiName,
        $token,
        $data
    );

    return $response['cookie'];

}

/*
 * type: attribute
 * */

function createNewAttribute ($label, $frontend_input) {

    $new_attribute_code = generateAttributeCodeByLabel($label);

    $collection = Mage::getModel('eav/entity_attribute')->getCollection()
        ->addFieldToFilter('frontend_label', $label)
        ->addFieldToFilter('attribute_code', $new_attribute_code);
    if ($collection->count() > 0) {
        return $collection->getFirstItem()->getId();
    }

    /* 'frontend_input' => 'backend_type' */
    $backend_type = array(
        'multiselect' => 'varchar',
        'boolean' => 'int',
        'select' => 'int',
        'text' => 'varchar',
        'textarea' => 'text'
    );

    if (!isset($backend_type[$frontend_input])) {
        echo 'error frontend_input in createNewAttribute' . PHP_EOL;
        exit(0);
    }

    $attr_params = array(
        'entity_type_id' => 4,
        'attribute_code' => $new_attribute_code,
        'backend_type' => $backend_type[$frontend_input],
        'frontend_input' =>  $frontend_input,
        'frontend_label' => $label,
        'is_global' => 2,       // website
        'is_user_defined' => 1,
        'is_visible' => 1,
        'is_visible_on_front' => 1,
        'used_in_product_listing' => 1
    );
    $attr = Mage::getModel('eav/entity_attribute')
        ->setData($attr_params)
        ->save();
    return $attr->getId();
}

function getAttributeCollection () {
    return Mage::getModel('eav/entity_attribute')->getCollection()
        ->addFieldToFilter('entity_type_id', 4);
}

function getAttributeSetCollection () {
    return Mage::getModel('eav/entity_attribute_set')->getCollection()
        ->addFieldToFilter('entity_type_id', 4);
}

function compareAttributeOptionArray ($oldAttributeOptions, $optionList) {
    $oldOptions = array();
    $response = array();
    foreach ($oldAttributeOptions as $old) {
        $oldOptions[] = ucwords(trim($old['label']));
    }
    foreach ($optionList as $new_opt) {
        $tmp = ucwords(trim($new_opt));
        if (!in_array($tmp, $oldOptions)) {
            $response[] = $tmp;
        }
    }
    return $response;
}

function checkAttributeInProductAttributeSet ($attributeCode, $product) {
    $attributes = Mage::getModel('eav/config')->getEntityAttributeCodes(
        Mage_Catalog_Model_Product::ENTITY,
        $product
    );

    return in_array($attributeCode, $attributes);
}

function moveAttributeToGroupInAttributeSet ($attributeCode, $attributeSetName, $groupName, $removeFirst = false) {
    /* $groupName = attribute set 裡面的資料夾 */
    try {
        $attributeId = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);
        $attributeSetId = Mage::getModel('eav/entity_attribute_set')->load($attributeSetName, 'attribute_set_name')->getAttributeSetId();

        $model = Mage::getModel('eav/entity_setup','core_setup');
        $attributeGroupData = $model->getAttributeGroup('catalog_product', $attributeSetId, $groupName);
        $groupId = $attributeGroupData["attribute_group_id"];

        if ($removeFirst) {
            //remove attribute from attribute set
            Mage::getModel('catalog/product_attribute_set_api')->attributeRemove($attributeId, $attributeSetId);
        }

        //re-add attribute to specific group in same attribute set
        $model->addAttributeToSet('catalog_product',$attributeSetId, $groupId, $attributeId);
    } catch (Exception $e) {
        echo 'moveAttributeToGroupInAttrbiuteSet exception occur' . PHP_EOL;
        return false;
    }
    return true;
}

function removeAttributeFromAttributeSet ($attributeCode, $attributeSetName) {
    /* $groupName = attribute set 裡面的資料夾 */
    try {
        $attributeId = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attributeCode);
        $attributeSetId = Mage::getModel('eav/entity_attribute_set')->load($attributeSetName, 'attribute_set_name')->getAttributeSetId();

        //remove attribute from attribute set
        Mage::getModel('catalog/product_attribute_set_api')->attributeRemove($attributeId, $attributeSetId);
    } catch (Exception $e) {
        echo 'removeAttributeFromAttributeSet exception occur' . PHP_EOL;
        return false;
    }
    return true;
}

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
        $attribute = Mage::getModel('eav/config')->getAttribute('catalog_product', $attributeCode);
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

function setAttributeOptions ($attr_id, $optionsArray) {
    /* check if $optionsArray exists */
    $oldAttributeOptions = getAttributeOptions('attributeId', $attr_id);
    $toAddArray = compareAttributeOptionArray($oldAttributeOptions['options'], $optionsArray);

    $tmpArray = array();
    foreach ($toAddArray as $index => $option) {
        $tmpArray['option' . $index][0] = $option;
    }
    try {
        $setup = new Mage_Eav_Model_Entity_Setup('core_setup');
        $setup->addAttributeOption(array(
            'attribute_id' => $attr_id,
            'value' => $tmpArray
        ));
    } catch (Exception $e) {
        echo $e->getMessage() . PHP_EOL;
        echo 'error in setAttributeOptions' . PHP_EOL;
        exit(0);
    }
    return true;
}

function setAttributeValueToOptions ($product, $nameOrId, $attrCodeOrId, $valueToBeMapped, $debug) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);
    if (!isset($optionsArray['frontend_input'])) {
        return $valueToBeMapped;
    }
    switch ($optionsArray['frontend_input']) {
        case 'select' :
            $options = getAttributeOptions($nameOrId, $attrCodeOrId);
            $valueToInput = null;
            foreach ($options['options'] as $eachOption) {
                if ($eachOption['label'] == $valueToBeMapped) {
                    echo 'mapped label: ' . $valueToBeMapped . ' value: ' . $eachOption['value'];
                    $valueToInput = $eachOption['value'];
                    continue;
                }
            }
            if (!$valueToInput) {
                echo $valueToBeMapped . ' mapped to nothing';
                var_dump($options);
                die();
            }
            break;
        default :
            echo '******** no mapping TYPE ********' . PHP_EOL;
            var_dump($optionsArray['frontend_input']);
            die();
    }
    if (!$debug) {
        if ($nameOrId == 'attributeName') {
            $product->setData($attrCodeOrId, $valueToInput)
                ->save();
            echo 'attribute value saved.' . PHP_EOL;
        } else {
            echo 'code no finished yet.' . PHP_EOL;
        }
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

function getAttributeLabelFromOptions ($nameOrId, $attrCodeOrId, $valueToBeMapped) {

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
                        $mappedArray[] = trim($optionObject['label']);
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

function generateAttributeCodeByLabel ($attr_label) {
    return preg_replace('/[^\w.]/', '_', trim(strtolower($attr_label)) );
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
            "url_key",
            "channelsinfo"
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
            'category',
            "is_in_stock",
            "is_salable",
            "tier_price_changed",
            "group_price_changed",
            "ne_product_specifications",
            "ewra",
            "msds_sheet",
            "has_options",
            "required_options"
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
        else if (is_array($attrValue)) {
            if (preg_in_array($attrKey, $keyWord['direct'])) {
                $response['direct'][$attrKey] = $attrValue;
            }
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



function existDifferentImages($jsonImageArray, $dbImageArray){
    /*
     * input(json format):
     * $jsonImageArray = [
     *     {"ImageName":"48-023-268-08.jpg","Priority":1,"IsActive":true,"IsReceive":true,"IsRMA":false},
     *     {"ImageName":"48-023-268-09.jpg","Priority":2,"IsActive":true,"IsReceive":true,"IsRMA":false},
     *     {"ImageName":"48-023-268-10.jpg","Priority":3,"IsActive":true,"IsReceive":true,"IsRMA":false},
     *     {"ImageName":"48-023-268-11.jpg","Priority":4,"IsActive":true,"IsReceive":true,"IsRMA":false},
     *     {"ImageName":"48-023-268-12.jpg","Priority":5,"IsActive":true,"IsReceive":true,"IsRMA":false},
     *     {"ImageName":"48-023-268-13.jpg","Priority":6,"IsActive":true,"IsReceive":true,"IsRMA":false},
     *     {"ImageName":"48-023-268-14.jpg","Priority":7,"IsActive":true,"IsReceive":true,"IsRMA":false}
     * ]
     *
     * $dbImageArray = [
     *     {"value_id":"8682","file":"/4/8/48-023-268-08.jpg","label":"","position":"1","disabled":"0","label_default":null,"position_default":"1","disabled_default":"0"},
     *     {"value_id":"8683","file":"/4/8/48-023-268-09.jpg","label":"","position":"2","disabled":"0","label_default":null,"position_default":"2","disabled_default":"0"},
     *     {"value_id":"8684","file":"/4/8/48-023-268-10.jpg","label":"","position":"3","disabled":"0","label_default":null,"position_default":"3","disabled_default":"0"},
     *     {"value_id":"8685","file":"/4/8/48-023-268-11.jpg","label":"","position":"4","disabled":"0","label_default":null,"position_default":"4","disabled_default":"0"},
     *     {"value_id":"8686","file":"/4/8/48-023-268-12.jpg","label":"","position":"5","disabled":"0","label_default":null,"position_default":"5","disabled_default":"0"},
     *     {"value_id":"8687","file":"/4/8/48-023-268-13.jpg","label":"","position":"6","disabled":"0","label_default":null,"position_default":"6","disabled_default":"0"},
     *     {"value_id":"8688","file":"/4/8/48-023-268-14.jpg","label":"","position":"7","disabled":"0","label_default":null,"position_default":"7","disabled_default":"0"}]
     *
     *  output:
     * array("48-023-268-15.jpg");

     * */
    $json_image_file_list = array();
    $db_image_file_list = array();

    foreach($jsonImageArray as $each){
        $json_image_file_list[] = trim($each["ImageName"]);
    }

    foreach($dbImageArray as $each){
        $file_name = trim($each['file']);
        preg_match('/([^\/]+).jpg/', $file_name, $match);
        $db_image_file_list[] = $match[0];
    }

    $needAdd = array_diff($json_image_file_list, $db_image_file_list);
    $needRemove = array_diff($db_image_file_list, $json_image_file_list);

    if(!empty($needAdd) || !empty($needRemove)){
        $result['massage'] = 'true';
        $result['add'] = $needAdd;
        $result['remove'] = $needRemove;
    }
    else{
        $result['message'] = 'false';
    }
    return $result;
}

function morethanDays ($dateTimeString, $locale = 'America/Los_Angeles', $daysMoreThan = 2) {
    // new Date with datetime string in UTC, and get timestamp
    $date = new DateTime('now', new DateTimeZone('UTC'));
    $date->setTimestamp(strtotime($dateTimeString));
    // get datetime string from UTC DateTime object.
    $newDateTimeString = $date->format('Y-m-d H:i:s');
    // Generate a DateTime object with the new dateTime string with new locale
    $newDate = new DateTime($newDateTimeString, new DateTimeZone($locale));
    $reviewTimeStamp = (int)$newDate->format('U');
    // Generate a now DateTime object with new locale
    $nowLATimeStamp = new DateTime('now', new DateTimeZone($locale));
    $nowLATimeStamp = (int)$nowLATimeStamp->format('U');

    $diff =  $nowLATimeStamp - $reviewTimeStamp;
    $days = $diff / 86400;
    if ($days > $daysMoreThan) {
        echo "More than $daysMoreThan days" . PHP_EOL;
        return true;
    }
    return false;
}

function moreThanSpecificDays ($date_string_1, $date_string_2, $specific_days = 2) {
    $date_1 = new DateTime($date_string_1);
    $date_2 = new DateTime($date_string_2);

    $interval = $date_1->diff($date_2);
    $days = $interval->days;
    if ($days >= $specific_days) {
        echo "More than $specific_days days" . PHP_EOL;
        return true;
    }
    return false;
}

function writeReviewCommentToLocal($data) {
//    const SUBMITTER_ADMIN = 1;
//    const SUBMITTER_CUSTOMER = 2;
//    const SUBMITTER_VISITOR = 3;
//    const STATUS_PENDING = 4;
//    const STATUS_APPROVED = 5;
//    const STATUS_NOT_APPROVED = 6;

    $review_data = (isset($data['review']) && !empty($data['review'])) ? $data['review'] : null;
    $comment_data = (isset($data['comment']) && !empty($data['comment'])) ? $data['comment'] : null;
    $parent_comment_data = (isset($data['parent']) && !empty($data['parent'])) ? $data['parent'] : null;

    if(empty($review_data) || empty($comment_data)){
        return array('status' => 'failed', 'message' => 'Invalidate input data');
    }

    $review_id = getReviewIdFromGivenReviewData($review_data);

    if($review_id) {
        $status = $comment_data['status'];
        $nickname = $comment_data['nickname'];
        $content = $comment_data['content'];
        $submitter_type = $comment_data['submitter_type'];
        $customer_email = $comment_data['customer_email'];
        $created_at = $comment_data['created_at'];
        $layer = 1;
        $path = $review_id;
        $child_list = null;
        $parent_id = 0;

        switch ($submitter_type) {
            case 1:
                //admin
                $user_model = Mage::getSingleton('admin/user');
                break;
            case 2:
                //customer
                $user_model = Mage::getSingleton('customer/customer');
                break;
            case 3:
                //visitor
                break;
        }
        $user_collection = $user_model->getCollection()->addFieldToFilter('email', $customer_email);
        $customer_id = $user_collection->getFirstItem()->getId();

        //if has parent
        if($parent_comment_data) {
            $collection = getCommentCollectionFromGivenCommentData($review_id, $parent_comment_data);
            if ($collection->count() > 1) {
                return array('status' => 'failed', 'message' => 'more than one parent comment record');
            }
            $parent_id = $collection->getFirstItem()->getId();
            $parent = Mage::getSingleton('customreview/comment')->load($parent_id);
            $layer = (int)$parent->getLayer() + 1;
            $path = $parent->getPath();
            $child_list = null;
        }

        $comment_data = array(
            'r_id'           => $review_id,
            'status'         => $status,
            'nickname'       => $nickname,
            'content'        => $content,
            'submitter_type' => $submitter_type,
            'customer_id'    => $customer_id,
            'created_at'     => $created_at,
            'path'           => $path,
            'layer'          => $layer,
            'parent_id'      => $parent_id,
            'child_list'     => $child_list
        );

        $model = Mage::getModel('customreview/comment');
        $model->setData($comment_data);
        try {
            $model->save();
            //update own path
            $own_comment_id = $model->getId();
            updateOwnPath($own_comment_id);
            //update child list of parent if exist
            if($parent_id) {
                updateParentCommentChildList($parent_id, $own_comment_id, 'add');
            }
            return array('status' => 'success', 'message' => $model->getData());
        } catch (Exception $e) {
            return array('status' => 'failed', 'message' => 'Exception occur.');
        }
    }
    else {
        return array('status' => 'failed', 'message' => 'Review not found.');
    }
}

function removeReviewCommentFromLocal($data) {
//    const SUBMITTER_ADMIN = 1;
//    const SUBMITTER_CUSTOMER = 2;
//    const SUBMITTER_VISITOR = 3;
//    const STATUS_PENDING = 4;
//    const STATUS_APPROVED = 5;
//    const STATUS_NOT_APPROVED = 6;
    $review_data = (isset($data['review']) && !empty($data['review'])) ? $data['review'] : null;
    $comment_data = (isset($data['comment']) && !empty($data['comment'])) ? $data['comment'] : null;
    $parent_comment_data = (isset($data['parent']) && !empty($data['parent'])) ? $data['parent'] : null;

    if(empty($review_data) || empty($comment_data)){
        return array('status' => 'failed', 'message' => 'Invalidate input data');
    }

    $review_id = getReviewIdFromGivenReviewData($review_data);

    if($parent_comment_data) {
        $collection = getCommentCollectionFromGivenCommentData($review_id, $parent_comment_data);
        if ($collection->count() > 1) {
            return array('status' => 'failed', 'message' => 'more than one parent comment record');
        }
        $parent_id = $collection->getFirstItem()->getId();
    }

    if($review_id) {
        $comment_collection = getCommentCollectionFromGivenCommentData($review_id, $comment_data);
        if ($comment_collection->count() > 1) {
            return array('status' => 'failed', 'message' => 'more than one comment record');
        }
        $comment_id = $comment_collection->getFirstItem()->getId();
        if ($comment_id) {
            $comment_ids = getRelatedCommentIds($comment_id);
            foreach($comment_ids as $each_comment_id) {
                Mage::getSingleton('customreview/comment')->load($each_comment_id)->delete();
            }
            if($parent_id) {
                updateParentCommentChildList($parent_id, $comment_id, 'delete');
            }
            return array('status' => 'success', 'message' => count($comment_ids) . ' records has been deleted.');
        } else {
            return array('status' => 'failed', 'message' => 'Comment not found.');
        }
    }
    else {
        return array('status' => 'failed', 'message' => 'Review not found.');
    }
}

function modifyReviewCommentFromLocal($data) {

    $review_data = (isset($data['review']) && !empty($data['review'])) ? $data['review'] : null;
    $comment_data = (isset($data['comment']) && !empty($data['comment'])) ? $data['comment'] : null;
    $after_modify_comment_data = (isset($data['after_modify_comment']) && !empty($data['after_modify_comment'])) ? $data['after_modify_comment'] : null;

    if(empty($review_data) || empty($comment_data)){
        return array('status' => 'failed', 'message' => 'Invalidate input data');
    }

    $review_id = getReviewIdFromGivenReviewData($review_data);
    if($review_id) {
        $comment_collection = getCommentCollectionFromGivenCommentData($review_id, $comment_data);
        if ($comment_collection->count() > 1) {
            return array('status' => 'failed', 'message' => 'more than one comment record');
        }
        $comment_id = $comment_collection->getFirstItem()->getId();
        if ($comment_id) {
            try {
                $comment = Mage::getModel('customreview/comment')->load($comment_id);
                $comment->setContent($after_modify_comment_data['content'])
                    ->setStatus($after_modify_comment_data['status'])
                    ->save();
                return array('status' => 'success', 'message' => $comment->getData());
            } catch (Exception $e) {
                return array('status' => 'failed', 'message' => 'Exception occur.');
            }
        } else {
            return array('status' => 'failed', 'message' => 'Comment not found.');
        }
    }
    else {
        return array('status' => 'failed', 'message' => 'Review not found.');
    }
}

function getReviewIdFromGivenReviewData($review_data) {
    $review_id = null;

    $product = getProductObject($review_data['product_sku'], 'sku');
    $entity_id = $product->getId();
    $review_title = $review_data['title'];
    $review_detail = stripslashes($review_data['detail']); // remove the escape slashes
    $review_nickname = $review_data['nickname'];
    $review_customer_email = $review_data['customer_email'];
    $review_rating_value = $review_data['rating_value'];

    $customer_collection = Mage::getSingleton('customer/customer')->getCollection()->addFieldToFilter('email', $review_customer_email);
    $customer_id = $customer_collection->getFirstItem()->getId();

    $review_collection = Mage::getModel('review/review')->getCollection();
    $review_collection->addFieldToFilter('title', $review_title)
        ->addFieldToFilter('nickname', $review_nickname)
        ->addFieldToFilter('detail', $review_detail)
        ->addFieldToFilter('entity_pk_value', $entity_id)
        ->addFieldToFilter('customer_id', $customer_id);

    //use rating value to double check the review and get it's id
    foreach ($review_collection as $each) {
        $each_review_id = $each->getReviewId();
        $rating_collection = Mage::getModel('rating/rating_option_vote')
            ->getCollection()
            ->addFieldToFilter('review_id', $each_review_id)
            ->setOrder('vote_id', 'DESC')
            ->setPageSize(1);
        $each_rating_value = $rating_collection->getFirstItem()->getValue();
        if($each_rating_value == $review_rating_value) {
            $review_id = $each_review_id;
        }
    }
    return $review_id;
}

function getCommentCollectionFromGivenCommentData($review_id, $comment_data) {
    $review_id = $review_id;
    $status = $comment_data['status'];
    $nickname = $comment_data['nickname'];
    $content = $comment_data['content'];
    $submitter_type = $comment_data['submitter_type'];
    $customer_email = $comment_data['customer_email'];
    $layer = $comment_data['layer'];
    $created_at = $comment_data['created_at'];
    switch ($submitter_type) {
        case 1:
            //admin
            $user_model = Mage::getSingleton('admin/user');
            break;
        case 2:
            //customer
            $user_model = Mage::getSingleton('customer/customer');
            break;
        case 3:
            //visitor
            return false;
    }
    $user_collection = $user_model->getCollection()->addFieldToFilter('email', $customer_email);
    $customer_id = $user_collection->getFirstItem()->getId();

    $comment_collection = Mage::getSingleton('customreview/comment')->getCollection();
    $comment_collection->addFieldToFilter('r_id', $review_id)
        ->addFieldToFilter('status', $status)
        ->addFieldToFilter('nickname', $nickname)
        ->addFieldToFilter('content', $content)
        ->addFieldToFilter('submitter_type', $submitter_type)
        ->addFieldToFilter('customer_id', $customer_id)
        ->addFieldToFilter('layer', $layer)
        ->addFieldToFilter('created_at', $created_at);

    return $comment_collection;
}

//include own id and ids of all children
function getRelatedCommentIds($comment_id) {
    if($comment_id === false || $comment_id === null) {
        return null;
    }
    $result = array();
    $result[] = (string)$comment_id;
    $comment = Mage::getSingleton('customreview/comment')->load($comment_id);
    $child_list = $comment->getChildList();
    if(!empty($child_list)) {
        $child_list = explode(',', $child_list);
        foreach($child_list as $each_child_id) {
            $child_ids = getRelatedCommentIds($each_child_id);
            $result = array_merge($result, $child_ids);
        }
    }
    return $result;
}

function updateOwnPath($comment_id){
    $comment = Mage::getSingleton('customreview/comment')->load($comment_id);
    $path = $comment->getPath();
    $path = $path . DS . $comment_id;
    $comment->setPath($path)
        ->save();
}

function updateParentCommentChildList($parent_id, $child_id, $action) {
    $model = Mage::getSingleton('customreview/comment')->load($parent_id);
    $child_list = $model->getChildList();
    if($action == 'add') {
        if ($child_list) {
            $child_list = explode(',', $child_list);
            $child_list[] = $child_id;
            $child_list = implode(',', $child_list);
        } else {
            $child_list = $child_id;
        }
        $model->setChildList($child_list)
            ->save();
    }
    elseif($action == 'delete') {
        if ($child_list) {
            $child_list = explode(',', $child_list);
            $child_key = array_search($child_id, $child_list);
            unset($child_list[$child_key]);
            $child_list = implode(',', $child_list);
        }
        $model->setChildList($child_list)
            ->save();
    }
}

function uploadProductImageByNewModule ($productModel, $imgUrl, $position, $label, $mediaType) {
    $pathInfo = pathinfo($imgUrl);     // get array of dirname, basename, extension, filename
    $fileName = getFileNameFromUrl($imgUrl);

    if (!$fileName) {
        die('Can not get xx-xxx-xxx file name from url');
    }

    $imgBaseDir = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product';

    $tmpFile = file_get_contents($imgUrl);
    $fullfileDir = $imgBaseDir . DS . $pathInfo['basename'];
    file_put_contents($fullfileDir, $tmpFile);
    echo 'file dir: ' . $fullfileDir . ' position: ' . $position . ' label: ' . $label . PHP_EOL;

    /* check if image file name exists */
    $galleryCollection = Mage::getModel('coreproductmediagallery/mediagallery')->getCollection()
        ->addFieldToFilter('entity_id', $productModel->getId())
        ->addFieldToFilter('value', array('like' => '%' . $pathInfo['basename'] . '%'));

    /* if image file exists, then exit. */
    if ($galleryCollection->count() > 0) {
        return;
    }

    $gallery = Mage::getModel('coreproductmediagallery/mediagallery')
        ->setData(array(
            'attribute_id' => Mage::getModel('eav/entity_attribute')->getCollection()
                ->addFieldToFilter('attribute_code', 'media_gallery')
                ->getFirstItem()
                ->getData('attribute_id'),
            'entity_id' => $productModel->getId(),
            'value' => DS . $pathInfo['basename']
        ))
        ->save();

    if (!$gallery->getId()) {
        sendMailWithDownloadUrl('product sync exception', null, array(
            'to' => array('gamlinnia@hotmail.com', 'Tim.H.Huang@newegg.com')
        ));
        echo 'media_gallery get no id' . PHP_EOL;
        exit(0);
    }

    $backend = new Mage_Catalog_Model_Resource_Product_Attribute_Backend_Media;
    $backend->insertGalleryValueInStore(array(
        'value_id' => $gallery->getId(),
        'label' => $label,
        'position' => $position,
        'disabled' => 0,
        'store_id' => 0
    ));

    if (!empty($mediaType)) {
        if (in_array('image', $mediaType)) {
            $productModel->setImage(DS . $pathInfo['basename']);
        }
        if (in_array('small_image', $mediaType)) {
            $productModel->setSmallImage(DS . $pathInfo['basename']);
        }
        if (in_array('thumbnail', $mediaType)) {
            $productModel->setThumbnail(DS . $pathInfo['basename']);
        }
        $productModel->save();
    }

}


function setProductValue ($product, $attribute_code, $frontend_input, $value_to_be_mapped) {
    echo PHP_EOL . 'set product ' . $product->getSku() . ' with attribute code: ' . $attribute_code . ' with value: ' . $value_to_be_mapped . ' input type = ' . $frontend_input . PHP_EOL;

    if (!is_array($value_to_be_mapped)) {
        $dividers = array('\/', '&', ',');
        $optionsArray = preg_split("/(" . join('|', $dividers) . ")/", $value_to_be_mapped);

        $function_to_map = array('trim', 'ucwords');
        foreach ($function_to_map as $function) {
            $optionsArray = array_map($function, $optionsArray);
        }
    } else {
        $optionsArray = $value_to_be_mapped;
    }
    var_dump($optionsArray);
    $value = getAttributeValueIdFromOptions('attributeName', $attribute_code, $optionsArray);
    if (empty($value)) {
        $attr_id = Mage::getModel('eav/entity_attribute')->getIdByCode('catalog_product', $attribute_code);

        $prompt = promptMessageForInput('sure to add new option: ' . join(', ', $optionsArray), array('y', 'n'));
        if ($prompt == 'y') {
            setAttributeOptions($attr_id, $optionsArray);
            $value = getAttributeValueIdFromOptions('attributeName', $attribute_code, $optionsArray);
        } else {
            return false;
        }
    }

    Zend_Debug::dump(array(
        'to be mapped value' => $value_to_be_mapped,
        'mapped' => $value
    ));

//    $prompt = promptMessageForInput('sure to save new value');
//    if ($prompt == 'y') {
    echo 'saving product ...' . PHP_EOL;
    try {
        $product->setData($attribute_code, $value)
            ->save();
    } catch (Exception $e) {
        echo 'setProductValue save exception' . PHP_EOL;
        exit(0);
    }
//    }

    return true;
}

function promptMessageForInput ($message, $acceptInput = null, $acceptEmptyInput = false) {
    $input = '';

    if ($acceptEmptyInput && !is_array($acceptInput)) {
        echo $message . PHP_EOL;
        $input = trim(fgets(STDIN));
    } else {
        while (empty($input)) {
            if (is_array($acceptInput) && count($acceptInput) > 0) {
                echo $message . ' accept input: [ ' . implode(' / ', $acceptInput) . ' ]' . PHP_EOL;
                while (!in_array($input, $acceptInput)) {
                    $input = trim(fgets(STDIN));
                }
            } else {
                echo $message . PHP_EOL;
                $input = trim(fgets(STDIN));
            }
        }
    }

    return $input;
}

function getDownloadableFileList()
{
    $fileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection();
    $fileList = array();
    foreach($fileListCollection as $_file) {
        $fileName = $_file->getFile();
        $fileList[] = $fileName;
    }
    return $fileList;
}


function getDownloadableFileAssociatedProduct()
{
    $fileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection();
    $fileList = array();
    foreach ($fileListCollection as $_file) {
        $fileId = $_file->getId();
        $fileName = $_file->getFile();
        $fileList[$fileName] = array();
        $associatedProductCollection = Mage::getModel('downloadablefile/associatedproduct')
            ->getCollection()
            ->addFieldToFilter('file_list_id', $fileId);
        foreach ($associatedProductCollection as $_each) {
            $productId = $_each->getProductId();
            $productSku = Mage::getModel('catalog/product')->load($productId)->getSku();
            $fileList[$fileName][] = $productSku;
        }
    }

    return $fileList;
}
//multi dimension array difference function
function arrayRecursiveDiff ($aArray1, $aArray2) {
    $aReturn = array();

    foreach ($aArray1 as $mKey => $mValue) {
        if (array_key_exists($mKey, $aArray2)) {
            if (is_array($mValue)) {
                $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                if (count($aRecursiveDiff)) {
                    $aReturn[$mKey] = $aRecursiveDiff;
                }
            } else {
                if (!in_array($mValue, $aArray2)) {
                    $aReturn[$mKey] = $mValue;
                }
            }
        } else {
            $aReturn[$mKey] = $mValue;
        }
    }
    return $aReturn;
}

function updateLocalFileList ($fileList, $remoteMediaUrl = null) {
    $localMediaDir = Mage::getBaseDir('media');
    foreach($fileList as $_file) {
        $fileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection()->addFieldToFilter('file', $_file);
        if($fileListCollection->count() < 1) {
            //get new file from remote server and create association
            //process physical file
            $file = file_get_contents($remoteMediaUrl . $_file);
            file_put_contents($localMediaDir. DS . $_file, $file);
            //process file list table
            preg_match('/\/([\w]+)\//', $_file, $match);
            $type = $match[1];
            $data = array(
                'file' => $_file,
                'type' => $type,
                'position' => 0
            );
            Mage::getModel('downloadablefile/filelist')->setData($data)
                ->save();
        }
        else {
            $fileListId = $fileListCollection->getFirstItem()->getId();
            //delete associated product records
            $associatedProductCollection = Mage::getModel('downloadablefile/associatedproduct')
                ->getCollection()
                ->addFieldToFilter('file_list_id', $fileListId);
            if($associatedProductCollection->count() > 0) {
                foreach ($associatedProductCollection as $each) {
                    Mage::getModel('downloadablefile/associatedproduct')->load($each->getId())->delete();
                }
            }
            Mage::getModel('downloadablefile/filelist')->load($fileListId)->delete();
            unlink($localMediaDir . DS . $_file);
        }
    }
}

function updateLocalAssociatedProductRecords($fileListWithAssociatedProduct) {
    foreach($fileListWithAssociatedProduct as $_file => $_productSkuList) {
        $fileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection()->addFieldToFilter('file', $_file);
        $fileListId = $fileListCollection->getFirstItem()->getId();
        if($fileListId) {
            foreach ($_productSkuList as $_productSku) {
                $productId = Mage::getModel('catalog/product')->getIdBySku($_productSku);
                if($productId) {
                    $associatedProductModel = Mage::getModel('downloadablefile/associatedproduct');
                    $associatedProductCollection = $associatedProductModel->getCollection()
                        ->addFieldToFilter('file_list_id', $fileListId)
                        ->addFieldToFilter('product_id', $productId);
                    if ($associatedProductCollection->count() < 1) { // if record not exist, then create new file and associated records
                        $data = array(
                            'product_id' => $productId,
                            'file_list_id' => $fileListId
                        );
                        Mage::getModel('downloadablefile/associatedproduct')->setData($data)
                            ->save();
                    }
                    else { //if records exist, then delete it
                        foreach ($associatedProductCollection as $each) {
                            Mage::getModel('downloadablefile/associatedproduct')->load($each->getId())->delete();
                        }
                    }
                }
            }
        }
    }
}