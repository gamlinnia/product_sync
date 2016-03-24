<?php

function currentTimeStamp () {
    $date = new DateTime(null, new DateTimeZone("UTC"));
    return $date->getTimestamp();
}

function currentTime () {
    $now = new DateTime(null, new DateTimeZone('UTC'));
    return $now->format('Y-m-d H:i:s');    /*MySQL datetime format*/
}

function currentYear () {
    $now = new DateTime(null, new DateTimeZone('Asia/Taipei'));
    return $now->format('Y');    /*MySQL datetime format*/
}

function currentTimeToMin () {
    $now = new DateTime(null, new DateTimeZone('UTC'));
    return $now->format('Y-m-d H:i') . ':00';    /*MySQL datetime format*/
}

function timeStampParseToDateTimeMinute ($timeStamp) {
    return date('Y-m-d H:i', $timeStamp) . ':00';
}

function timeStampParseToUtcDate ($timeStamp) {
    $dt = new DateTime(null, new DateTimeZone('UTC'));
    $dt->setTimestamp((int)$timeStamp);
    return $dt->format('Y-m-d');
}

function formatTimeToLocal($timestamp, $timeZoneOffset) {
    return date('Y-m-d H:i', ($timestamp - ($timeZoneOffset * 60))) . ':00';
}

function secToTime ($seconds) {
    return gmdate("H:i:s", $seconds);
}

function secToDay ($seconds) {
    return floor((int)$seconds / (60 * 60 * 24));
}

function printArrayToJson ($result) {
    return json_encode($result);
}

function json ($result) {
    return json_encode(
        array(
            'code' => '0',
            'message' => 'success',
            'DataCollection' => $result
        )
    );
}

function jsonResult ($result) {
    $response = array(
        'status' => 'success',
        'DataCollection' => $result
    );
    if (is_array($result)) {
        $response['count'] = count($result);
    }
    return json_encode($response);
}

function jsonStatus ($status) {
    return json_encode(array('status' => $status));
}

function jsonMessage ($status, $message) {
    return json_encode(array('status' => $status, 'message' => $message));
}

function isEmptyInput ($inputArray) {
    global $input;
    foreach ($inputArray AS $inputKey) {
        if (!isset($input[$inputKey]) || empty($input[$inputKey])) {
            return true;
        }
    }
    return false;
}

function parseInputToNumber (&$inputArray) {
    global $input;
    foreach ($inputArray AS $inputKey) {
        $input[$inputKey] = (int)$input[$inputKey];
    }
}

function timeStampParseToLocalTime ($format, $timeStamp, $minuteOffset) {
    date_default_timezone_set('UTC');
    return date($format, ($timeStamp - ($minuteOffset * 60)));
}

function utcTimeParseToTimeStamp ($utcTime) {
    $date = new DateTime($utcTime, new DateTimeZone('UTC'));
    return $date->getTimestamp();
}

function parseInputStringIntoNumber ($input) {
    foreach ($input AS $index => $element) {
        if (is_numeric($element)) {
            $input[$index] = (int)$element;
        }
    }
    return $input;
}

function getUserRole ($userName) {
    $roleInfo = mongoFindOne(DB, 'priviledge', array('user' => $userName), array('role' => true));
    if (!$roleInfo) {
        return null;
    }
    return ($roleInfo['role']);
}

function getUserRoles ($userName) {
    $cursor = mongoFind(DB, 'priviledge', array('user' => $userName), array('role' => true));
    if (empty($cursor)) {
        return null;
    }
    $roles = array();
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        if (!in_array($r['role'], $roles)) {
            array_push($roles, $r['role']);
        }
    }
    return ($roles);
}

function message ($code, $message) {
    return json_encode(
        array('code' => $code,
            'message' => $message)
    );
}

