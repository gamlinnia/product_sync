#!/usr/bin/php -q
<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app();

$data = array(
    'videogallery_id' => '102',
    'videogallery_category' =>  'Product Video',
    'videogallery_url' =>  'https://www.youtube.com/watch?v=OUI6iM8iPOs',
    'name' =>  'test video',
    'image' =>  'videogallery_OUI6iM8iPOs.jpg',
    'gallery_image' => '',
    'created' =>  '2015-10-23'
);

parse_str( parse_url( $data['videogallery_url'], PHP_URL_QUERY ) );
$imageUrl = 'http://img.youtube.com/vi/'.$v.'/0.jpg';
$videoimage = $v;
$videoname = $data['name'];

if(isset($data['videogallery_url']) && $data['videogallery_url'] != '') {
    if(!file_exists(Mage::getBaseDir('media').'/videogallery/'))mkdir(Mage::getBaseDir('media').'/videogallery/',0777);
    //$img_file = $videourl;
    $img_file=file_get_contents($imageUrl);
    $file_loc=Mage::getBaseDir('media').DS."videogallery".DS.'videogallery_'.$videoimage.'.jpg';

    $file_handler=fopen($file_loc,'w');

    if(fwrite($file_handler,$img_file)==false){
        echo 'error';
    }
    fclose($file_handler);

    $newfilename ='videogallery_'.$videoimage.'.jpg';
    // Upload the image
    $videoimage = $newfilename;

}

$model = Mage::getModel('videogallery/videogallery');
//$model->setData($data);
$model->setData($data);
//$model->setData($data)->setImage($videoimage)->setName($videoname)->setVideogalleryUrl($data['videogallery_url'])->setVideogalleryCategory($data['videogallery_category']);
$model -> save();
