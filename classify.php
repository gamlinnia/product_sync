<?php
/**
 * Created by IntelliJ IDEA.
 * User: th98
 * Date: 2015/9/25
 * Time: 下午 05:47
 */

// name = c13060_model, value = 2199
function parseAttributeNameToLabel($nameOrId, $value){
    //{"attributeCode":"c13100_power_supply","attributeId":"1644","frontend_input":"select","backend_type":"int","options":[{"value":"2206","label":"120 V \/ 60 Hz"},{"value":"3479","label":"120V"}]}
    $attributeOptions = getAttributeOptions('attributeName', $nameOrId);
    if ($attributeOptions['frontend_input'] == 'select' && $attributeOptions['backend_type'] == 'int'){
        foreach ($attributeOptions['options'] as $object) {
            if ($object['value'] == $value){
                return $object['label'];
            }
        }
    }
}


$input_arr1 = array(
    "entity_id" => "1280",
    "entity_type_id" => "4",
    "attribute_set_id" => "89",
    "type_id" =>  "simple",
    "sku" =>  "96-268-076",
    "has_options" => "0",
    "required_options" =>  "0",
    "created_at" => "2015-11-07 03:11:31",
    "updated_at" =>  "2015-07-29 03:09:25",
    "brand" => "46",
    "status" => "1",
    "visibility" =>  "4",
    "enable_rma" =>  "0",
    "oem" => "3164",
    "restricted_item_mark" =>  "0",
    "orm_d" =>  "0",
    "large_flag" =>  "61",
    "manufacturer" =>  "45",
    "tax_class_id" =>  "0",
    "c13000_brand" =>  "2194",
    "c13020_color" =>  "2195",
    "c13060_model" =>  "2199",
    "c13100_power_supply" =>  "3479",
    //"short_description" =>  "<p>With a retro wooden cabinet, the Rosewill RHWH-14001 Heater is a great way to warm any space up to 1500 square feet in size. With six long-lasting infrared tube heating elements and 1,500W power, it heats your room to a comfortable temperature quickly, and features an ECO mode that keeps the warmth at 500W power. It's designed with safety as a high priority, with an overheat-cutoff design and no exposed heating element, so the housing only gets warm to the touch. The built-in wheels allow the heater to be easily moved from room to room. In addition, a remote control is included, so you can adjust the settings without leaving the comfort of your sofa.</p>",
    "subcategory" => "Heater",
    "length" => "21.2",
    "is_salable" =>  "1"
);

$input_arr2 = array(
    "entity_id" => "1491",
    "entity_type_id" => "4",
    "attribute_set_id" => "63",
    "type_id" => "simple",
    "sku" => "15-166-038",
    "has_options" => "0",
    "required_options" => "0",
    "created_at" => "2015-09-15 06:59:02",
    "updated_at" => "2015-09-15 06:59:02",
    "status" => "1",
    "visibility" => "4",
    "enable_rma" => "0",
    "tax_class_id" => "0",
    "description" => "ADDON CARD ROSEWILL| RC-508 R",
    "short_description" => "ADDON CARD ROSEWILL| RC-508 R",
    "image" => "no_selection",
    "small_image" => "no_selection",
    "thumbnail" => "no_selection",
    "name" => "Add-On Card Up to 5.0 Gbps Data Transfer Rate 4 port USB 3.0 4 port USB 3.0",
    "name_long" => "Rosewill Add-On Card Model RC-508",
    "model_number" => "RC-508",
    "upc_number" => "840951114902",
    "length" => "7.9",
    "width" => "5.6",
    "height" => "1.4",
    "mfrproductpagelink" => "http://www.rosewill.com",
    "ne_manufacturer_part" => "RC-508",
    "ne_length_cm" => "20.066",
    "ne_width_cm" => "14.224",
    "ne_height_cm" => "3.556",
    "ne_package_weight_kg" => "0.1360777",
    "url_key" =>  "add-on-card-up-to-5-0-gbps-data-transfer-rate-4-port-usb-3-0-4-port-usb-3-0",
    "weight" => "0.3000",
    "price" => "44.9900",
    "msrp" => "44.9900",
    "stock_item (Varien_Object)" => "{ }"
);

// data from cron/writeNewProductsToFile.php
$input_collection = [$input_arr1, $input_arr2];

$keyWord = array(
    'direct' => array(
        "weight",
        "length",
        "price",
        "status",
        "visibility",
        "enable_rma",
        "is_salable",
        "entity_type_id",
        "has_options"
    ),
    'dontCare' => array(
        "created_at", "updated_at", "short_description"
    )
);

$response = array();

foreach ($input_collection as $arr) {
    $direct = [];
    $dontCare = [];
    $needToBeParsed = [];
    foreach ($arr as $key => $value) {
        # dontCare class -> in pre-defined dontCare_keyWord array
        if( in_array($key, $keyWord['dontCare']) ){
            $dontCare[$key] = $value;
        }
        # direct class -> the value is not numeric or in pre-defined direct_keyWord array
        else if ( ! is_numeric($value) || in_array($key, $keyWord['direct'])){
            $direct[$key] = $value;
        }
        # needToBeParsed -> others
        else{
            $needToBeParsed[$key] = $value;
        }
    }

    $response[] = array(
        'direct' => $direct,
        'dontCare' => $dontCare,
        'needToBeParsed' => $needToBeParsed
    );
}


echo json_encode($response);