function parseCsvIntoArray ($inputFileName) {
    require_once 'lib/PHPExcel/Classes/PHPExcel.php';
    $objPHPExcel = new PHPExcel();

    date_default_timezone_set('Asia/Taipei');
    try {
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
    } catch(Exception $e) {
        die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
    }

    /*Get worksheet dimensions*/
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnNumber = excel_col_to_num($highestColumn);

    /*Loop through each row of the worksheet in turn*/
    $dataArray = array();
    $title = array();
    for ($row = 1; $row <= $highestRow; $row++){
        $rowRawData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
            NULL,
            TRUE,
            FALSE);
        array_push($dataArray, $rowRawData);
    }
    return $dataArray;
}

/*
 * $excelSheet start from 0
 * $titleRow start from row 1
 * */
function parseXlsxIntoArray ($inputFileName, $excelSheet, $titleRow) {
    date_default_timezone_set('Asia/Taipei');
    try {
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
    } catch(Exception $e) {
        die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
    }

    /*Get worksheet dimensions*/
    $sheetCount = $objPHPExcel->getSheetCount();
    $sheet = $objPHPExcel->getSheet($excelSheet);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnNumber = excel_col_to_num($highestColumn);

    /*Loop through each row of the worksheet in turn*/
    $dataArray = array();
    $rowTitle = array();
    for ($row = $titleRow; $row <= $highestRow; $row++){
        if ($row == $titleRow) {
            /*Read a row of data into an array*/
            $rowTitle = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
        } else {
            /*Read a row of data into an array*/
            $rowRawData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
            /*Insert row data array into your database of choice here*/
            $rowData = array();
            for ($i = 0; $i < $highestColumnNumber; $i++) {
                switch ($rowTitle[0][$i]) {
                    case '入庫日期':
                    case '保固到期日':
                        if ($rowRawData[0][$i] > 25569 && $rowRawData[0][$i] != null) {
                            $rowRawData[0][$i] = ((int)$rowRawData[0][$i] - 25569) * 86400;     /*change excel date number to timestamp.*/
                        } else {
                            $rowRawData[0][$i] = 0;
                        }
                        break;
                    default:
                }
                if ($rowRawData[0][$i] != null) {
                    $rowData[$rowTitle[0][$i]] = $rowRawData[0][$i];
                }
            }
            array_push($dataArray, $rowData);
        }
    }
    return $dataArray;
}

function checkExistedDisplayNames ($displayName) {
    $ds=ldap_connect("10.16.202.5", 389);
    $r=ldap_bind($ds, "CN=Cloud Service Account,OU=ServiceAccounts,OU=ITIN,OU=Special Accounts,DC=buyabs,DC=corp", "11qqAAZZ");

    $sr=ldap_search($ds, "DC=buyabs,DC=corp", "(&(cn=" . ldap_escape($displayName) . "*)(|(co=Taiwan)(co=United States)(co=China)))", array("cn"));

    $infos = ldap_get_entries($ds, $sr);

    foreach ($infos as $info) {
        if (isset($info['cn'][0])) {
            return $info['cn'][0];
        }
    }
    return null;
}

function getEmailFromUserAccount ($userAccount) {
    $ds=ldap_connect("10.16.202.5", 389);
    $r=ldap_bind($ds, "CN=Cloud Service Account,OU=ServiceAccounts,OU=ITIN,OU=Special Accounts,DC=buyabs,DC=corp", "11qqAAZZ");

    $sr=ldap_search($ds, "DC=buyabs,DC=corp", "(&(sAMAccountName=" . ldap_escape($userAccount) . "*)(|(co=Taiwan)(co=United States)(co=China)))", array("mail"));

    $infos = ldap_get_entries($ds, $sr);

    return $infos;
}

function getCountryFromId ($assetId) {
    $rs = mongoFindOne(DB, 'list', array("_id" => new MongoId($assetId)), array('location' => true));
    return $rs['location'];
}

/*英文轉數字*/
function excel_col_to_num($str){
    $result = 0;
    $arr = array_reverse(str_split($str));
    foreach((array)$arr as $key => $val){
        $result += pow(26, $key)*az_num($val);
    }
    return $result;
}

