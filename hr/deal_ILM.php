<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../../' . $config['magentoDir'] . 'app/Mage.php';
require_once '../functions.php';
require_once '../lib/PHPExcel-1.8/Classes/PHPExcel.php';
require_once '../lib/ganon.php';

Mage::app('admin');
require '../vendor/autoload.php';
use JonnyW\PhantomJs\Client;


$reader = PHPExcel_IOFactory::createReader('Excel2007'); // ????2007 excel ???¡Ñ
$PHPExcel = $reader->load("ILM_department_name.xlsx"); // ???¡Ñ?W?? ???w?g?W?????D?¡Ò?W
$sheet = $PHPExcel->getSheet(0); // ???????@???u¡±@??(?s??¡Óq 0 ?}?l)
$highestRow = $sheet->getHighestRow(); // ??¡Óo?`?C??
echo '?`?@ '.$highestRow.' ?C';
// ?@???????@?C

$tmpArray = array();
for ($row = 0; $row <= $highestRow; $row++) {
    for ($column = 0; $column <= 12; $column++) {//??¡±A???X?????? ???d???¢X 13 ????
        $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
        if ( !empty($val) ) {
            preg_match('/<department name="(.+)" code="(.+)" \/>/', $val, $match);
            var_dump($match);
            if (count($match) == 3) {
                $tmpArray[$match[1]] = $match[2];
            }
        }
    }
}

foreach ($tmpArray as $key => $value) {
    $response[] = array('dept' => $key, 'code' => $value);
}

var_dump($response);
exportArrayToXlsx($response, array(
    "filename" => 'hr.xls',
    "title" => 'main'
));

Mage::getModel('contactus/notify')->sendNotification('contactus_weekly_data_template', array('li.l.liu@newegg.com'), null, null, null, null, array(array('path'=>'/var/www/html/product_sync/hr/hr.xls', 'name'=>'hr.xls'))
);