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



foreach ($categorysAddList as $mainCategoryName => $subCategoryArray) {
    echo 'deal with main category: ' . $mainCategoryName . PHP_EOL;
    if ( $category = isCategoryExist($mainCategoryName) ) {
        Zend_Debug::dump($category->getData());
        die();
    }

    $mainCategoryId = createCategory($mainCategoryName, null);

    foreach ($subCategoryArray as $subCategoryName) {
        echo 'deal with sub category: ' . $subCategoryName . PHP_EOL;
        $subCategoryId = createCategory($subCategoryName, $mainCategoryId);

        if (!$subCategoryId) {
            echo 'category ' . $subCategoryName . ' exists' . PHP_EOL;
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

function createCategory ($name, $parentId = null, $enabled = 0) {
    $categoryCollection = Mage::getModel( 'catalog/category' )->getCollection()
        ->addAttributeToFilter('name', $name);
    if ($categoryCollection->count() < 1) {
        $category = Mage::getModel('catalog/category');
        $category->setStoreId(0);
        $category->setName($name); // The name of the category
        $category->setUrlKey(strtolower(str_replace(' ', '-', $name))); // The category's URL identifier
        $category->setIsActive($enabled); // Is it enabled?
        $category->setIsAnchor(0);
        $category->setDisplayMode('PRODUCTS');
        $category->setPath('1/2/' . $parentId); // Important you get this right.
        $category->setMetaTitle($name);
        $category->save();

        $mainCategoryId = $category->getId();

        if ($parentId == null) {
            Mage::getModel('catalog/category')->load($mainCategoryId)
                ->setPath('1/2/' . $mainCategoryId)
                ->save();
        } else {
            Mage::getModel('catalog/category')->load($mainCategoryId)
                ->setPath('1/2/' . $parentId . '/' . $mainCategoryId)
                ->save();
        }

        return $mainCategoryId;
    } else {
        return $categoryCollection->getFirstItem()->getId();
    }
}