<?php
/*get config setting*/
if (!file_exists('../config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

require_once('data.php');

foreach ($categorysAddList as $mainCategoryName => $subCategoryArray) {
    if (empty($subCategoryArray)) {
        continue;
    } else if (count($subCategoryArray) > 1) { // main category have more than one sub category
        echo 'Deal with main category: ' . $mainCategoryName . PHP_EOL;
        foreach ($subCategoryArray as $eachSubCategory) {
            $subCategoryProductList = getProductListByCategoryName($eachSubCategory);
            $categoryIdArray = getCategoryIdArrayByCategoryName($eachSubCategory);
            array_shift($categoryIdArray);
            array_shift($categoryIdArray);
            foreach ($subCategoryProductList as $eachProductId) {
                $product = Mage::getModel('catalog/product')->load($eachProductId);
                setProductCategoryIdsByCategoryIdArray($product, $categoryIdArray);
            }
        }
    } else { // main category have only one sub category
        echo 'Deal with main category: ' . $mainCategoryName . PHP_EOL;
        if ($category = isCategoryExist($mainCategoryName)) {
            $mainCategoryProductList = getProductListByCategoryName($mainCategoryName);
            $subCategoryName = $subCategoryArray[0];
            $subCategoryProductList = getProductListByCategoryName($subCategoryName);
            $diffList = array();
            $diff_1 = array_diff($mainCategoryProductList, $subCategoryProductList);
            $diff_2 = array_diff($subCategoryProductList, $mainCategoryProductList);
            $diffList = array_merge($diff_1, $diff_2);

            $categoryIdArray = getCategoryIdArrayByCategoryName($subCategoryName);
            array_shift($categoryIdArray);
            array_shift($categoryIdArray);

            foreach ($diffList as $eachProductId) {
                $product = Mage::getModel('catalog/product')->load($eachProductId);
//                echo 'Product ID: ' . $eachProductId . PHP_EOL;
//                echo 'Original Category ids:' . PHP_EOL;
//                var_dump($product->getCategoryIds());
//                echo 'New Category Path: ' . PHP_EOL;
//                var_dump($categoryIdArray);
                setProductCategoryIdsByCategoryIdArray($product, $categoryIdArray);
            }
        }
    }
}

getProductCountInCategories($categorysAddList);

function isCategoryExist ($name) {
    $categoryCollection = Mage::getModel( 'catalog/category' )->getCollection()
        ->addAttributeToFilter('name', $name);
    if ($categoryCollection->count() < 1) {
        return false;
    }
//    echo 'category ' . $name . ' exists, level: ' . $categoryCollection->getFirstItem()->getLevel() . PHP_EOL;
    return Mage::getModel( 'catalog/category' )->load(
        $categoryCollection->getFirstItem()->getId()
    );
}

function getProductListByCategoryName($categoryName) {
    if(empty($categoryName)) {
        return null;
    }
    $productList = array();
    $productCollection = getCategoryByName($categoryName)->getProductCollection();
    foreach ( $productCollection as $_product) {
//          $product = Mage::getModel('catalog/product')->load($_product->getId());
        $productList [] = $_product->getId();
    }
    return $productList;
}

function getProductCountInCategories($categorysAddList){
    foreach ($categorysAddList as $mainCategoryName => $subCategoryArray) {
        $mainProductCount = getCategoryByName($mainCategoryName)->getProductCollection()->count();
        $subProductCount = 0;
        foreach($subCategoryArray as $each) {
            $subProductCount += getCategoryByName($each)->getProductCollection()->count();
        }
        if ($mainProductCount != $subProductCount) {
            echo "Main Category Name: " . $mainCategoryName . ", Count: " . $mainProductCount . PHP_EOL;
            echo "Sub Category Total Count: " . $subProductCount . PHP_EOL;
        }
    }
}