/*英文轉數字對照*/
function az_num($str) {
    if(strtoupper($str)=="A"){return 1;}
    if(strtoupper($str)=="B"){return 2;}
    if(strtoupper($str)=="C"){return 3;}
    if(strtoupper($str)=="D"){return 4;}
    if(strtoupper($str)=="E"){return 5;}
    if(strtoupper($str)=="F"){return 6;}
    if(strtoupper($str)=="G"){return 7;}
    if(strtoupper($str)=="H"){return 8;}
    if(strtoupper($str)=="I"){return 9;}
    if(strtoupper($str)=="J"){return 10;}
    if(strtoupper($str)=="K"){return 11;}
    if(strtoupper($str)=="L"){return 12;}
    if(strtoupper($str)=="M"){return 13;}
    if(strtoupper($str)=="N"){return 14;}
    if(strtoupper($str)=="O"){return 15;}
    if(strtoupper($str)=="P"){return 16;}
    if(strtoupper($str)=="Q"){return 17;}
    if(strtoupper($str)=="R"){return 18;}
    if(strtoupper($str)=="S"){return 19;}
    if(strtoupper($str)=="T"){return 20;}
    if(strtoupper($str)=="U"){return 21;}
    if(strtoupper($str)=="V"){return 22;}
    if(strtoupper($str)=="W"){return 23;}
    if(strtoupper($str)=="X"){return 24;}
    if(strtoupper($str)=="Y"){return 25;}
    if(strtoupper($str)=="Z"){return 26;}
}

function getSapNosFromDB () {
    $cursor = mongoFind(DB, 'list', array(), array('sapno' => true));
    $rs = array();
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        if (isset($r['sapno'])) {
            array_push($rs, $r['sapno']);
        }
    }
    return $rs;
}

function getSapNosFromCsv ($dataArray) {
    $sapnos = array();
    foreach ($dataArray AS $data) {
        array_push($sapnos, $data[0][0]);
    }
    return $sapnos;
}

function dbCsvComparison ($csvSapNos, $country) {
    $rs = array();
    $cursor = mongoFind(DB, 'list', array('sapno' => array('$exists' => true, '$nin' => $csvSapNos), 'location' => $country),
        array(
            'category' => true,
            'sapno' => true,
            'assetno' => true,
            'model' => true,
            'spec' => true,
            'locate' => true
        )
    );
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        unset($r['_id']);
        array_push($rs, $r);
    }
    return $rs;
}

function dbCsvComparisonFromMobile ($csvSapNos, $country) {
    $testCase = array(181634, 181633, 181629, 181453, 181630);
    $rs = array();
    $cursor = mongoFind(DB, 'list', array('sapno' => array('$exists' => true, '$nin' => $csvSapNos), 'location' => $country),
        array(
            'category' => true,
            'sapno' => true,
            'assetno' => true,
            'model' => true,
            'spec' => true,
            'locate' => true
        )
    );
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        unset($r['_id']);
        if (in_array($r['sapno'], $testCase)) {
            array_push($rs, $r);
        }
    }
    return $rs;
}

function listRestDataFromDb ($sapNosArray) {
    $rs = array();
    foreach ($sapNosArray AS $sapNo) {
        $r = mongoFindOne(DB, 'list', $sapNo, array());
        array_push($rs, $r);
    }
    return $rs;
}

function getRoleByUser ($ldapName) {
    $notDeptHead = array('helpDesk', 'admin');
    $cursor = mongoFind(DB, 'priviledge', array('user' => $ldapName), array());
    $pushedArray = array('deptHead' => array(), 'all' => array(), 'user' => array(), 'admin' => array('isAdmin' => false));
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        unset($r['_id']);
        array_push($pushedArray['all'], $r['role']);
        if (!in_array($r['role'], $notDeptHead)) {
            if ($r['title'] == 'manager') {
                array_push($pushedArray['deptHead'], $r['role']);
            } elseif ($r['title'] == 'user') {
                array_push($pushedArray['user'], $r['role']);
            }
        }
        if ($r['role'] == 'admin') {
            $pushedArray['admin'] = array('isAdmin' => true, 'title' => $r['title']);
        }
    }
    return $pushedArray;
}

