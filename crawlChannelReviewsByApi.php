<?php
/*get config setting*/
if (!file_exists('config.json')) {
    echo 'config.json is not exist.';
}
$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

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
            'Wayne.M.Chou@rosewill.com',
            'techsupport@rosewill.com'
        ),
        'cc' => array(
            'Carl.S.Pittman@rosewill.com',
            'Jesus.J.Penaloza@rosewill.com',
            'Gary.K.Peng@rosewill.com',
            'Tom.M.Liu@rosewill.com',
            'Connie.Y.Lu@newegg.com',
            'Mike.L.Zhang@newegg.com',
            'Peggie.P.Hsieh@rosewill.com',
            'Susan.S.Sun@newegg.com',
            'Thompson.Y.Lu@rosewill.com',
            'Yama.M.Wu@rosewill.com',
            'Bruce.C.Lai@rosewill.com',
            'Stephanie.Y.Chang@rosewill.com',
            'Vincent.W.Hsueh@newegg.com',
            'Shirley.Q.Pi@rosewill.com',
            'Ruchen.R.Lin@rosewill.com'
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
$response = array();
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

        $channelReviews = getLatestChannelsProductReviewsByApi($channel, $sku, $channelsinfo);
        $review_count = count($channelReviews);
        echo "Total $review_count record(s) found." . PHP_EOL;
        //var_dump($channelReviews);
        /*foreach review*/
        foreach($channelReviews as $eachReview) {
            $detail = $eachReview['detail'];
            $nickname = $eachReview['nickname'];
            $created_at = $eachReview['created_at'];
            $rating = $eachReview['rating'];
            $subject = $eachReview['subject'];
            $product_url = $eachReview['product_url'];

            var_dump($eachReview);

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
//                    if ((float)$rating <= 2 && !morethanDays($created_at, 'America/Los_Angeles', 2)) {
                    if ((float)$rating <= 2 && !moreThanSpecificDays($created_at, 'now', 2)) {
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
        file_put_contents('crawlChannelReviews.log', "Number of Records Need To Export To Excel: " . count($arrayToExcel));
        $now = date('Y-m-d');
        $fileName = 'bad_review/' . $channel . '_' . $now . '.xls';
        $sheetName = 'Sheet 1';
        /*push file into fileList*/
        $fileList[] = $fileName;
        exportArrayToXlsx($arrayToExcel, array(
            "filename" => $fileName,
            "title" => $sheetName
        ));
        $response = array_merge($response, $arrayToExcel);
    }

}

/*export all reviews with 1 or 2 rate to excel by channel*/
if(!empty($response)) {
    /*send email notification*/
    sendMailWithDownloadUrl('Bad product review alert', $fileList, $recipient_array);
} else {
    /* no bad review found. */
    sendMailWithDownloadUrl('Bad product review alert - no bad review submitted', null, $recipient_array);
}

/*log ending time*/
$now = new DateTime(null, new DateTimeZone('UTC'));
file_put_contents('crawlChannelReviews.log', "Process end at: " . $now->format('Y-m-d H:i:s') . PHP_EOL, FILE_APPEND);



function getLatestChannelsProductReviewsByApi ($channel, $sku, $channelsinfo) {
    /* need to include ganon.php */
    $response = array();
    $review_limit = 50;
    switch ($channel) {
        case 'rakuten':
            $channel_title = 'Rakuten.com';
            //product_url is required for this channel
            $required_fields = array('product_url');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $review_url = $product_url = $channelsinfo['product_url'][$channel_title];

            $html = file_get_dom($review_url);

            if(!empty($html)){
                foreach($html('ul.list-reviews li div.rating-block') as $element){
                    //nickname could be empty in rakuten.com
                    $nickname = "None";
                    //rating
                    $ratingStr = $element->parent->getChild(3)->getChild(1)->getChild(1)->getInnerText();
                    preg_match_all('/class="s(.+)">/', $ratingStr, $matchRating);
                    $rating = trim($matchRating[1][0]);
                    if($rating > 10){
                        $rating = $rating/10;
                    }
                    $subjectAndCreatedatAndNicknameStr = $element->parent->getChild(5)->html();
                    if(strpos($subjectAndCreatedatAndNicknameStr, "</span><span")){
                        //if contain </span><span, means there has nickname
                        preg_match_all('/<b>(.+)<\/b>/', $subjectAndCreatedatAndNicknameStr, $matchSubjectAndCreatedatAndNickname);
                        $subjectAndCreatedatAndNicknameStr = $matchSubjectAndCreatedatAndNickname[1][0];

                        //title
                        preg_match_all('/(.+)<\/b>/', $subjectAndCreatedatAndNicknameStr, $matchSubject);
                        $subject = trim($matchSubject[1][0]);
                        //created at
                        preg_match_all('/<\/b>(.+)<br \/>/', $subjectAndCreatedatAndNicknameStr, $matchCreatedat);
                        $created_at = trim($matchCreatedat[1][0]);
                        //nickname
                        preg_match_all('/<b>(.+)/', $subjectAndCreatedatAndNicknameStr, $matchNickname);
                        $nickname = trim($matchNickname[1][0]);
                    }
                    else{
                        //no nickname, process title and created only
                        //title
                        preg_match_all('/<b>(.+)<\/b>/', $subjectAndCreatedatAndNicknameStr, $matchSubject);
                        $subject = trim($matchSubject[1][0]);
                        //created at
                        preg_match_all('/<\/b>(.+)<br \/>/', $subjectAndCreatedatAndNicknameStr, $matchCreatedat);
                        $created_at = trim($matchCreatedat[1][0]);
                    }
                    //detail
                    $detail = trim($element->parent->getChild(6)->getPlainText());

                    $data = array(
                        'detail' => $detail,
                        'nickname' => $nickname,
                        'subject' => $subject,
                        'created_at' => $created_at,
                        'rating' => (string)$rating,
                        'product_url' => $product_url
                    );
                    $response[] = $data;
                }
            }
            break;
        case 'walmart' :
            $channel_title = 'Walmart.com';
            //channel_sku is required for this channel
            $required_fields = array('channel_sku');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = 'http://www.walmart.com/ip/' . $channel_sku;

            $review_url = 'http://www.walmart.com/reviews/api/product/' . $channel_sku . '?limit=10&sort=submission-desc&filters=&showProduct=false';

            $html = CallAPI('GET', $review_url);
            $content = $html['reviewsHtml'];
            preg_match_all('/<h3 class=\"visuallyhidden\">Customer review by ([^>^<]+)/', $content, $matchNickname);
            preg_match_all('/<[^>]+customer-review-title">([^>^<]+)/', $content, $matchSubject);
            preg_match_all('/<p class=\"js-customer-review-text\"[^>]+>([^>^<]+)/', $content, $matchReviewText);
            preg_match_all('/<span class="Grid-col[^>]+customer-review-date[^>]+>([^<]+)/', $content, $matchPostDate);
            preg_match_all('/<span class="visuallyhidden">([^>^<]+) stars/', $content, $matchRating);
            if (!empty($matchNickname[1])) {
                foreach ($matchNickname[1] as $index => $nickname) {
                    $data = array(
                        'nickname' => trim($nickname),
                        'detail' => trim($matchReviewText[1][$index]),
                        'created_at' => trim($matchPostDate[1][$index]),
                        'subject' => trim($matchSubject[1][$index]),
                        'rating' => trim($matchRating[1][$index]),       // first one is overall rating
                        'product_url' => $product_url
                    );
                    $response[] = $data;
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'wayfair' :
            $channel_title = 'Wayfair.com';
            // both channel_sku and product_url are required
            $required_fields = array('channel_sku', 'product_url');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $product_url = $channelsinfo['product_url'][$channel_title];
            $channel_sku = $channelsinfo['channel_sku'][$channel_title];

            $review_url = "http://www.wayfair.com/a/product_review_page/get_update_reviews_json?_format=json&page_number=1&sort_order=date_desc&filter_rating=&filter_tag=&item_per_page=" . $review_limit. "&product_sku=" . $channel_sku;
//            //vaildate
//            $html = CallAPI('GET', $review_url);
//
//            preg_match('/<script type=\"text\/javascript\" src=\"\/(wf-[^>]+.js)\"/', trim($html), $match);
//            $js_file = $match[1];
//            $base_url = parse_url($review_url);
//            $second_url = $base_url['scheme'] . "://" .  $base_url['host'] . "/" . $js_file;
//            $js_headers = get_headers($second_url, 1);
//            $third_url = $base_url['scheme'] . "://" .  $base_url['host'] . $js_headers['X-JU'];
//
//            $data = 'p=%7B%22appName%22%3A%22Netscape%22%2C%22platform%22%3A%22Win32%22%2C%22cookies%22%3A1%2C%22syslang%22%3A%22zh-TW%22%2C%22userlang%22%3A%22zh-TW%22%2C%22cpu%22%3A%22%22%2C%22productSub%22%3A%2220030107%22%2C%22setTimeout%22%3A0%2C%22setInterval%22%3A0%2C%22plugins%22%3A%7B%220%22%3A%22ShockwaveFlash%22%2C%221%22%3A%22WidevineContentDecryptionModule%22%2C%222%22%3A%22ChromePDFViewer%22%2C%223%22%3A%22NativeClient%22%2C%224%22%3A%22ChromePDFViewer%22%7D%2C%22mimeTypes%22%3A%7B%220%22%3A%22ShockwaveFlashapplication%2Fx-shockwave-flash%22%2C%221%22%3A%22ShockwaveFlashapplication%2Ffuturesplash%22%2C%222%22%3A%22WidevineContentDecryptionModuleapplication%2Fx-ppapi-widevine-cdm%22%2C%223%22%3A%22application%2Fpdf%22%2C%224%22%3A%22NativeClientExecutableapplication%2Fx-nacl%22%2C%225%22%3A%22PortableNativeClientExecutableapplication%2Fx-pnacl%22%2C%226%22%3A%22PortableDocumentFormatapplication%2Fx-google-chrome-pdf%22%7D%2C%22screen%22%3A%7B%22width%22%3A1440%2C%22height%22%3A900%2C%22colorDepth%22%3A24%7D%2C%22fonts%22%3A%7B%220%22%3A%22Calibri%22%2C%221%22%3A%22Cambria%22%2C%222%22%3A%22Constantia%22%2C%223%22%3A%22LucidaBright%22%2C%224%22%3A%22Georgia%22%2C%225%22%3A%22SegoeUI%22%2C%226%22%3A%22Candara%22%2C%227%22%3A%22TrebuchetMS%22%2C%228%22%3A%22Verdana%22%2C%229%22%3A%22Consolas%22%2C%2210%22%3A%22LucidaConsole%22%2C%2211%22%3A%22LucidaSansTypewriter%22%2C%2212%22%3A%22CourierNew%22%2C%2213%22%3A%22Courier%22%7D%7D';
//
//            $ch = curl_init($third_url);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
//            $result = curl_exec($ch);
//            curl_close($ch);
//
//            //get cookie
//            $cookie_file = file_get_contents('cookie.txt');
//
//            $data = explode("\n", $cookie_file);
//            $cookies = array();
//            foreach($data as $index => $line) {
//                if($index >= 4 && $index < 9){
//                    $str = explode("\t", $line);
//                    $cookies[] = $str[5] . "=" . $str[6];
//                    //var_dump($str);
//                }
//            }
//            $cookie = implode(';', $cookies);

            $cookie = getCookieFromAws('wayfair', $channel_sku, $product_url);

            //get review data
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $review_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            //curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
            $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            $html = curl_exec($ch);
            curl_close($ch);
            $content = json_decode(trim($html), true);
            foreach($content['reviews'] as $each){
                $detail = trim($each['product_comments']);
                $subject = trim($each['headline']);
                $nickname = trim($each['reviewer_name']);
                $created_at = trim($each['date']);
                $rating = trim($each['rating']);

                $data = array(
                    'detail' => $detail,
                    'nickname' => $nickname,
                    'subject' => $subject,
                    'created_at' => $created_at,
                    'rating' => $rating,
                    'product_url' => $product_url
                );
                $response[] = $data;
            }

            break;
        case 'sears' :
            $channel_title = 'Sears.com';
            // both channel_sku and product_url are required
            $required_fields = array('channel_sku', 'product_url');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = $channelsinfo['product_url'][$channel_title];

            $review_url = "http://www.sears.com/content/pdp/ratings/single/search/Sears/" . $channel_sku ."&targetType=product&limit=". $review_limit . "&offset=0";

            $html = file_get_contents($review_url);
            $html = json_decode($html,true);
            $review_data = $html['data']['reviews'];

            if(!empty($review_data)){
                foreach($review_data as $each_review){
                    $date = new Zend_Date(strtotime(trim($each_review['published_date'])));
                    $data = array(
                        'detail' => trim($each_review['content']),
                        'nickname' => htmlentities(trim($each_review['author']['screenName'])),
                        'subject' => htmlentities(trim($each_review['summary'])),
                        'created_at' => $date->get('MMM dd, yyyy'),
                        'rating' => trim($each_review['attribute_rating'][0]['value']),
                        'product_url' => $product_url
                    );
                    $response[] = $data;
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'homedepot' :
            $channel_title = 'HomeDepot.com';
            // channel_sku is required
            $required_fields = array('channel_sku');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = 'http://www.homedepot.com/p/' . $channel_sku;

            $review_url = 'http://homedepot.ugc.bazaarvoice.com/1999aa/' . $channel_sku . '/reviews.djs?format=embeddedhtml&page=1&sort=submissionTime&scrollToTop=true';

            $content = stripslashes(file_get_contents($review_url));
            preg_match_all('/<span itemprop="author" class="BVRRNickname">([^>^<]+)<\/span>/', $content, $matchNickname);
            preg_match_all('/<span class="BVRRReviewText">([^>^<]+)<\/span>/', $content, $matchReviewText);
            preg_match_all('/<span class="BVRRValue BVRRReviewDate">[^>^<]+<meta itemprop="datePublished" content="([^\"]+)"\/><\/span>/', $content, $matchPostDate);
            preg_match_all('/<span itemprop="ratingValue" class="BVRRNumber BVRRRatingNumber">([^<>]+)<\/span>/', $content, $matchRating);
            preg_match_all('/<span itemprop="name" class="BVRRValue BVRRReviewTitle">([^<>]+)<\/span>/', $content, $matchSubject);
            if (!empty($matchNickname[1])) {
                foreach ($matchNickname[1] as $index => $nickname) {
                    $data = array(
                        'nickname' => trim($nickname),
                        'detail' => trim($matchReviewText[1][$index]),
                        'created_at' => trim($matchPostDate[1][$index]),
                        'subject' => trim($matchSubject[1][$index]),
                        'rating' => trim($matchRating[1][$index +1]),       // first one is overall rating
                        'product_url' => $product_url
                    );
                    $response[] = $data;
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'amazon' :
            $channel_title = 'Amazon.com';

            $required_fields = array('channel_sku');
            $count = 0;
            foreach ($required_fields as $attr) {
                if ( isset($channelsinfo[$attr][$channel_title]) && !empty($channelsinfo[$attr][$channel_title]) ) {
                    $count++;
                }
            }
            if ($count < count($required_fields)) {
                echo 'loss required information' . PHP_EOL;
                return $response;
            }

            $channel_sku = $channelsinfo['channel_sku'][$channel_title];
            $product_url = 'http://www.amazon.com/gp/product/' . $channel_sku;
            $review_url = 'http://www.amazon.com/product-reviews/' . $channel_sku . '/ref=cm_cr_pr_viewopt_srt?ie=UTF8&showViewpoints=1&sortBy=recent&pageNumber=1';

            $html = file_get_dom($review_url);
            foreach ($html('#cm_cr-review_list > .a-section') as $index => $element) {
                echo $index . PHP_EOL;
                echo $element->getPlainText() . PHP_EOL;

                preg_match('/(.+) out of/', $element->getChild(0)->getChild(0)->getPlainText(), $matchRating);
                if (count($matchRating) == 2) {
                    $rating = $matchRating[1];
                    $response[] = array(
                        'detail' => $element->getChild(3)->getPlainText(),
                        'rating' => $rating,
                        'subject' => $element->getChild(0)->lastChild()->getPlainText(),
                        'created_at' => $element->getChild(1)->lastChild()->getPlainText(),
                        'nickname' => $element->getChild(1)->getChild(0)->getPlainText(),
                        'product_url' => $product_url
                    );
                }
            }
            echo json_encode($response) . PHP_EOL;
            break;
        case 'newegg' :
            $product_url = 'http://www.newegg.com/Product/Product.aspx?Item=' . $sku . '&Pagesize=' . $review_limit;

            $review_url  = 'http://apis.newegg.org/api/bu/customerreview/list?parameters=' .
                json_encode(array(
                    "Start" => 0,
                    "Rows" => 10,
                    "FilterQueries" => array(
                        array(
                            "Field" => "p_item_number",
                            "Value" => "17-182-316",
                            "Type" => 0
                        ),
                        array(
                            "Field" => "p_indate",
                            "Value" => "DESC",
                            "Type" => 3
                        )
                    )
                ));

            die($review_url);

//            $html = file_get_dom($review_url);
            $html = file_get_contents($review_url);
            $html = preg_replace('/\\\u[\d]{3}[\w]{1}/', '', $html);
            preg_match('/{.+}/', $html, $match);

            $data = $match[0];
            $data = json_decode(trim($data), true);
            $review_list = $data['ReviewList'];
            $html = str_get_dom($review_list);
            if(!empty($html)) {
                foreach ($html('.grpReviews tr td .details') as $element) {
                    $nickname = $element->parent->parent->getChild(0)->getChild(0)->getChild(0)->getPlainText();
                    $created = $element->parent->parent->getChild(0)->getChild(0)->getChild(1)->getPlainText();
                    $ratingText = $element->parent->parent->getChild(1)->getChild(2)->getChild(0)->getPlainText();
                    preg_match('/(\d).?\/.?\d/', $ratingText, $match);
                    if (count($match) == 2) {
                        $rating = $match[1];
                    }
                    if ($element->parent->parent->getChild(1)->getChild(2)->getChild(1)) {
                        $subject = $element->parent->parent->getChild(1)->getChild(2)->getChild(1)->getPlainText();
                    } else {
                        $subject = null;
                    }
                    $detail = trim($element->getPlainText());
                    /*remove string before "Pros: " and add <br /> in front of "Crons:" and "Other Thoughts:"*/
                    $detail =  substr($detail, strpos($detail, 'Pros:'), strlen($detail));
                    $detail = str_replace('Cons:', '<br /><br />Cons:', $detail);
                    $detail = str_replace('Other Thoughts:', '<br /><br />Other Thoughts:', $detail);
                    $detail = trim($detail);

                    if(stripos($detail, 'Manufacturer Response:') !== false){
                        continue;
                    }

                    $data = array(
                        'detail' => $detail,
                        'nickname' => htmlentities($nickname),
                        'subject' => htmlentities($subject),
                        'created_at' => $created,
                        'rating' => $rating,
                        'product_url' => $product_url
                    );
//                    var_dump($data);
                    $response[] = $data;
                }
            }
            else {
                echo "Empty html...." . PHP_EOL;
            }
            break;
    }
    return $response;
}
