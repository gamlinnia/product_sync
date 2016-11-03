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

$fileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection();

$fileList = array();
foreach ($fileListCollection as $each) {
    $fileName = strtolower($each->getFile());
    if (array_key_exists($fileName, $fileList)) {
        $fileList[$fileName]['count']++;
    }
    else {
        $fileList[$fileName]['id'] = $each->getId();
        $fileList[$fileName]['count'] = 1;
    }
}

$duplicateFileName = array();
$duplicateFileId = array();

foreach ($fileList as $fileName => $data) {
    if($data['count'] > 1) {
        $duplicateFileName[] = $fileName;
        $duplicateFileId[] = $data['id'];
    }
}

if (!empty($duplicateFileName) && !empty($duplicateFileId)) {
    echo "Exist duplicate file list" . PHP_EOL;
    $duplicateFileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection()->addFieldToFilter('file', array('in' => $duplicateFileName));

    $keep = array();
    $delete = array();
    foreach ($duplicateFileListCollection as $each) {
        $id = $each->getId();
        if (in_array($id, $duplicateFileId)) {
            $keep[] = $each->getData();
        } else {
            $delete[] = $each->getData();
        }
    }

    foreach ($delete as $d_each) {
        //file_list_id
        $d_id = $d_each['id'];
        $d_file = $d_each['file'];
        foreach ($keep as $k_each) {
            //file_list_id
            $k_id = $k_each['id'];
            $k_file = $k_each['file'];
            if (strtolower($k_file) == strtolower($d_file)) {
                echo "file list id change from " . $d_id . " to " . $k_id . PHP_EOL;
                $associatedProductCollection = Mage::getModel('downloadablefile/associatedproduct')
                    ->getCollection()
                    ->addFieldToFilter('file_list_id', $d_id);
                foreach ($associatedProductCollection as $each) {
                    var_dump($each->getData());
                    //check if the record already exist in associated product table
                    $existsCollection = Mage::getModel('downloadablefile/associatedproduct')
                        ->getCollection()
                        ->addFieldToFilter('file_list_id', $k_id)
                        ->addFieldToFilter('product_id', $each->getProductId());
                    if ($existsCollection->count() > 0) {
                        //if exist same record, delete $each to avoid duplicate associated product record
                        echo "***Exist***" . PHP_EOL;
                        $each->delete();
                    } else {
                        //if not exist, modify the delete_id to keep_id
                        echo "***Not Exist***" . PHP_EOL;
                        $each->setFileListId($k_id)
                            ->save();
                    }
                }
            }
        }
        //delete duplicate file_list_id
        echo "===========================Delete======================================" . PHP_EOL;
        $needToBeDelete = Mage::getModel('downloadablefile/filelist')->load($d_id);
        var_dump($needToBeDelete->getData());
        Mage::getModel('downloadablefile/filelist')->load($d_id)->delete();
        echo "=======================================================================" . PHP_EOL;
    }
}
else{
    echo "No duplicate file list" . PHP_EOL;
}