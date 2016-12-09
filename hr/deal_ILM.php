<?php

$config = json_decode(file_get_contents('../config.json'), true);
require_once '../functions.php';
require_once '../lib/PHPExcel-1.8/Classes/PHPExcel.php';


$reader = PHPExcel_IOFactory::createReader('Excel2007'); // Ū��2007 excel �ɮ�
$PHPExcel = $reader->load("ILM_department_name.xlsx"); // �ɮצW�� �ݤw�g�W�Ǩ�D���W
$sheet = $PHPExcel->getSheet(0); // Ū���Ĥ@�Ӥu�@��(�s���q 0 �}�l)
$highestRow = $sheet->getHighestRow(); // ���o�`�C��
echo '�`�@ '.$highestRow.' �C';
// �@��Ū���@�C
for ($row = 0; $row <= $highestRow; $row++) {
    for ($column = 0; $column <= 12; $column++) {//�ݧA���X����� ���d�Ҭ� 13 �Ӧ�
        $val = $sheet->getCellByColumnAndRow($column, $row)->getValue();
        if ( !empty($val) ) {
            echo $val . PHP_EOL;
        }
    }
}