<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
Mage::app('admin');

$collection = Mage::getModel('catalog/product')->getCollection();
$reviewModel = Mage::getModel('channelreviews/channelreviews');
$channels = array('newegg');

/*foreach product*/
foreach($collection as $each){
    $sku = $each->getSku();
    $entity_id = $each->getId();
    /*foreach channel*/
    foreach($channels as $channel) {
        $channelReviews = getLatestChannelsProductReviews($channel, $sku);
        /*foreach review*/
        foreach($channelReviews as $eachReview){
            $collection_count = $reviewModel->getCollection()
                ->addFiledToFilter('entity_id', $entity_id)
                ->addFiledToFilter('channel', $channel)
                ->addFiledToFilter('detail', $eachReview['detail'])
                ->addFiledToFilter('nickname', $eachReview['nickname'])
                ->addFiledToFilter('subject', $eachReview['subject'])
                ->addFiledToFilter('created_at', $eachReview['created_at'])
                ->addFiledToFilter('rating', $eachReview['rating'])
                ->count();

            /*if review doesn't exist in database, then save*/
            if($collection_count == 0) {
                try {
                    $data['entity_id'] = $entity_id;
                    $data['channel'] = $channel;
                    $data['detail'] = $eachReview['detail'];
                    $data['nickname'] = $eachReview['nickname'];
                    $data['subject'] = $eachReview['subject'];
                    $data['created_at'] = $eachReview['created_at'];
                    $data['rating'] = $eachReview['rating'];
                    //$reviewModel->setData($data);
                    //$reviewModel->save();
                    var_dump($data);
                }
                catch (Exception $e){

                }
            }
        }
    }
}

