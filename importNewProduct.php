#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

if (!isset($argv[1])) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}

preg_match('/[\d]{2}-[\d]{3}-[\d]{3}/', $argv[1] , $match);
if (count($match) < 1) {
    echo 'Model number is not specified.' . PHP_EOL;
    return;
}
$sku = $match[0];

/* save product json to local files in dev environment. */
$dir = './rest/productJson/';
if (!file_get_contents($dir . 'mappingAttrs.json')) {
    echo 'Error getting mapping table file.' . PHP_EOL;
    return;
}
if (!file_get_contents($dir . 'categoryMapToAttributeSet.json')) {
    echo 'Error getting category mapping table file.' . PHP_EOL;
    return;
}
if (!file_get_contents($dir . $sku)) {
    echo 'Error getting product json file.' . PHP_EOL;
    return;
}

$productJson = json_decode(file_get_contents($dir . $sku), true);
$mapTable = json_decode(file_get_contents($dir . 'mappingAttrs.json'), true);
$categoryMapToAttributeSet = json_decode(file_get_contents($dir . 'categoryMapToAttributeSet.json'), true);

/*get SubcategoryName in baseinfo*/
$subcategoryName = $productJson['baseinfo']['SubcategoryName'];
echo 'SubcategoryName: ' . $subcategoryName . PHP_EOL;
$mappedAttrSets = $categoryMapToAttributeSet[$subcategoryName];
echo 'map to attribute set names: ' . $mappedAttrSets . PHP_EOL;

/* check existence */
$collection = Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('sku', $sku);
$productExists = false;
if ($collection->count() < 1) {
    echo 'whole new product' . PHP_EOL;
    $model = Mage::getModel('catalog/product');
    $attrSetInfo = attributeSetNameAndId('attributeSetName', $mappedAttrSets);
    echo $mappedAttrSet . 'map to attr set id: ' . $attrSetInfo['id'] . PHP_EOL;
} else {
    $productId = $collection->getFirstItem()->getId();
    $model = Mage::getModel('catalog/product')->load($productId);
    $attrSetInfo = attributeSetNameAndId('attributeSetId', $model->getAttributeSetId());
    echo $mappedAttrSet . 'map to attr set id: ' . $attrSetInfo['id'] . PHP_EOL;
//    $model = Mage::getModel('catalog/product');
    $productExists = true;
    echo 'product exists' . PHP_EOL;
}

if (!$productExists) {
    /* map attribute set */
    $mappedAttrSetsArray = explode(',', $mappedAttrSets);
    if ( count($mappedAttrSetsArray) > 1 ) {
        do {
            /*透過 標準輸出 印出要詢問的內容*/
            fwrite(STDOUT, 'Enter attribute set name to import new product: ');
            /*抓取 標準輸入 的 內容*/
            $mappedAttrSet = trim(fgets(STDIN));
        } while (empty($mappedAttrSet));
        echo 'map to attribute set name: ' . $mappedAttrSet . PHP_EOL;
        $attrSetInfo = attributeSetNameAndId('attributeSetName', $mappedAttrSet);
        echo $mappedAttrSet . 'map to attr set id: ' . $attrSetInfo['id'] . PHP_EOL;
    } else if (count($mappedAttrSetsArray) == 1) {
        $mappedAttrSet = $mappedAttrSetsArray[0];
    } else {
        echo 'no attribute set name map to subcategory: ' . $subcategoryName . PHP_EOL;
        return;
    }

    /* detect websites and select all */


    $model->setAttributeSetId($attrSetInfo['id'])
        ->setData('type_id', 'simple')
        ->setData('status', '1')
        ->setData('tax_class_id', '0')
        ->setData('enable_rma', '0')
        ->setData('visibility', '4')
        ->setWebsiteIds(getAllWebisteIds());

    if (isset($productJson['Model'])) {
        $model->setData('model_number', $productJson['Model']);
    }

}

/* set product description */
if (isset($productJson['intelligence']['Introduction']) && !empty($productJson['intelligence']['Introduction'])) {
    echo 'set product description' . $productJson['intelligence']['Introduction'] . PHP_EOL;
    $model->setData('description', $productJson['intelligence']['Introduction']);
} elseif ( isset($productJson['description']['WebDescription']) && !empty($productJson['description']['WebDescription']) ) {
    echo 'set product description' . $productJson['description']['WebDescription'] . PHP_EOL;
    $model->setData('description', $productJson['description']['WebDescription']);
} else {
    echo json_encode($productJson, JSON_PRETTY_PRINT) . PHP_EOL;
}


foreach ($mapTable as $bigProductInfoItem => $bigItemObject) {
    switch ($bigProductInfoItem) {
        case 'property' :
            /* get all attributes belong to a attribute set id */
            $attributes = Mage::getModel('catalog/product_attribute_api')->items($attrSetInfo['id']);
            foreach ($productJson['property'] as $eachProductPropertyObject) {
                foreach ($bigItemObject as $propertyObject) {
                    /* search if $propertyObject['AttrToMap'] exist in $attributes[]['code'] */
                    if ($eachProductPropertyObject['PropertyCode'] == $propertyObject['PropertyCode']) {
                        echo 'find property code match' . $propertyObject['PropertyCode'] . ' ' . $propertyObject['PropertyName'] . PHP_EOL;
                        foreach ($attributes as $eachAttrObject) {
//                            echo 'compare ' . $eachAttrObject['code'] . ' to array: ' . json_encode($propertyObject['AttrToMap']) . PHP_EOL;
                            if (in_array($eachAttrObject['code'], $propertyObject['AttrToMap'])) {
                                echo 'find code: ' . $eachAttrObject['code'] . PHP_EOL;
                                if (isset($eachProductPropertyObject['UserInputted']) && !empty($eachProductPropertyObject['UserInputted'])) {
                                    $value = $eachProductPropertyObject['UserInputted'];
                                } else {
                                    $value = $eachProductPropertyObject['ValueName'];
                                }
                                switch ($eachAttrObject['type']) {
                                    case 'text' :
                                    case 'textarea' :
                                    case 'weight' :
                                        echo 'set attribute: ' . $eachAttrObject['code'] . ' value: ' . $value . PHP_EOL;
                                        $model->setData($eachAttrObject['code'], $value);
                                        break;
                                    default :
                                        echo 'type problem, type: ' . $eachAttrObject['type'] . PHP_EOL;
                                        die();
                                }
                            }
                        }
                        break;
                    }
                }
            }
            break;
        default :
            foreach ($bigItemObject as $toBeMappedKey => $mapToAttr) {
                if ( !empty($productJson[$bigProductInfoItem][$toBeMappedKey]) ) {
                    $model->setData($mapToAttr, $productJson[$bigProductInfoItem][$toBeMappedKey]);
                }
            }
    }
    $specialBigItems = array('property');
}

