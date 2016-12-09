<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../functions.php';
require_once '../lib/PHPExcel-1.8/Classes/PHPExcel.php';


$reader = PHPExcel_IOFactory::createReader('Excel2007'); // 讀取2007 excel 檔案
$PHPExcel = $reader->load("ILM_department_name.xlsx"); // 檔案名稱 需已經上傳到主機上
$sheet = $PHPExcel->getSheet(0); // 讀取第一個工作表(編號從 0 開始)
$highestRow = $sheet->getHighestRow(); // 取得總列數
echo '總共 '.$highestRow.' 列';
// 一次讀取一列
for ($row = 0; $row <= $highestRow; $row++) {
    for ($column = 0; $column <= 12; $column++) {//看你有幾個欄位 此範例為 13 個位
        $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
        if ( !empty($val) ) {
            echo $val . PHP_EOL;
        }
    }
}