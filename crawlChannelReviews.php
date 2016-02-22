<?php

/*log starting time*/
$now = new DateTime(null, new DateTimeZone('UTC'));
file_put_contents('crawlChannelReviews.log', "Process start at: " . $now->format('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

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

$debug = false;

/*product collection*/
$productCollection = Mage::getModel('catalog/product')
    ->getCollection()
    ->addAttributeToSelect('name')
    ->addAttributeToSelect('model_number');
$productCollection->setOrder('entity_id', 'desc');

/*channel review model*/
$channelReviewModel = Mage::getModel('channelreviews/channelreviews');

/*file list array*/
$fileList = array();

/*channels array*/
$channels = array(
    'newegg' => 'http://www.newegg.com/Product/Product.aspx?Item=',
);

if ($debug) {
    $productCollection->setPageSize(1);
    $recipient_array = array(
        'to' => array('Tim.H.Huang@newegg.com'),
        'cc' => array('Stephanie.Y.Chang@rosewill.com'),
        'bcc' => array('Li.L.Liu@newegg.com', 'Tim.H.Huang@newegg.com')
    );
} else {
    $recipient_array = array(
        'to' => array(
            'Fred.F.Yang@rosewill.com',
            'Kenny.T.Chan@rosewill.com',
            'Wentao.W.Zhu@rosewill.com',
            'Thaid.C.Thor@rosewill.com',
            'Audrey.X.Feng@rosewill.com',
            'Sam.T.Chou@rosewill.com',
            'Wayne.M.Chou@rosewill.com'
        ),
        'cc' => array(
            'Carl.S.Pittman@rosewill.com',
            'Jesus.J.Penaloza@rosewill.com',
            'Sunny.S.Ooi@rosewill.com',
            'Gary.K.Peng@rosewill.com',
            'Ray.C.Huang@rosewill.com',
            'Tom.M.Liu@rosewill.com',
            'Jessy.Y.Chu@rosewill.com',
            'Connie.Y.Lu@newegg.com',
            'Mike.L.Zhang@newegg.com',
            'Peggie.P.Hsieh@rosewill.com',
            'Susan.S.Sun@newegg.com',
            'Thompson.Y.Lu@rosewill.com',
            'Weiyu.W.Chen@rosewill.com',
            'Yama.M.Wu@rosewill.com',
            'SB.S.Wu@newegg.com',
            'Bruce.C.Lai@rosewill.com',
            'Stephanie.Y.Chang@rosewill.com'
        ),
        'bcc' => array(
            'Li.L.Liu@newegg.com',
            'Tim.H.Huang@newegg.com'
        )
    );
}

/*foreach channel*/
foreach($channels as $channel => $url) {
    /*each excel for each channel */
    $arrayToExcel = array();
    /*foreach product*/
    foreach($productCollection as $eachProduct){
        $sku = $eachProduct->getSku();
        $entity_id = $eachProduct->getId();
        $productName = $eachProduct->getName();
        $modelNumber = $eachProduct->getModelNumber();
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

            /*check if this review already in database*/
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
                    $data['nickname'] = $nickname;
                    $data['subject'] = $subject;
                    $data['detail'] = $detail;
                    $data['rating'] = $rating;
                    $data['created_at'] = $created_at;
                    $channelReviewModel->setData($data);
                    $channelReviewModel->save();
                    var_dump($data);

                    /*push rating 1~2 reviews to array and wait for export to excel*/
                    if ((int)$rating <= 2 && !morethanDays($created_at, 'America/Los_Angeles', 2)) {
                        $excelData = [];
                        $excelData['item_number'] = $sku;
                        $excelData['product_name'] = $productName;
                        $excelData['model_number'] = $modelNumber;
                        $excelData['product_url'] = $url . $sku;
                        $excelData['rating'] = $rating;
                        $excelData['subject'] = $subject;
                        $excelData['detail'] = str_replace("<br />", "\r\n", $detail);
                        $excelData['created_at'] = $created_at;
                        $excelData['entity_id'] = $entity_id;
                        $excelData['nickname'] = $nickname;
                        $excelData['channel'] = $channel;

                        $arrayToExcel[] = $excelData;
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
    sendMailWithDownloadUrl('Bad product review alert', $fileList, $recipient_array);
}

/*log ending time*/
$now = new DateTime(null, new DateTimeZone('UTC'));
file_put_contents('crawlChannelReviews.log', "Process end at: " . $now->format('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

function morethanDays ($dateTimeString, $locale = 'America/Los_Angeles', $daysMoreThan = 2) {
    // new Date with datetime string in UTC, and get timestamp
    $date = new DateTime('now', new DateTimeZone('UTC'));
    $date->setTimestamp(strtotime($dateTimeString));
    // get datetime string from UTC DateTime object.
    $newDateTimeString = $date->format('Y-m-d H:i:s');
    // Generate a DateTime object with the new dateTime string with new locale
    $newDate = new DateTime($newDateTimeString, new DateTimeZone($locale));
    $reviewTimeStamp = (int)$newDate->format('U');
    // Generate a now DateTime object with new locale
    $nowLATimeStamp = new DateTime('now', new DateTimeZone($locale));
    $nowLATimeStamp = (int)$nowLATimeStamp->format('U');

    $diff =  $nowLATimeStamp - $reviewTimeStamp;
    $days = $diff / 86400;
    if ($days > $daysMoreThan) {
        echo "More than $daysMoreThan days";
        return true;
    }
    return false;
}