function splitCharNumber ($origin) {
    $i = 0;
    $a = $origin;
    while (!is_numeric($a)) {
        $i++;
        $a = substr($a, 1);
    }
    return array(substr($origin, 0, $i), $a);
}

function returnAssetInfoByID ($assetID) {
    $rs = mongoFindOne(DB, COLLECTION, array('_id' => new MongoId($assetID)), array());
    unset($rs['_id']);
    unset($rs['dtime']);
    return $rs;
}

function returnDataInfoByID ($id, $collection) {
    $rs = mongoFindOne(DB, $collection, array('_id' => new MongoId($id)), array());
    unset($rs['_id']);
    return $rs;
}

function groupToSeeAll ($roles) {
    if (empty($roles)) {
        return false;
    }
    $groupsToSeeAll = array('IT','GA','FIN', 'GM');
    foreach ($roles AS $role) {
        if (in_array($role, $groupsToSeeAll)) {
            return true;
        }
    }
    return false;
}

function getCategory ($roles) {
    $cursor = mongoFind(DB, 'category',
        array(
            'dept' => array('$in'=>$roles),
            'country' => $_SESSION['location']
        ),
        array());
    $pushedArray = array();
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        $r['id'] = $r['_id']->{'$id'};
        unset($r['_id']);
        array_push($pushedArray, $r['category']);
    }
    return $pushedArray;
}

function userAuthenticate () {
    if (!isset($_SESSION['account'])) {
        return false;
    }
    return true;
}

function sendMailLocalUse ($action, $group) {
    include_once 'Mail.php';
    include_once 'Mail/mime.php' ;
    global $input;
    $subject = getMailSubject($action);
    $html = getMailContent($action);

    $cursor = mongoFind(DB, 'priviledge', array('role' => $group, 'country' => $_SESSION['location']), array('user' => true));
    $pushedArray = array();
    while ($cursor->hasNext()) {
        $r = $cursor->getNext();
        array_push($pushedArray, $r['user']);
    }
    $recipient = array('to' => array());
    foreach ($pushedArray AS $userName) {
        $email = getEmailFromUserAccount($userName);
        array_push($recipient['to'], $email[0]['mail'][0]);
    }

    /* SMTP server name, port, user/passwd */
    $smtpInfo = array("host" => "10.16.11.68",
        "port" => "25",
        "auth" => false);
    $recipients = join(',', $recipient['to']) . (isset($recipient['cc']) ? ', ' . $recipient['cc'] : '') . (isset($recipient['bcc']) ? ', ' . $recipient['bcc'] : '');

    $crlf = "\n";
    $headers = array("From" => "assets@newegg.com",
        "To" => join(',', $recipient['to']),
        "Subject" => $subject);
    if (isset($recipient['cc'])) $headers['Cc'] = $recipient['cc'];
    if (isset($recipient['bcc'])) $headers['Bcc'] = $recipient['bcc'];

    /*Creating the Mime message*/
    $mime = new Mail_mime($crlf);

    /*Setting the body of the email*/
    /*$mime->setTXTBody($body);*/
    $mime->setHTMLBody($html);

    $body = $mime->get();
    $headers = $mime->headers($headers);

    /* Create the mail object using the Mail::factory method */
    $mail_object =& Mail::factory("smtp", $smtpInfo);
    /* Ok send mail */
    $mail_object->send($recipients, $headers, $body);
    error_log(currentTime() . $subject . ' ' . 'e-mail has been sent to' . ' ' . $recipients);
}

function inputLog ($action, $originInput, $udpatedInput) {
    $log['action'] = $action;
    $log['original'] = $originInput;
    $log['updated'] = $udpatedInput;
    mongoInsertion(DB, 'requisition', $log, array('safe' => true));
}

function logEverything ($input, $action, $muser, $country) {
    $log['user'] = $muser;
    $log['action'] = $action;
    $log['mtime'] = currentTimeStamp();
    $log['country'] = $country;
    $log['data'] = $input;
    mongoInsertion(DB, 'log', $log, array('safe' => true));
}

