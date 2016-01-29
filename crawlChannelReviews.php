<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
Mage::app('admin');

$productCollection = Mage::getModel('catalog/product')->getCollection();
$reviewModel = Mage::getModel('channelreviews/channelreviews');
$channels = array('newegg');

/*foreach product*/
foreach($productCollection as $each){
    $sku = $each->getSku();
    echo 'SKU: ' . $sku . PHP_EOL;
    $entity_id = $each->getId();
    /*foreach channel*/
    foreach($channels as $channel) {
        $channelReviews = getLatestChannelsProductReviews($channel, $sku);
        /*foreach review*/
        foreach($channelReviews as $eachReview){
            $reviewCollection = $reviewModel->getCollection()
                    ->addFieldToFilter('entity_id', $entity_id)
                    ->addFieldToFilter('channel', $channel)
                    ->addFieldToFilter('detail', $eachReview['detail'])
                    ->addFieldToFilter('nickname', $eachReview['nickname'])
                    ->addFieldToFilter('created_at', array('eq' => date("Y-m-d H:i:s", strtotime($eachReview['created_at']))))
                    ->addFieldToFilter('rating', $eachReview['rating']);

            if(!empty($eachReview['subject'])){
                $reviewCollection->addFieldToFilter('subject', $eachReview['subject']);
            }

            $reviewCollectionCount = $reviewCollection->count();

            /*if review doesn't exist in database, then save*/
            if($reviewCollectionCount == 0) {
                try {
                    $data['entity_id'] = $entity_id;
                    $data['channel'] = $channel;
                    $data['detail'] = $eachReview['detail'];
                    $data['nickname'] = $eachReview['nickname'];
                    $data['subject'] = $eachReview['subject'];
                    $data['created_at'] = $eachReview['created_at'];
                    $data['rating'] = $eachReview['rating'];
                    $reviewModel->setData($data);
                    $reviewModel->save();
                    var_dump($data);
                }
                catch (Exception $e){

                }
            }
            else{
                echo "Already Exist!" . PHP_EOL;
            }
        }
    }
}