/*透過 標準輸出 印出要詢問的內容*/
fwrite(STDOUT, 'Are you sure to save this product information?');
/*抓取 標準輸入 的 內容*/
$sureToAction = trim(fgets(STDIN));

if ( strtolower($sureToAction) == 'y' || strtolower($sureToAction) == 'yes' ) {
    $model->save();
    $productId = $model->getId();

    /* set inventory */
    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($model);
    echo "processing inventory id: " . $model->getId() . PHP_EOL;

    if (!$stockItem->getData('manage_stock')) {
        echo 'not managed by stock' . PHP_EOL;
        $stockItem->setData('product_id', $model->getId());
        $stockItem->setData('stock_id', 1);
        $stockItem->save();

        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($model);
        $stockItem->setData('manage_stock', 1);
        $stockItem->setData('is_in_stock', 1);
        $stockItem->setData('qty', 100);
        $stockItem->save();
    } else {
        if ((string)$stockItem->getData('qty') != '100') {
            $stockItem->setData('manage_stock', 1);
            $stockItem->setData('is_in_stock', 1);
            $stockItem->setData('qty', 100);
            $stockItem->save();
        }
    }

} else {
    exit();
}

/* deal with image part */
$mediaGallery = $model->getMediaGallery();
$dbImageCount = count($mediaGallery['images']);
echo 'image gallery content' . PHP_EOL;
var_dump($mediaGallery['images']);

/* if need to upload or delete images, set status to disable */
if (count($mediaGallery['images']) < 1) {
    echo 'no image, set status to disable product id: ' . $productId . PHP_EOL;
    $model = Mage::getSingleton('catalog/product')->load($productId);
    $model->setData('status', '2')->save();
}

switch ($dbImageCount) {
    case 1 :
        preg_match('/cs/', $mediaGallery['images'][0]['file'], $match);
        if ($match) {
            if (count($productJson['images']) == 1 && strtolower(substr($productJson['images'][0]['ImageName'], 0, 2)) == 'cs') {
                echo 'no new images' . PHP_EOL;
            } else {
                echo 'This product has new images to upload.' . PHP_EOL;
                echo 'code not finished yet.' . PHP_EOL;
            }
            break;
        }
        break;
    case 0 :
        echo 'no image exists, need to upload new images.' . PHP_EOL;
        if (isset($productJson['Images']) && count($productJson['Images'])) {
            $imageUploadResopnse = importProductImageByImageFileName($model, $productJson['Images']);
            if ($imageUploadResopnse) {
                echo 'image upload success' . PHP_EOL;
                $model = Mage::getSingleton('catalog/product')->load($productId);
                $model->setData('status', '1')->save();
            }
        } else {
            echo 'no image information to upload' . PHP_EOL;
        }
        break;
    default :
        $compareResult = existDifferentImages($productInfo['images'], $mediaGallery['images']);
        $message = $compareResult['message'];
        if($message){
            echo "need add or remove images" . PHP_EOL;
            $addList = $compareResult['add'];
            $removeList = $compareResult['remove'];
            if(!empty($addList)){
                echo "add new images" . PHP_EOL;
            }
            if(!empty($removeList)){
                echo "remove exist images" . PHP_EOL;
            }
        }
        else{
            echo 'no need to upload new images.' . PHP_EOL;
        }
}


function importProductImageByImageFileName ($productModel, $imageFileInfoArray) {

    $imageBase = 'http://images10.newegg.com/productimage/';
    $media = Mage::getModel('catalog/product_attribute_media_api');

    foreach ($imageFileInfoArray as $index => $eachFileInfo) {
        // get array of dirname, basename, extension, filename
        $pathInfo = pathinfo($eachFileInfo['ImageName']);
        var_dump($pathInfo);
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
            default:
                return false;
        }
        $tmpFile = file_get_contents($imageBase . $eachFileInfo['ImageName']);
        echo 'image url: ' . $imageBase . $productModel->getSku() . PHP_EOL;
        file_put_contents('imageTmp', $tmpFile);

        if ((int)$eachFileInfo['Priority'] < 2) {
            $mediaArray = array(
                'thumbnail',
                'small_image',
                'image'
            );
        } else {
            $mediaArray = array();
        }

        $newImage = array(
            'file' => array(
                'content' => base64_encode('imageTmp'),
                'mime' => $mimeType,
                'name' => $pathInfo['filename'],
            ),
            'label' => $pathInfo['filename'],
            'position' => (int)$eachFileInfo['Priority'] * 10,
            'types' => $mediaArray,
            'exclude' => 0,
        );
        var_dump($newImage);
        $media->create($productModel->getSku(), $newImage);
    }
    return true;
}