function actionLog ($input, $action) {
    $noIdAction = array('create', 'import', 'User Create', 'update');
    if (isset($_SESSION['account'])) {
        $log['user'] = $_SESSION['account'];
    } else {
        if (isset($input['creator'])) {
            $log['user'] = $input['creator'];
            unset($input['creator']);
        }
    }
    if (in_array($action, $noIdAction)) {
        $log['action'] = $action;
        $log['mtime'] = currentTimeStamp();
        $log['data'] = $input;
        mongoInsertion(DB, 'log', $log, array('safe' => true));
        return;
    }
    $rs = returnAssetInfoByID($input['id']);
    $log['action'] = $action;
    $log['mtime'] = currentTimeStamp();
    $log['data'] = $rs;
    mongoInsertion(DB, 'log', $log, array('safe' => true));
}

function processDeliveryCheck () {
    global $input;
    $id = $input['id'];
    foreach ($input['file'] AS $value) {
        preg_match('/files(.*)$/', $value['fileUrl'], $match); /*match[0] is the directory*/
        unlink($match[0]);
    }
    unset($input['id']);
    unset($input['file']);
    mongoDeletion(DB, 'delivery', array('_id' => new MongoId($id)), array('safe' => true));
}

function returnDisplayNameOfCurrentUser () {
    $accountName = $_SESSION['account'];
    $ds=ldap_connect("10.16.202.5", 389);
    $r=ldap_bind($ds, "CN=Cloud Service Account,OU=ServiceAccounts,OU=ITIN,OU=Special Accounts,DC=buyabs,DC=corp", "11qqAAZZ");

    $sr=ldap_search($ds, "DC=buyabs,DC=corp", "(&(sAMAccountName=" . ldap_escape($accountName) . "*)(|(co=Taiwan)(co=United States)(co=China)))", array("cn"));

    $infos = ldap_get_entries($ds, $sr);

    $displayNames = array();
    foreach ($infos as $info) {
        if (isset($info['cn'][0])) {
            array_push($displayNames, $info['cn'][0]);
        }
    }
    return $infos[0]['cn'][0];
}

function dispRepair($str,$len,$msg,$type='1') {
    $length = $len - strlen($str);
    if($length<1)return $str;
    if ($type == 1) {
        $str = str_repeat($msg,$length).$str;
    } else {
        $str .= str_repeat($msg,$length);
    }
    return $str;
}

function returnAssetLogic ($assetId, $returnQty, $checkResponse) {
    if (!$checkResponse['status']) {
        echo jsonMessage('danger', $checkResponse['message']);
        return false;
    }
    switch ($checkResponse['status']) {
        case 'all':
            switch ($checkResponse['action']) {
                case 'requisite':
                    $inputArray = array('$set' => array('assetStatus' => array('inStock')), '$inc' => array('quantity' => $returnQty), '$unset' => array('requisiteQty' => ''));
                    break;
                case 'allocate':
                    $inputArray = array('$set' => array('assetStatus' => array('inStock')), '$inc' => array('quantity' => $returnQty), '$unset' => array('allocateQty' => ''));
                    break;
                default:
                    return false;
            }
            break;
        case 'partial':
            switch ($checkResponse['action']) {
                case 'requisite':
                    $inputArray = array('$set' => array('assetStatus' => array('inStock', 'requisited')), '$inc' => array('quantity' => $returnQty, 'requisiteQty' => $returnQty*(-1)));
                    break;
                case 'allocate':
                    $inputArray = array('$set' => array('assetStatus' => array('inStock', 'allocated')), '$inc' => array('quantity' => $returnQty, 'allocateQty' => $returnQty*(-1)));
                    break;
                default:
                    return false;
            }
            break;
        default:
            echo jsonMessage('danger', 'Quantity Exception');
            return false;
    }
    $response = mongoUpdate(DB, 'list', array('_id' => new MongoId($assetId)), $inputArray, array('safe' => true));
    if ($response['ok'] < 1) {
        echo jsonMessage('danger', 'DB update error');
        return false;
    }
    return true;
}

