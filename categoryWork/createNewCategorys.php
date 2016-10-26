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

$main_category_position = 10;
/* main category level 應該是2 */
foreach ($categorysAddList as $mainCategoryName => $subCategoryArray) {
    echo 'deal with main category: ' . $mainCategoryName . PHP_EOL;
    if ( $category = isCategoryExist($mainCategoryName) ) {
        /* ["level"] => string(1) "3" */
        if ( (int)$category->getLevel() != 2 ) {
            Zend_Debug::dump($category->getData());
            $root_category_id = getCategoryIdByCategoryName('Default Category');
            echo 'root category id: ' . $root_category_id . PHP_EOL;

            $mainCategoryId = $category->getId();
            moveCategory($mainCategoryId, $root_category_id);

        } else {
            $mainCategoryId = $category->getId();
        }
    } else {
        echo 'create main category' . PHP_EOL;
        $mainCategoryId = createCategory($mainCategoryName, null);
        $category = Mage::getModel('catalog/category')->load(
            $mainCategoryId
        );
    }

    echo 'main category is ' . $category->getName() . ' id is ' . $mainCategoryId . PHP_EOL;

    /* sub category level 應該是3 */
    foreach ($subCategoryArray as $subCategoryName) {
        echo 'deal with sub category: ' . $subCategoryName . PHP_EOL;

        if ($subCategory = isCategoryExist($subCategoryName)) {

            $sub_category_path = $subCategory->getPath();
            $sub_category_path_array = explode('/', $sub_category_path);
            if (!in_array($mainCategoryId, $subCategoryArray)) {
                Zend_Debug::dump($subCategory->getData());
                echo 'start moving category: ' . $subCategory->getName() . PHP_EOL;
                moveCategory($subCategory->getId(), $mainCategoryId);
            }

        } else {
            if (!empty($mainCategoryId)) {
                echo 'create sub category' . PHP_EOL;
                $subCategoryId = createCategory($subCategoryName, $mainCategoryId);
            }
        }
    }
    $main_category_position++;
}


function isCategoryExist ($name) {
    $categoryCollection = Mage::getModel( 'catalog/category' )->getCollection()
        ->addAttributeToFilter('name', $name);
    if ($categoryCollection->count() < 1) {
        return false;
    }
    echo 'category ' . $name . ' exists, level: ' . $categoryCollection->getFirstItem()->getLevel() . PHP_EOL;
    return Mage::getModel( 'catalog/category' )->load(
        $categoryCollection->getFirstItem()->getId()
    );
}

function createCategory ($name, $parentId = null, $enabled = 1) {
    $categoryCollection = Mage::getModel( 'catalog/category' )->getCollection()
        ->addAttributeToFilter('name', $name);
    if ($categoryCollection->count() < 1) {
        /* category not exist, create a new one */
        $category = Mage::getModel('catalog/category')
            ->setStoreId(0)
            ->setName($name) // The name of the category
            ->setAttributeSetId(3)
            ->setUrlKey(strtolower(str_replace(' ', '-', $name))) // The category's URL identifier
            ->setIsActive($enabled) // Is it enabled?
            ->setIsAnchor(0)
            ->setDisplayMode('PRODUCTS')
            ->setPath('1/2') // Important you get this right.
            ->setMetaTitle($name)
            ->save();

        $mainCategoryId = $category->getId();

        if ($parentId == null) {
            Mage::getModel('catalog/category')->load($mainCategoryId)
                ->setPath('1/2/' . $mainCategoryId)
                ->setLevel(2)
                ->save();
        } else {
            Mage::getModel('catalog/category')->load($mainCategoryId)
                ->setPath('1/2/' . $parentId . '/' . $mainCategoryId)
                ->setLevel(3)
                ->save();
        }

        return $mainCategoryId;
    } else {
        return $categoryCollection->getFirstItem()->getId();
    }
}

function getCategoryIdByCategoryName ($category_name) {
    $collection = Mage::getModel('catalog/category')->getCollection()
        ->addAttributeToFilter('name', $category_name);
    if ($collection->count() < 1) {
        return null;
    }
    return $collection->getFirstItem()->getId();
}

function moveCategory ($category_id, $parentId) {
    $parent_category = Mage::getModel('catalog/category')->load($parentId);
    $parent_path = $parent_category->getPath();

    $pathArray = explode('/', $parent_path);
    $pathArray[] = $category_id;

    $category = Mage::getModel('catalog/category')->load($category_id);
    $category->setPath(implode('/', $pathArray))
        ->setLevel( count($pathArray) -1 )
        ->save();
    return true;
}

function listCategories() {
    $cate = array();
    $sub = array();
    $categories = Mage::getModel('catalog/category')->getCollection();
    foreach ($categories as $each) {
        $id = $each->getEntityId();
        $level = $each->getLevel();
        $path = $each->getPath() . '/';
        if($level > 2) {
            $sub[$id] = $path;
        }
        // level 2 categories
        else if ($level == 2) {
            $cate[$id] = $path;
        }
    }
    return array('level 2' => $cate, 'other' => $sub);
}

function reCalChildrenCount() {
    $in = listCategories();
    $cate = $in['level 2'];
    $sub = $in['other'];

    $result = array();
    foreach ($cate as $id => $path) {
        $result[$id] = 0;
        foreach ($sub as $subId => $subPath) {
            $pos = strpos($subPath, $path);
            if ( $pos !== false && $pos === 0){
                $result[$id]++;
            }
        }
    }
    foreach($result as $id => $count) {
        $category = Mage::getModel('catalog/category')->load($id);
        $childrenCountInDB = $category->getChildrenCount();
        $newCount = $count;
        if($newCount > $childrenCountInDB) {
            $category->setChildrenCount($newCount)
                     ->save();
        }
        //echo $category->getName() . PHP_EOL;
        //echo "    Children Count in DB: " . $category->getChildrenCount() . PHP_EOL;
        //echo "    Actual Children Count: " . $count . PHP_EOL;
    }

}


function listCategoriesMoveToLevel2() {
    $in = listCategories();
    $cate = $in['level 2'];
    $sub = $in['other'];
    foreach($cate as $id => $path) {
        echo "Level 2 Category: " . $id . PHP_EOL;
        foreach ($sub as $subId => $subPath) {
            $pos = strpos($subPath, '/' . $id . '/');
            if($pos !== false && $pos !== 3) {
                echo "    " . $subPath . PHP_EOL;
            }
        }
    }
}