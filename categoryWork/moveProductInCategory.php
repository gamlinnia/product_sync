<?php
/*get config setting*/
if (!file_exists('../config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
Mage::app('admin');


$new_category_mapping_table = array(
    'Gaming Headsets' => 'Headsets',
    'Gaming Speakers' => 'Speakers',
    'Gaming Cases' => 'Case',
    'Gaming PSUs' => 'Power Supplies',
    'Keyboard Accessories' => 'Keyboards & Accessories',
    'Gaming Keyboards' => 'Keyboards & Accessories',
    'Gaming Mouse / Mice' => 'Mice  & Accessories',
    'PC Tools' => 'PC Tools & Accessories',
//    'Computer Case' => 'Case',
//    'Power Supply' => 'Power Supplies',
    'Laptop Backpack & Case' => 'Laptop Backpack & Case',
    'USB Air Humidifiers' => 'USB Hubs & Accessories',
    'USB LED Lights' => 'USB Hubs & Accessories',
    'SSD & HDD Mounting Kit' => 'SSD & HDD Accessories',
    'SSD / HDD Tray' => 'SSD & HDD Accessories',
    'Hard Drive Docking Station' => 'Hard Drive Docking Stations & External Enclosures',
    '8mm Computer Case Fan' => 'Fans & Accessories',
    '12mm Computer Case Fan' => 'Fans & Accessories',
    '14mm Computer Case Fan' => 'Fans & Accessories',
    'PCI Slot Computer Case Fan' => 'Fans & Accessories',
    'CPU Cooling Fan' => 'Coolers',
    'Add On Card' => 'Add-on Cards',
    'Card Reader' => 'Card Readers',                         // may be 'Internal Card Readers and Hubs'
    'Hard Drive External Enclosure' => 'Hard Drive Docking Stations & External Enclosures',     // HDD Enclosures
    'USB Hub' => 'USB Hubs & Accessories',                   // Internal Card Readers and Hubs
    'Cable Tester' => 'PC Tools & Accessories',
    'PC Headsets' => 'Headsets',
    'PC Speakers' => 'Speakers',
    'Laptop AC Adapter' => 'Laptop AC Adapter',
    'Phone Cables' => 'Cables',
    'Lightning Cables' => 'Cables',
    'Audio Adapters' => 'Cables',
    'Audio Video Splitters' => 'Cables',
    '3.5mm / 2.5mm Audio Cables' => 'Cables',
    'Computer Power Cords' => 'Cables',
    'DVI Cables' => 'Cables',
    'Firewire Cables' => 'Cables',
    'HDMI Cables' => 'Cables',
    'Internal Power Cables' => 'Cables',
    'Mouse / Keyboard (PS2) Cables' => 'Cables',
    'Network Ethernet Cables' => 'Cables',
    'SATA / eSATA Cables' => 'Cables',
    'Serial Cables' => 'Cables',
    'USB Cables' => 'Cables',
    'VGA / SVGA Cables' => 'Cables',
    'Coaxial RF Cables' => 'Cables',
    'Extenders & Repeaters' => 'Cables',
    'Hard Drive Adapters' => 'Cables',
    'KVM Switches' => 'Cables',
    'USB Converters' => 'Cables',
    'Video Adapters' => 'Cables',
    'DisplayPort Cables' => 'Cables',
    'Cable Ties' => 'Cables',
    'Wireless Accessories' => 'Wireless Accessories',
    'Network Interface Card' => 'Network Interface Cards',
    'Network Switcher' => 'Network Switches',
    'Modem' => 'Modems',
    'Wireless Adapters' => 'Wireless Adapters',
    'Wireless Routers' => 'Wireless Routers',
    'Network Antennas' => 'Network Antennas',
    'Computer Keyboard' => 'Keyboards & Accessories',
    'Computer Mouse' => 'Mice  & Accessories',
    'Monitors Accessories' => array(
        array(
            'ne_subcategory' => 'Accessories - Monitors',
            'move_to' => 'Monitor Mounts'
        ),
        array(
            'ne_subcategory' => 'Accessories - Projectors',
            'move_to' => 'Projector Mounts'
        )
    ),
    'Hard Drive Controllers & RAID Cards' => 'Hard Drive Controllers & RAID Cards',
    'Server Accessories' => 'Server Accessories',
    'RAID - Sub Systems' => 'RAID - Sub Systems',
    'IP & Network Cameras' => 'IP & Network Cameras',
    'CCTV & Analog Cameras' => 'CCTV & Analog Cameras',
    'Video Monitoring Kits' => 'Video Monitoring Kits',
    'Power Strips' => 'Power Strips & Surge Protectors',
    'Surge Protector' => 'Power Strips & Surge Protectors',
    'Cartridges & Drum' => 'Ink & Toner',
    'Ink Cartridges' => 'Ink & Toner',
    'Office Chairs & Stools' => 'Office Furniture',
    'Footrest' => 'Office Furniture',
    'Desk Lamps' => 'Office Electronics',
    'Shredders' => 'Office Electronics',
    '3D Filament' => 'Ink & Toner',
    'Digital Media Remote' => 'TV Antennas & Accessories',
    'HDTV Antenna' => 'TV Antennas & Accessories',
    'TV bracket' => 'TV Brackets',
    'Mobile Hardware Accessories' => 'Cellphone & Tablet Mounts',
    'Stylus' => 'Cellphone & Tablet Mounts',
    'Bluetooth Headsets' => 'Cellphone & Tablet Mounts',
    'Bluetooth Speakers' => 'Cellphone & Tablet Mounts',
    'Mounts & Holders' => 'Cellphone & Tablet Mounts',
    'Headphones' => array(
        array(
            'ne_subcategory' => 'Headsets and Accessories',
            'move_to' => 'Headsets'
        ),
        array(
            'ne_subcategory' => 'Headphones and Accessories',
            'move_to' => 'Cellphone & Tablet Mounts'
        ),
        array(
            'ne_subcategory' => 'MP3 / MP4 Player Accessories',
            'move_to' => 'Cellphone & Tablet Mounts'
        )
    ),
    'Battery & Chargers' => 'Battery & Chargers',
    'Bread Maker' => 'Bread Makers',
    'Deep Fryers' => 'Air Fryers',
    'Fan' => 'Fans',
    'Humidifier' => 'Humidifiers',
    'Juicers & Extractors' => 'Juicers & Extractors',
    'Kitchen Scales' => 'Kitchen Scales',
    'Landscape Lighting' => 'Tool & Electrical Accessories',
    'Spot Lights' => 'Tool & Electrical Accessories',
    'Stud Finders' => 'Tool & Electrical Accessories',
    'Solar' => 'Tool & Electrical Accessories',
    'Solar Landscape Lighting' => 'Tool & Electrical Accessories',
    'Solar Spot Light' => 'Tool & Electrical Accessories',
    'Gauges' => 'Tool & Electrical Accessories',
    'Flashlights' => 'Tool & Electrical Accessories',
    'LED Light Bulbs' => 'Tool & Electrical Accessories',

    /* pre-prd沒看到的category */
    'Anti-Static (ESD) Wrist Straps' => 'PC Tools & Accessories',
    'Fan Grills' => 'Fans & Accessories',
    'Fan Filters' => 'Fans & Accessories'
);


foreach ($new_category_mapping_table as $category_name_to_be_mapped => $map_to_category) {
    $category = getCategoryByName($category_name_to_be_mapped);
    if (!$category) {
        echo 'category name: ' . $category_name_to_be_mapped . ' map to nothing' . PHP_EOL;
        exit(0);
    }
    $category_product_collection = $category->getProductCollection();

    echo 'category product collection count: ' . $category_product_collection->count() . PHP_EOL;

    $categoryIdArray = array();

    $categoryIdArray = getCategoryIdArrayByCategoryName($map_to_category);
    if (count($categoryIdArray) < 1) {
        echo 'category id array less than 1' . PHP_EOL;
        exit(0);
    }
    array_shift($categoryIdArray);
    array_shift($categoryIdArray);

    foreach ($category_product_collection as $_product) {
        $product = Mage::getModel('catalog/product')->load(
            $_product->getId()
        );
        echo 'product name: ' . $product->getName() . PHP_EOL;
        setProductCategoryIdsByCategoryIdArray($product, $categoryIdArray);
    }

}

function getNewCategoryName ($map_to_category, $ne_subcategory = null) {
    global $new_category_mapping_table;

    if (!is_array($map_to_category)) {
        return $map_to_category;
    }

    if (empty($ne_subcategory)) {
        echo 'no ne_subcategory inputted' . PHP_EOL;
        return null;
    }

    foreach ($map_to_category as $move_to_object) {
        if ($move_to_object['ne_subcategory'] == $ne_subcategory) {
            return $move_to_object['move_to'];
        }
    }
    echo 'match no ne_subcategory' . PHP_EOL;
    return null;
}