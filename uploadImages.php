<?php

if(isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
    try {
        /* Starting upload */
        $uploader = new Varien_File_Uploader('filename');

// Any extention would work
        $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
$uploader->setAllowRenameFiles(false);

// Set the file upload mode
// false -> get the file directly in the specified folder
// true -> get the file in the product like folders
// (file.jpg will go in something like /media/f/i/file.jpg)
$uploader->setFilesDispersion(false);

// We set media as the upload dir
$path = Mage::getBaseDir('media') . DS ;
$uploader->save($path, $_FILES['filename']['name'] );

} catch (Exception $e) {

    }
}