function bindAssetInfo ($toBeBindArray, $assetCursor) {
    while ($assetCursor->hasNext()) {
        $r = $assetCursor->getNext();
        foreach ($toBeBindArray AS $key => $eachLog) {
            if ($r['_id']->{'$id'} == $eachLog['assetId']) {
                $toBeBindArray[$key]['assetDetail'] = $r;
            }
        }
    }
    return $toBeBindArray;
}

function filterLowValueAndInventory ($toBeBindArray, $assetCursor, $assetPriceType) {
    $pushArray = array();
    while ($assetCursor->hasNext()) {
        $r = $assetCursor->getNext();
        foreach ($toBeBindArray AS $key => $eachLog) {
            if ($r['_id']->{'$id'} == $eachLog['assetId']) {
                if ($r['assetPriceType'] == $assetPriceType) {
                    $toBeBindArray[$key]['assetDetail'] = $r;
                    array_push($pushArray, $toBeBindArray[$key]);
                }
                break;
            }
        }
    }
    return $pushArray;
}

function getAssetPriceType ($assetId) {
    $result = mongoFindOne(DB, 'list', array('_id' => new MongoId($assetId)), array('assetPriceType' => true));
    return $result['assetPriceType'];
}

function parseMagentoJson ($magentoObject) {
    $result = array();
    foreach ($magentoObject AS $value) {
        array_push($result, $value);
    }
    return $result;
}

function totalSpendTime ($startTimeStamp) {
    $endTimeStamp = currentTimeStamp();
    $duration = $endTimeStamp - $startTimeStamp;
    $mins = floor($duration / 60);
    $sec = $duration % 60;
    if ($mins > 0) {
        return $mins . 'mins ' . $sec . ' secs';
    }
    return $sec . ' secs';
}

function fileNamePrefix ($fileName) {
    $fileNameArray = explode('.', $fileName);
    return $fileNameArray[0];
}

function parseQueryString ($queryStringArray) {
    $count = 0;
    $queryString = '';
    foreach ($queryStringArray AS $key => $value) {
        if ($count == 0) {
            $queryString = '?' . $key . '=' . $value;
        } else {
            $queryString .= '&' . $key . '=' . $value;
        }
        $count++;
    }
    return $queryString;
}

function writeItemNumberToLocal ($magentoProductList) {
    global $config;
    if (!file_exists($config['ItemNumberStoreDirectory'])) {
        mkdir($config['ItemNumberStoreDirectory']);
    }
    foreach ($magentoProductList AS $itemArray) {
        $fileName = $itemArray['sku'];
        if (!empty($itemArray['sku'])) {
            file_put_contents($config['ItemNumberStoreDirectory'] . $fileName, json_encode($itemArray));
        }
    }
    return $magentoProductList;
}

function compareLocalItemNumber ($itemObject) {
    global $config;
    $ItemNumber = $itemObject['ItemNumber'];
    $fileNameIncludeDir = $config['ItemNumberStoreDirectory'] . $ItemNumber;
    if (file_exists($fileNameIncludeDir)) {
        $fileContent = file_get_contents($fileNameIncludeDir);
        $jsonDataArray = json_decode($fileContent, true);
        foreach ($jsonDataArray AS $key => $value) {
            // 判斷現有的資訊與檔案(PIM)中是否有重覆
            if (isset($itemObject[$key])) {
                echo $key . $value;
                return false;
            }
            $itemObject[$key] = $value;
            preg_match('/^[a-z]{1}[0-9]{2}/', $key, $matches);
            if (isset($matches[0]) && !isset($itemObject['attribute_set_code'])) {
                $attribute_set_code = $matches[0];
                $itemObject['attribute_set_code'] = $attribute_set_code;
            }
        }
        $itemObject['exists'] = true;
    } else {
        $itemObject['exists'] = false;
    }
    return $itemObject;
}
