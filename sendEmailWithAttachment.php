<?php
/*get config setting*/
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

echo "Please input recipients addresses(use commma separate): " . PHP_EOL;

$recipient = trim(fgets(STDIN));

$recipient_array = explode(',', $recipient);

$recipient_array = array_map('trim', $recipient_array);

//var_dump($recipient_array);

echo "Please input file name(use commma separate): " . PHP_EOL;

$file = trim(fgets(STDIN));

$file_array = explode(',', $file);

$file_array = array_map('trim', $file_array);

//var_dump($recipient_array);
echo "Send....." . PHP_EOL;

sendMailWithDownloadUrl('test', $file_array, $recipient_array);
