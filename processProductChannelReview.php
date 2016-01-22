<?php
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

require_once 'lib/ganon.php';

$latestChannelsProductReviews = getLatestChannelsProductReviews('newegg', '11-147-153');
foreach ($latestChannelsProductReviews as $eachChannelReview) {
    Zend_Debug::dump($eachChannelReview);
}