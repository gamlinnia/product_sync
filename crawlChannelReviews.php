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
require 'vendor/autoload.php';
use JonnyW\PhantomJs\Client;

$debug = false;
if (in_array('debug', $argv)) {
    $debug = true;
}

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
    'amazon' => 'http://www.amazon.com/Rosewill-MicroATX-Tower-Computer-FBM-01/product-reviews/B005LIDU5S/ref=cm_cr_arp_d_viewopt_srt?ie=UTF8&showViewpoints=1&sortBy=recent&pageNumber=1',
    'homedepot' => 'http://homedepot.ugc.bazaarvoice.com/1999aa/205479530/reviews.djs?format=embeddedhtml&page=3&sort=submissionTime&scrollToTop=true',
    'walmart' => 'http://www.walmart.com/reviews/api/product/',
    'wayfair' => 'http://www.wayfair.com/a/product_review_page/get_update_reviews_json?_format=json&page_number=1&sort_order=date_desc&filter_rating=&filter_tag=&item_per_page=10&product_sku=',
    'sears' => 'http://www.sears.com/content/pdp/ratings/single/search/Sears/SPM3300036421&targetType=product&limit=50&offset=0',
    'rakuten' => 'http://rosewillinc.shop.rakuten.com/p/rosewill-rps-200-6-outlets-power-strip-125v-input-voltage-1875w/229101142.html'
);

if ($debug) {
//    $productCollection->setPageSize(1);
    $recipient_array = array(
        'to' => array('Li.L.Liu@newegg.com', 'Tim.H.Huang@newegg.com')
    );
} else {
    $recipient_array = array(
        'to' => array(
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
            'Connie.Y.Lu@newegg.com',
            'Mike.L.Zhang@newegg.com',
            'Peggie.P.Hsieh@rosewill.com',
            'Susan.S.Sun@newegg.com',
            'Thompson.Y.Lu@rosewill.com',
            'Yama.M.Wu@rosewill.com',
            'SB.S.Wu@newegg.com',
            'Bruce.C.Lai@rosewill.com',
            'Stephanie.Y.Chang@rosewill.com',
            'Vincent.W.Hsueh@newegg.com',
            'Shirley.Q.Pi@rosewill.com'

        ),
        'bcc' => array(
            'Li.L.Liu@newegg.com',
            'Tim.H.Huang@newegg.com'
        )
    );
}

/*log starting time*/
$now = new DateTime(null, new DateTimeZone('UTC'));
file_put_contents('crawlChannelReviews.log', "Process start at: " . $now->format('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);

/*foreach channel*/
foreach($channels as $channel => $url) {
    if ($debug) {
        if (!in_array($channel, $argv)) {
            continue;
        }
    }
    /*each excel for each channel */
    $arrayToExcel = array();
    /*foreach product*/
    foreach($productCollection as $eachProduct){
        $sku = $eachProduct->getSku();
        $entity_id = $eachProduct->getId();
        $channelsinfo = Mage::getModel('catalog/product')->load($entity_id)->getChannelsinfo();
        $productName = $eachProduct->getName();
        $modelNumber = $eachProduct->getModelNumber();
        echo 'SKU: ' . $sku . PHP_EOL;
        echo 'ID: ' . $entity_id . PHP_EOL;
        echo 'Channel: ' . $channel . PHP_EOL;

        $channelReviews = getLatestChannelsProductReviews($channel, $sku, $channelsinfo);
        var_dump($channelReviews);
        /*foreach review*/
        foreach($channelReviews as $eachReview) {
            $detail = $eachReview['detail'];
            $nickname = $eachReview['nickname'];
            $created_at = $eachReview['created_at'];
            $rating = $eachReview['rating'];
            $subject = $eachReview['subject'];
            $product_url = $eachReview['product_url'];

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
                    var_dump($data);
                    //if in debug mode, do not save
                    if (!$debug) {
                        $channelReviewModel->setData($data);
                        $channelReviewModel->save();
                    }
                    else{
                        echo 'Debug Mode , not saving' . PHP_EOL;
                    }

                    /*push rating 1~2 reviews to array and wait for export to excel*/
                    if ((float)$rating <= 2 && !morethanDays($created_at, 'America/Los_Angeles', 2)) {
                        $excelData = [];
                        $excelData['item_number'] = $sku;
                        $excelData['product_name'] = $productName;
                        $excelData['model_number'] = $modelNumber;
                        $excelData['product_url'] = $product_url;
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
        echo "More than $daysMoreThan days" . PHP_EOL;
        return true;
    }
    return false;
}