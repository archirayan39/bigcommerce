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
    if (($handle = fopen('cesbsys/WOO/ProdIn/SQP/inprogress/SQP_ICR.csv', "r")) !== FALSE) {
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

    if ($request_csv_details[0][0] == 'sku') {
        $updatearry = array();
        for ($r = 1; $r < count($request_csv_details); $r++) {
            if ($request_csv_details[$r][0] != '' || $request_csv_details[$r][1] != '' || $request_csv_details[$r][2] != '') {
                try {

                    $update_avail = 'No';
                    $price = '';
                    $quantity = '';
                    for ($p = 0; $p < count($wooproducts); $p++) {
                        #echo $request_csv_details[$r][0].' >> '.$wooproducts[$p]['sku'].'<br>';
                        if ($request_csv_details[$r][0] == $wooproducts[$p]['sku']) {
                            $data = array("regular_price" => $request_csv_details[$r][1], "stock_quantity" => $request_csv_details[$r][2], 'managing_stock' => true);
                            $updation = $woocommerce->put('products/'.$wooproducts[$p]['id'], array("product" => $data));
                            $updation = $updation['product'];
                            $upd = array("sku" => $updation['sku'], "price" => $updation['price'], "quantity" => $updation['stock_quantity']);                      
                            array_push($updatearry, $upd);
                            break;                           
                        }
                    }                    
                } catch (HttpClientException $e) {
                    echo $e->getMessage(); // Error message.
                    $e->getRequest(); // Last request data.
                    $e->getResponse(); // Last response data.
                }
            }
        }
    }


    if (count($updatearry) > 0) {       
        $file = fopen("cesbsys/WOO/ProdIn/SQP/complete/SQP_RCR.csv", "w");
        #set product output csv headers name array  
        $header_array = array('sku', 'price', 'quantity', 'gw_result');
        #set product output csv headers 
        fputcsv($file, $header_array);
        #set product output csv orders details
        if (count($updatearry) > 0) {
            for ($i = 0; $i < count($updatearry); $i++) {
                fputcsv($file, array($updatearry[$i]['sku'], $updatearry[$i]['price'], $updatearry[$i]['quantity'], 'success'));
            }
        }
        
        echo 'success'; exit;
    }    
} catch (Exception $e) {
    echo $e->getMessage(); // Error message.
    echo $e->getRequest(); // Last request data.
    echo $e->getResponse(); // Last response data.
}
?>