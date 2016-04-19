#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');


/* save product json to local files in dev environment. */
$dir = './rest/productJson/';
if (!file_exists($dir)) {
    if (!mkdir($dir)) {
        echo 'Error create directory.' . PHP_EOL;
        return;
    }
}
if (!file_exists($dir . 'mappingAttrs.json')) {
    echo 'create new mapping table file, content: ' .  json_encode(array()) .PHP_EOL;
    file_put_contents($dir . 'mappingAttrs.json', json_encode(array()));
}
if (!$mappingAttrs = file_get_contents($dir . 'mappingAttrs.json')) {
    echo 'Error getting mapping table file.' . PHP_EOL;
    return;
}

echo $mappingAttrs . PHP_EOL;
$mapTableArray = json_decode($mappingAttrs, true);

do {
    $acceptInput = array('edit', 'delete', 'quit', 'e', 'd', 'q');
// 透過 標準輸出 印出要詢問的內容
    fwrite(STDOUT, 'To edit or to delete: ');
// 抓取 標準輸入 的 內容
    $action = trim(fgets(STDIN));
} while (!in_array(strtolower($action), $acceptInput));
echo $action . PHP_EOL;

if ($action[0] == 'q') {
    exit(0);
}

do {
// 透過 標準輸出 印出要詢問的內容
    fwrite(STDOUT, 'Enter target to modify [general | property]: ');
// 抓取 標準輸入 的 內容
    $target = trim(fgets(STDIN));
} while (empty($target));
echo $target . PHP_EOL;

switch (strtolower($target)) {
    case 'general' :

        break;
    case 'property' :

        do {
            /*透過 標準輸出 印出要詢問的內容*/
            fwrite(STDOUT, 'Enter PropertyCode: ');
 /*抓取 標準輸入 的 內容*/
            $propertyCode = trim(fgets(STDIN));
        } while (!is_numeric($propertyCode));
        echo $propertyCode . PHP_EOL;

        do {
// 透過 標準輸出 印出要詢問的內容
            fwrite(STDOUT, 'Enter PropertyName: ');
// 抓取 標準輸入 的 內容
            $propertyName = trim(fgets(STDIN));
        } while (empty($propertyCode));
        echo $propertyName . PHP_EOL;

        do {
// 透過 標準輸出 印出要詢問的內容
            fwrite(STDOUT, 'Enter mapping attribute: ');
// 抓取 標準輸入 的 內容
            $attrToMap = trim(fgets(STDIN));
        } while (empty($propertyCode));
        echo $attrToMap . PHP_EOL;

        if (!isset($mapTableArray['property'])) {
            $mapTableArray['property'][] = array(
                'PropertyCode' => $propertyCode,
                'PropertyName' => $propertyName,
                'AttrToMap' => array($attrToMap)
            ) ;
        } else {
            $exist = false;
            foreach ($mapTableArray['property'] as $index => $property) {
                if ($property['PropertyCode'] == $propertyCode) {
                    if ($property['PropertyName'] != $propertyName) {
                        echo 'different code and name' . PHP_EOL;
                        die();
                    }
                    if (!in_array($attrToMap, $property['AttrToMap'])) {
                        $mapTableArray['property'][$index]['AttrToMap'][] = $attrToMap;
                    }
                    $exist = true;
                }
            }
            if (!$exist) {
                $mapTableArray['property'][] = array(
                    'PropertyCode' => $propertyCode,
                    'PropertyName' => $propertyName,
                    'AttrToMap' => array($attrToMap)
                ) ;
            }
        }


        break;
    default :
        exit(0);
}

file_put_contents($dir . 'mappingAttrs.json', json_encode($mapTableArray));