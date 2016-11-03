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

$result = array();
foreach ($fileListCollection as $each) {
    $fileName = strtolower($each->getFile());
    if (array_key_exists($fileName, $result)) {
        $result[$fileName]['count']++;
    }
    else {
        $result[$fileName]['id'] = $each->getId();
        $result[$fileName]['count'] = 1;
    }
}

$duplicate = array();
foreach ($result as $file => $data) {
    if($data['count'] > 1) {
        $duplicateFileName[] = $file;
        $duplicateFileId[] = $data['id'];
    }
}

$duplicateFileListCollection = Mage::getModel('downloadablefile/filelist')->getCollection()->addFieldToFilter('file', array('in'=> $duplicateFileName));

$keep = array();
$delete = array();
foreach ($duplicateFileListCollection as $each) {
    $id = $each->getId();
    if (in_array($id, $duplicateFileId)) {
        $keep[] = $each->getData();
    }
    else{
        $delete[] = $each->getData();
    }
}
//var_dump($delete);
foreach($delete as $d_each) {
    //file_list_id
    $d_id = $d_each['id'];
    $d_file = $d_each['file'];
    $newFileListId = 0;
    foreach($keep as $k_each){
        //file_list_id
        $k_id = $k_each['id'];
        $k_file = $k_each['file'];
        if ( strtolower($k_file) == strtolower($d_file)) {
            echo "file list id change from " .  $d_id . " to " . $k_id . PHP_EOL;
            $associatedProductCollection = Mage::getModel('downloadablefile/associatedproduct')->getCollection()->addfieldToFilter('file_list_id', $d_id);
            foreach ($associatedProductCollection as $each) {
                var_dump($each->getData());
                $each->setFileListId($k_id)
                     ->save();
            }
        }
    }
    //delete duplicate file_list_id
    echo "===========================Delete======================================" . PHP_EOL;
    $needToBeDelete = Mage::getModel('downloadablefile/filelist')->load($d_id);
    var_dump($needToBeDelete->getData());
    Mage::getModel('downloadablefile/filelist')->load($d_id)->delete();
    echo "=======================================================================" . PHP_EOL;
//    $needToBeDelete->delete();

    //var_dump($needToBeDelete->getData());
}

//$associatedProductCollection = Mage::getModel('downloadablefile/associatedproduct')->getCollection();