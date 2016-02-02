<?php
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
Mage::app('admin');

require_once 'lib/ganon.php';

//$latestChannelsProductReviews = getLatestChannelsProductReviews('newegg', '11-147-153');
//foreach ($latestChannelsProductReviews as $eachChannelReview) {
//    Zend_Debug::dump($eachChannelReview);
//}

$html = file_get_dom('http://www.amazon.com/Rosewill-RK-6000-Mechanical-Programmable-Anti-Ghosting/dp/B00G505M4S/ref=sr_1_2?s=pc&ie=UTF8&qid=1454377442&sr=1-2&keywords=rosewill');
foreach ($html('#revMH #revMHRL .a-section') as $element) {
    echo $element->getPlainText();
    die();
}
