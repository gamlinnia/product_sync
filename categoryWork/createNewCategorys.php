<?php
/*get config setting*/
if (!file_exists('../config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');

$categorysAddList = array(
    'Headsets & Speakers' => array(
        'Headsets', 'Speakers'
    ),
    'Case' => array(),
    'Power Supply' => array(),
    'Mice' => array(
        'Mice  & Accessories'
    ),
    'Keyboards' => array(
        'Keyboards & Accessories'
    ),
    'Computer Accessories' => array(
        'Cables', 'PC Tools & Accessories', 'USB Hubs & Accessories', 'Hard Drive Docking Stations & External Enclosures', 'Card Readers',
        'Monitor Mounts', 'Projector Mounts', 'Power Strips & Surge Protectors'
    ),
    'Electronics & Accessories' => array(
        'Laptop Backpack & Case', 'Laptop AC Adapter', 'Cellphone & Tablet Mounts', 'Battery & Chargers'
    ),
    'Computer Components' => array(
        'Fans & Accessories', 'SSD & HDD Accessories', 'Coolers', 'Add-on Cards', 'Internal Card Readers and Hubs', 'HDD Enclosures',
        'Hard Drive Controllers & RAID Cards'
    ),
    'Networking' => array(
        'Wireless Accessories', 'Network Interface Cards', 'Network Switches', 'Modems', 'Wireless Adapters', 'Wireless Routers', 'Network Antennas'
    ),
    'Server Systems & Components' => array(
        'Server Accessories', 'Server Chassis', 'RAID - Sub Systems'
    ),
    'Surveillance Cameras' => array(
        'IP & Network Cameras', 'CCTV & Analog Cameras', 'Video Monitoring Kits'
    ),
    'Printer & Office Accessories' => array(
        'Ink & Toner', 'Office Furniture', 'Office Electronics'
    ),
    'Living Room & Other Appliances' => array(
        'TV Antennas & Accessories', 'TV Brackets', 'Fans', 'Heaters', 'Humidifiers', 'Tool & Electrical Accessories'
    ),
    'Kitchen Appliances' => array(
        'Bread Makers', 'Induction Cooktops', 'Rice Cookers', 'Cutlery', 'Air Fryers', 'Ice Cream Makers', 'Juicers & Extractors',
        'Kitchen Scales', 'Popcorn Poppers', 'Thermo Pots', 'Toaster Ovens', 'Ice Makers', 'Electric Kettles', 'Food Dehydrators',
        'Food Steamers', 'Pressure Cookers'
    )
);



/* sub category level 應該是2 */
foreach ($categorysAddList as $mainCategoryName => $subCategoryArray) {
    echo 'deal with main category: ' . $mainCategoryName . PHP_EOL;
    if ( $category = isCategoryExist($mainCategoryName) ) {
        echo 'level: ' . $category->getLevel() . PHP_EOL;
        /* ["level"] => string(1) "3" */
        if ( (int)$category->getLevel() != 2 ) {
            Zend_Debug::dump($category->getData());
        }
    } else {
        echo 'create main category' . PHP_EOL;
        $mainCategoryId = createCategory($mainCategoryName, null);
    }


    /* sub category level 應該是3 */
    foreach ($subCategoryArray as $subCategoryName) {
        echo 'deal with sub category: ' . $subCategoryName . PHP_EOL;

        if ($subCategory = isCategoryExist($subCategoryName)) {
            echo 'level: ' . $subCategory->getLevel() . PHP_EOL;
            if ( (int)$subCategory->getLevel() != 3 ) {
                Zend_Debug::dump($subCategory->getData());

            }
        } else {
            if (!empty($mainCategoryId)) {
                echo 'create sub category' . PHP_EOL;
                $subCategoryId = createCategory($subCategoryName, $mainCategoryId);
            }
        }
    }
}

function isCategoryExist ($name) {
    $categoryCollection = Mage::getModel( 'catalog/category' )->getCollection()
        ->addAttributeToFilter('name', $name);
    if ($categoryCollection->count() < 1) {
        return false;
    }
    echo 'category ' . $name . ' exists' . PHP_EOL;
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