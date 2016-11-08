<?php
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
#require_once '../enterprise/public_html/app/Mage.php';
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

$collection = Mage::getModel('blog/post')->getCollection();

foreach($collection as $each) {
    $model = Mage::getModel('blog/post')->load($each->getId());
    $tags = $each->getTags();
    $tags = explode(',', $tags);
    $tags = array_map('trim', $tags);
    $tags = implode(',', $tags);
    $model->setTags($tags)
        ->save();
}
