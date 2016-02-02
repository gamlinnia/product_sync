<?php

if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
require_once 'lib/phpQuery/phpQuery/phpQuery.php';
require_once 'rest/tools.php';
Mage::app('admin');
$channels = array('newegg');
$productCollection = Mage::getModel('catalog/product')->getCollection();
$productCollection->setOrder('entity_id', 'desc');
$channelReviewModel = Mage::getModel('channelreviews/channelreviews');
$fileList = array();

/*foreach channel*/
foreach($channels as $channel) {
    /*each excel for each channel */
    $arrayToExcel = array();
    /*foreach product*/
    foreach($productCollection as $each){
        $sku = $each->getSku();
        $entity_id = $each->getId();
        echo 'SKU: ' . $sku . PHP_EOL;
        echo 'ID: ' . $entity_id . PHP_EOL;
        $channelReviews = getLatestChannelsProductReviews($channel, $sku);
        /*foreach review*/
        foreach($channelReviews as $eachReview) {
            $detail = $eachReview['detail'];
            $nickname = $eachReview['nickname'];
            $created_at = $eachReview['created_at'];
            $rating = $eachReview['rating'];
            $subject = $eachReview['subject'];

            $reviewCollection = $channelReviewModel->getCollection()
                ->addFieldToFilter('entity_id', $entity_id)
                ->addFieldToFilter('channel', $channel)
                ->addFieldToFilter('detail', array('like' => replaceSpecialCharacters($detail)))
                ->addFieldToFilter('nickname', array('like' => replaceSpecialCharacters($nickname)))
                ->addFieldToFilter('created_at', array('eq' => date("Y-m-d H:i:s", strtotime($created_at))))
                ->addFieldToFilter('rating', $rating);

            if (!empty($subject)) {
                $reviewCollection->addFieldToFilter('subject', array('like' => replaceSpecialCharacters($subject)));
            }

            $reviewCollectionCount = $reviewCollection->count();

            /*if review doesn't exist in database, then save*/
            if ($reviewCollectionCount == 0) {
                try {
                    $data['entity_id'] = $entity_id;
                    $data['channel'] = $channel;
                    $data['detail'] = $detail;
                    $data['nickname'] = $nickname;
                    $data['subject'] = $subject;
                    $data['created_at'] = $created_at;
                    $data['rating'] = $rating;
                    $channelReviewModel->setData($data);
                    $channelReviewModel->save();
                    var_dump($data);

                    /*push  rating 1~2 reviews to array and wait for export to excel*/
                    if ((int)$rating <= 2) {
                        $arrayToExcel[] = $data;
                    }
                } catch (Exception $e) {

                }
            } else {
                echo "Already Exist!" . PHP_EOL;
            }
        }
    }
    /*export all reviews with 1 or 2 rate to excel by channel*/
    if(!empty($arrayToExcel)) {
        $now = date('Y-m-d');
        $fileName = $channel . '_' . $now . '.xls';
        $sheetName = 'Sheet 1';
        /*push file into fileList*/
        $fileList[] = $fileName;
        exportArrayToXlsx($arrayToExcel, array(
            "filename" => $fileName,
            "title" => $sheetName
        ));
    }
}

/*send email notification*/
if(!empty($fileList)) {
    /*sendEmail*/
    sendMailWithDownloadUrl('Channel Reviews', $fileList);
}




