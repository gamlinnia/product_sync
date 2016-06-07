<?php

$config = json_decode(file_get_contents('config.json'), true);
require_once '../' . $config['magentoDir'] . 'app/Mage.php';
require_once 'functions.php';
require_once 'lib/ganon.php';
require_once 'lib/PHPExcel-1.8/Classes/PHPExcel.php';
Mage::app('admin');

$question = array('from' => 'From specific date(yyyy/mm/dd): ', 'to' => 'To specific date(yyyy/mm/dd): ', 'debug' => 'debug mode(Y/n): ');
$input = array();

do {
    /*透過 標準輸出 印出要詢問的內容*/
    fwrite(STDOUT, $question['debug']);
    /*抓取 標準輸入 的 內容*/
    $debug = trim(fgets(STDIN));
} while(empty($debug));

// input from date
do {
    /*透過 標準輸出 印出要詢問的內容*/
    fwrite(STDOUT, $question['from']);
    /*抓取 標準輸入 的 內容*/
    $input_from = trim(fgets(STDIN));
    $from = new Zend_Date(strtotime($input_from), Zend_Date::TIMESTAMP);
    $from->setTime('00:00:00');
} while(empty($input_from));

do {
    /*透過 標準輸出 印出要詢問的內容*/
    fwrite(STDOUT, $question['to']);
    /*抓取 標準輸入 的 內容*/
    $input_to = trim(fgets(STDIN));
    $to = new Zend_Date(strtotime($input_to), Zend_Date::TIMESTAMP);
    $to->setTime('23:59:59');
    if($to->isEarlier($from)) {
        echo "Please re-enter the 'to' date" . PHP_EOL;
        $keep_going = true;
    }
    else{
        $keep_going = false;
    }
} while(empty($input_to) || $keep_going);

if ($debug) {
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
            'Shirley.Q.Pi@rosewill.com',
            'Ruchen.R.Lin@rosewill.com'
        ),
        'bcc' => array(
            'Li.L.Liu@newegg.com',
            'Tim.H.Huang@newegg.com'
        )
    );
}

$channelReviewModel = Mage::getModel('channelreviews/channelreviews');
$reviewCollection = $channelReviewModel
    ->getCollection()
    ->addFieldToFilter('created_at', array(
            'date' => true, 'from' => $from
        )
    )
    ->addFieldToFilter('created_at', array(
            'date' => true, 'to' => $to
        )
    )
    ->addFieldToFilter('rating', array('lteq'=>'2'))
    ->setOrder('created_at', 'asc');

$arrayToExcel = [];
foreach($reviewCollection as $each) {
    $entity_id = $each->getEntityId();
    $product = Mage::getModel('catalog/product')->load($entity_id);
    $sku = $product->getSku();
    $product_name = $product->getName();
    $model_number = $product->getModelNumber();
    $product_url = 'http://www.newegg.com/Product/Product.aspx?Item=' . $sku . '&Pagesize=50';
    $arrayToExcel[] = array(
        'item_number' => $sku,
        'product_name' => $product_name,
        'model_number' => $model_number,
        'product_url' => $product_url,
        'rating' => $each->getRating(),
        'subject' => $each->getSubject(),
        'detail' => str_replace("<br />", "\r\n", $each->getDetail()),
        'created_at' => $each->getCreatedAt(),
        'entity_id' => $entity_id,
        'nickname' => $each->getNickname(),
        'channel' => $each->getChannel()
    );
}

if(!empty($arrayToExcel)) {
    $now = date('Y-m-d');
    $fileName = 'bad_review/bad_review_'. str_replace("/", "", $input_from) . '-' . str_replace("/", "", $input_to) . '.xls';
    $sheetName = 'Sheet 1';
    /*push file into fileList*/
    $fileList[] = $fileName;
    exportArrayToXlsx($arrayToExcel, array(
        "filename" => $fileName,
        "title" => $sheetName
    ));
}

if(!empty($fileList)) {
    sendMailWithDownloadUrl('Bad product review alert', $fileList, $recipient_array);
}
