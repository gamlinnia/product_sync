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

$continueStrings = array('y', 'yes');
$exitStrings = array('q', 'quit');
do {

    /*
        do {
            $acceptInput = array('edit', 'delete', 'quit', 'e', 'd', 'q');
            fwrite(STDOUT, 'To edit or to delete: ');
            $action = trim(fgets(STDIN));
        } while (!in_array(strtolower($action), $acceptInput));
    
        if ($action[0] == 'q') {
            exit(0);
        }
    */

    $targetArray = array('general' , 'property' , 'price' , 'intelligence' , 'description' , 'baseinfo' , 'dimension' , 'ProductInfos' , 'inventory');
    do {
        /* 透過 標準輸出 印出要詢問的內容 */
        fwrite(STDOUT, 'Enter target to modify [ ' . implode(' | ', $targetArray) . ' ]: press enter for property');
        /* 抓取 標準輸入 的 內容 */
        $target = trim(fgets(STDIN));
        if (in_array(strtolower($target), $exitStrings)) {
            exit(0);
        }
        if (empty($target)) {
            $target = 'property';
        }
    } while (!in_array(trim($target), $targetArray));

    switch ($target) {
        case 'property' :
            do {
                /*透過 標準輸出 印出要詢問的內容*/
                fwrite(STDOUT, 'Enter PropertyCode: ');
                /*抓取 標準輸入 的 內容*/
                $propertyCode = trim(fgets(STDIN));
                if (in_array(strtolower($propertyCode), $exitStrings)) {
                    exit(0);
                }
            } while (!is_numeric($propertyCode));
            echo $propertyCode . PHP_EOL;

            $exist = false;
            $expectPropertyName = null;
            foreach ($mapTableArray[$target] as $eachProperty) {
                if ($eachProperty['PropertyCode'] == $propertyCode) {
                    $expectPropertyName = $eachProperty['PropertyName'];
                    $exist = true;
                    break;
                }
            }

            if ($exist) {
                fwrite(STDOUT, 'Press Enter to keep the same PropertyName: ' . $expectPropertyName);
                $tempInput = trim(fgets(STDIN));

                if (in_array(strtolower($tempInput), $exitStrings)) {
                    exit(0);
                }

                if (empty($tempInput)) {
                    $propertyName = $expectPropertyName;
                } else {
                    $propertyName = trim(fgets(STDIN));
                }
            } else {
                echo '***** code map to different property name *****' . PHP_EOL;
                do {
                    fwrite(STDOUT, 'Enter PropertyName: ');
                    $propertyName = trim(fgets(STDIN));

                    if (in_array(strtolower($propertyName), $exitStrings)) {
                        exit(0);
                    }
                } while (empty($propertyCode));
            }
            echo $propertyName . PHP_EOL;

            do {
                fwrite(STDOUT, 'Enter mapping attribute: ');
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
            $acceptInput = array('general', 'description', 'price', 'intelligence', 'dimension', 'baseinfo', 'ProductInfos', 'inventory');
            if (!in_array($target, $acceptInput)) {
                exit(0);
            }

            do {
                /*透過 標準輸出 印出要詢問的內容*/
                fwrite(STDOUT, 'Enter product info to be mapped: ');
                /*抓取 標準輸入 的 內容*/
                $toBeMapped = trim(fgets(STDIN));
            } while (empty($toBeMapped));
            echo $toBeMapped . PHP_EOL;

            do {
                /*透過 標準輸出 印出要詢問的內容*/
                fwrite(STDOUT, 'Enter product info map to: ');
                /*抓取 標準輸入 的 內容*/
                $mapToAttribute = trim(fgets(STDIN));
            } while (empty($mapToAttribute));
            echo $mapToAttribute . PHP_EOL;

            $mapTableArray[$target][$toBeMapped] = $mapToAttribute;

    }

    file_put_contents($dir . 'mappingAttrs.json', json_encode($mapTableArray));

    /*透過 標準輸出 印出要詢問的內容*/
    fwrite(STDOUT, 'To continue next mapping?: ');
    /*抓取 標準輸入 的 內容*/
    $continue = trim(fgets(STDIN));
} while (in_array(strtolower($continue), $continueStrings));
