<?php

require __DIR__ . '/wc-api-php-master/vendor/autoload.php';

use Automattic\WooCommerce\Client;

$consumer_key = 'ck_bef149f9ce816ce6f10596f10d4887741f6e5918'; // Add your own Consumer Key here
$consumer_secret = 'cs_a4434d8df8ff55fc0e59c53c939c999a551bf518'; // Add your own Consumer Secret here
$store_url = 'http://easydatasearch.com/easydata1/wordpress_cSEB/'; // Add the home URL to the store you want to connect to here
$woocommerce = new Client($store_url, $consumer_key, $consumer_secret, ['version' => 'v3',]);

use Automattic\WooCommerce\HttpClient\HttpClientException;

function getCsvContent() {
    $action = array();
    $row = 1;
    if (($handle = fopen('cesbsys/WOO/ProdIn/SLR/inprogress/Book1.csv', "r")) !== FALSE) {
        while (($csvContent = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num = count($csvContent);
            if (DEBUG) {
                #####echo "<p> $num fields in line $row: <br /></p>\n";
            }
            $row++;
            $action1 = array();
            for ($c = 0; $c < $num; $c++) {
                if (DEBUG) {
                    $action1[] = $csvContent[$c];
                    if (DEBUG) {
                        #####echo $csvContent[$c] . "<br/>";
                    }
                }
            }
            array_push($action, $action1);
        }
        fclose($handle);
    }
    return $action;
}

try {
    $request_csv_details = getCsvContent();

    try {
        $wooproducts = $woocommerce->get('products');
        $wooproducts = $wooproducts['products'];
    } catch (HttpClientException $e) {
        echo $e->getMessage(); // Error message.
        $e->getRequest(); // Last request data.
        $e->getResponse(); // Last response data.
    }
    
    $outputprod = array();

    for ($r = 1; $r < count($request_csv_details); $r++) {
        if ($request_csv_details[$r][0] != '' || $request_csv_details[$r][1] != '') {
            try {
                if ($request_csv_details[$r][2] == '' || $request_csv_details[$r][3] == '' || $request_csv_details[$r][4] == '') {
                    $status = 'draft';
                } else {
                    $status = 'publish';
                }
                
                if($request_csv_details[$r][0] != ''){
                    $sku = $request_csv_details[$r][0];
                    $type = 'simple';
                }else{
                    $sku = $request_csv_details[$r][1];
                    $type = 'variable';
                }

                if ($request_csv_details[$r][2] != '') {
                    $catidarr = array();
                    $cats = explode('|', $request_csv_details[$r][2]);
                    #checking categories on woocommerce
                    for ($c = 0; $c < count($cats); $c++) {
                        $availinw = 'No';
                        $woo_avail_cats = $woocommerce->get('products/categories');
                        $woo_avail_cats = $woo_avail_cats['product_categories'];
                        if (count($woo_avail_cats) > 0) {
                            for ($cw = 0; $cw < count($woo_avail_cats); $cw++) {
                                if ($cats[$c] == $woo_avail_cats[$cw]['name']) {
                                    array_push($catidarr, $woo_avail_cats[$cw]['id']);
                                    $availinw = 'Yes';
                                    break;
                                }
                            }
                        }

                        if ($availinw == 'No') {
                            try {
                                $inserted_cat = $woocommerce->post('products/categories', array('product_category' => array("name" => $cats[$c])));
                                array_push($catidarr, $inserted_cat['product_category']['id']);
                            } catch (HttpClientException $e) {
                                echo $e->getMessage(); // Error message.
                                $e->getRequest(); // Last request data.
                                $e->getResponse(); // Last response data.
                            }
                        }
                    }
                }
                
                if($request_csv_details[$r][9] != ''){
                    $images = explode('|', $request_csv_details[$r][9]);
                    if(count($images) > 0){
                        $imgarr = array();
                        for($im=0;$im<count($images);$im++){
                            if($images[$im] != ''){
                                $newarr['src'] = $images[$im];
                                $newarr['position'] = $im;                                
                                array_push($imgarr, $newarr);
                            }
                        }
                    }
                }
                
                $attributes = array();
                $defattributes = array();
                $variation = array();
                             
                if($request_csv_details[$r][6] != ''){
                    $attributesinfo = explode('|', $request_csv_details[$r][6]);
                   
                    if(count($attributesinfo) > 0){                        
                        for($at=0;$at<count($attributesinfo);$at++){
                            if($attributesinfo[$at] != ''){
                                $attrdet = explode('=', $attributesinfo[$at]);                               
                                $opt = explode(';', $attrdet[1]);
                                if(count($opt) > 0){
                                    $nwarray['name'] = $attrdet[0];
                                    $nwarray['slug'] = strtolower($attrdet[0]);
                                    $nwarray['position'] = $at;
                                    $nwarray['options'] = $opt;
                                    $nwarray['visible'] = false;
                                    $nwarray['variation'] = true;                                    
                                    array_push($attributes, $nwarray);
                                    
                                    $nwarray1['name'] = $attrdet[0];
                                    $nwarray1['slug'] = strtolower($attrdet[0]);                                    
                                    $nwarray1['options'] = $opt; 
                                    
                                    if($at == 0){
                                        array_push($defattributes, $nwarray1);
                                    }
                                    
                                    $nwarry2['regular_price'] = $request_csv_details[$r][4];
                                    $nwarry2['attributes'] = $nwarray1;
                                    array_push($variation, $nwarry2);
                                }
                            }                            
                        }                        
                    }
                }

                $product = array(
                    'title' => $request_csv_details[$r][3],
                    'sku' => $sku,
                    'regular_price' => $request_csv_details[$r][4],
                    'type' => $type, 
                    'description' => $request_csv_details[$r][7],
                    'status' => $status,
                    "categories" => $catidarr,
                    "images" => $imgarr,
                    "attributes" => $attributes,
                    "default_attributes" => $defattributes,
                    "variations" => $variation,
                    "stock_quantity" => $request_csv_details[$r][5],
                    "short_description" => $request_csv_details[$r][7],
                    "description" => $request_csv_details[$r][8],
                    "dimensions" => array("length" => $request_csv_details[$r][10], "width" => $request_csv_details[$r][11], "height" => $request_csv_details[$r][12])
                );

                for ($woop = 0; $woop < count($wooproducts); $woop++) {
                    if ($wooproducts[$woop]['sku'] == $request_csv_details[$r][0]) {
                        $woo_prod_avail = 'Yes';
                        break;
                    }
                }
                
                if ($woo_prod_avail == 'No') {                     
                    $inserted = $woocommerce->post('products', array("product" => $product));
                    array_push($outputprod, $inserted['product']);
                }
            } catch (HttpClientException $e) {
                echo $e->getMessage(); // Error message.
                $e->getRequest(); // Last request data.
                $e->getResponse(); // Last response data.
            }
        }
    }
    
    if(count($outputprod) > 0){        
        $file = fopen("cesbsys/WOO/ProdIn/SLR/complete/RCR.csv", "w");
        #set product output csv headers name array  
        $header_array = array('sku', 'variation-sku', 'title', 'DateTimeListed', 'slr_result', 'sku-permalink', 'variation-sku-permalink');
        #set product output csv headers 
        fputcsv($file, $header_array);
        #set product output csv orders details
        if (count($outputprod) > 0) {
            for ($i = 0; $i < count($outputprod); $i++) {
                fputcsv($file, array($outputprod[$i]['sku'], '', $outputprod[$i]['title'], $outputprod[$i]['created_at'], $outputprod[$i]['id'], $outputprod[$i]['permalink'], ''));
            }
        }
    }

    exit;



    $woo_avail_cats = $woocommerce->get('products/categories');

    for ($r = 1; $r < count($request_csv_details); $r++) {
        if ($request_csv_details[$r][0] != '') {
            if ($request_csv_details[$r][3] == '' || $request_csv_details[$r][4] == '') {
                $status = 'draft';
            } else {
                $status = 'publish';
            }
            $product = array(
                'title' => $request_csv_details[$r][3],
                'sku' => $request_csv_details[$r][0],
                'regular_price' => $request_csv_details[$r][4],
                'type' => 'simple', 'description' => $request_csv_details[$r][7],
                'status' => $status,
                "categories" => $catids,
                'images' => array(array("src" => $request_csv_details[$r][9], "position" => '0'))
            );

            print_r($woocommerce->post('products', array("product" => $product)));
        }
    }




    echo "<pre>";
    print_r($results);
} catch (HttpClientException $e) {
    echo $e->getMessage(); // Error message.
    echo $e->getRequest(); // Last request data.
    echo $e->getResponse(); // Last response data.
}

//$results = $woocommerce->post('products/categories', array("product_category" => array("name" => 'Clothings')));

echo "<pre>";
print_r($results);
exit;


#require_once("woocommerce_api/lib/woocommerce-api/class-wc-api-client.php");
require_once( 'woocommerce_api/lib/woocommerce-api.php' );

if ($path == '') {
    $path = dirname(__FILE__);
}


$consumer_key = 'ck_bef149f9ce816ce6f10596f10d4887741f6e5918'; // Add your own Consumer Key here
$consumer_secret = 'cs_a4434d8df8ff55fc0e59c53c939c999a551bf518'; // Add your own Consumer Secret here
$store_url = 'http://easydatasearch.com/easydata1/wordpress_cSEB/'; // Add the home URL to the store you want to connect to here

$options = array(
    'debug' => true,
    'return_as_array' => false,
    'validate_url' => false,
    'timeout' => 30,
    'ssl_verify' => false,
);

// Initialize the class


$client = new WC_API_Client($store_url, $consumer_key, $consumer_secret, $options);

$request_csv_details = getCsvContent();

//$cats = $client->products->get_categories();
//$cats = $client->custom->get('products');

$client->custom->setup('products/categories', 'categories');

$data = array('name' => 'Clothing');

print_r($client->custom->post('', $data));
exit;

//echo "<pre>";
//print_r($cats);
//if(count($cats->product_categories) > 0){
//  $catarr = $cats->product_categories;  
//}else{
//  $catarr = arry();  
//}

for ($r = 1; $r < count($request_csv_details); $r++) {
    if ($request_csv_details[$r][0] != '') {
        try {
            $prod_avail = $client->products->get_by_sku('ddfdfdfd');
        } catch (WC_API_Client_Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            echo $e->getCode() . PHP_EOL;

            if ($e instanceof WC_API_Client_HTTP_Exception) {
                #print_r( $e->get_request() );
                #print_r( $e->get_response() );
            }
        }


        if ($prod_avail->product->id != '') {
            #update
        } else {
            #add
//            if($request_csv_details[$r][2] != ''){
//                $csv_cat = explode('|', $request_csv_details[$r][2]);
//                if(count($csv_cat) > 0){
//                    $catids = array();
//                    for($c=0;$c<count($csv_cat);$c++){
//                        if($csv_cat[$c] != ''){
//                            $avail = 'Yes';
//                            for($ca=0;$ca<count($catarr);$ca++){
//                                $catinf = (array) $catarr[$ca];
//                                if(strtolower($catinf['name']) == strtolower($csv_cat[$c])){
//                                    array_push($catids, $catinf['id']);
//                                    $avail = 'Yes';
//                                    break;
//                                }else{
//                                    $avail = 'No';
//                                }
//                            }
//                            
//                            if($avail == 'No'){
//                                
//                            }                            
//                        }
//                    }
//                }
//            }
            #$request_csv_details[$r][2] == '' || 
            if ($request_csv_details[$r][3] == '' || $request_csv_details[$r][4] == '') {
                $status = 'draft';
            } else {
                $status = 'publish';
            }
            $product = array(
                'title' => $request_csv_details[$r][3],
                'sku' => 'ANP9',
                'regular_price' => $request_csv_details[$r][4],
                'type' => 'simple', 'description' => $request_csv_details[$r][7],
                'status' => $status,
                "categories" => $catids,
                'images' => array(array("src" => $request_csv_details[$r][9], "position" => '0'))
            );

            echo "<pre>";
            print_r($product);
            $prod_inserted = $client->products->create($product);

            echo "<pre>";
            print_r($prod_inserted);
            echo "abobe is response";
        }
    }
}


echo 'rb';



exit;

$orders = $client->orders->get();

#echo "<pre>";
#print_r($orders); exit;

$header_array = array('order-id', 'order-item-id', 'purchase-date', 'payments-date', 'buyer-email', 'buyer-name', 'buyer-phone-number', 'sku', 'variation-sku', 'product-name', 'quantity-purchased', 'currency', 'item-price', 'item-tax', 'shipping-price', 'shipping-tax', 'ship-service-level', 'recipient-name', 'ship-address-1', 'ship-address-2', 'ship-address-3', 'ship-city', 'ship-state', 'ship-postal-code', 'ship-country', 'ship-phone-number', 'delivery-start-date', 'delivery-end-date', 'delivery-time-zone', 'delivery-Instructions', 'sales-channel');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=data.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

fputcsv($output, $header_array);


foreach ($orders->orders as $orderraw) {
    $order_items = $orderraw->line_items;
    if (count($order_items) > 0) {
        foreach ($order_items as $itemraw) {
            fputcsv($output, array($orderraw->id, $itemraw->id, $orderraw->created_at, $orderraw->created_at, $orderraw->billing_address->email, $orderraw->billing_address->first_name, $orderraw->billing_address->phone, $itemraw->sku, 'variation-sku', $itemraw->name, $itemraw->quantity, $orderraw->currency, $itemraw->price, $itemraw->total_tax, 'shipping-price', 'shipping-tax', 'ship-service-level', $orderraw->shipping_address->first_name, $orderraw->shipping_address->address_1, $orderraw->shipping_address->address_2, 'ship-address-3', $orderraw->shipping_address->city, $orderraw->shipping_address->state, $orderraw->shipping_address->postcode, $orderraw->shipping_address->country, $orderraw->shipping_address->phone, 'delivery-start-date', 'delivery-end-date', 'delivery-time-zone', $orderraw->note, 'Woocommerce'));
        }
    }

    #echo "<pre>";
    #print_r($order_items);   
    //fputcsv($file, $header_array);          
}

#echo "<pre>";
#print_r($orders);
#$products = $client->products->get();
#echo "<pre>";
#print_r($products); exit;
?>