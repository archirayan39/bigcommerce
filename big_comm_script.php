<?php

require __DIR__ . '/wc-api-php-master/vendor/autoload.php';

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

require_once("connection.php");
require_once( 'woocommerce_api/lib/woocommerce-api.php' );
#include("main_script.php");
//constant
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR); // used for cross platform for linux and window
}
define('DEBUG', true);
$panel_path = dirname(__FILE__);

/* * *************
 * CsvPaser is processor of the csv for particular client
 *
 *
 * ************* */

class CsvParser {

    public $dirctory_schema;
    public $csv_file_schema;
    private $currentCsvProcess; // current processing csv for internal use
    private $temp_client;
    public $all_csv_file_schema;

    // main function processCsv ::

    public function processCsv($path = '') {
        // scan dir                       
        if ($path == '') {
            $path = dirname(__FILE__);
        }
        $this->dirlist($path);
        if (is_array($this->csv_file_schema) && count($this->csv_file_schema) > 0) {
            foreach ($this->csv_file_schema as $csvPath => $csvValue) {
                $this->temp_client = $csvPath;

                $this->csvLoader($csvPath . DS . $csvValue);
            }
        }
    }

    function CsvLoader($csvfilename) {
        $this->currentCsvProcess = $csvfilename;
        if (DEBUG) {
            echo '--------now processing ' . $csvfilename . '------ <br/>' . "\n";
        }

        $request_csv_details = $this->getCsvContent();
        #echo '<br> >> '.$this->temp_client.' << <br>';
        if (strpos(dirname($this->temp_client), 'WOO') !== false) {
            #woocommerce actions  
            if (strpos(dirname($this->temp_client), 'GO') !== false) {
                #get orders process
                try {
                    if ($request_csv_details[0][0] == 'GetNewOrders') {
                        $client = new WC_API_Client('http://easydatasearch.com/easydata1/wordpress_cSEB/', 'ck_bef149f9ce816ce6f10596f10d4887741f6e5918', 'cs_a4434d8df8ff55fc0e59c53c939c999a551bf518');
                        $orders = $client->orders->get();
                        $orders_details = $orders->orders;
                        if (count($orders_details) > 0) {
                            #output path cration
                            $path = str_replace("ProdIn", "ProdOut", $this->temp_client);
                            #set orders output csv
                            $file = fopen($path . "/orders.csv", "w");
                            #set orders output csv headers name array  
                            $header_array = array('order-id', 'order-item-id', 'purchase-date', 'payments-date', 'buyer-email', 'buyer-name', 'buyer-phone-number', 'sku', 'variation-sku', 'product-name', 'quantity-purchased', 'currency', 'item-price', 'item-tax', 'shipping-price', 'shipping-tax', 'ship-service-level', 'recipient-name', 'ship-address-1', 'ship-address-2', 'ship-address-3', 'ship-city', 'ship-state', 'ship-postal-code', 'ship-country', 'ship-phone-number', 'delivery-start-date', 'delivery-end-date', 'delivery-time-zone', 'delivery-Instructions', 'sales-channel');
                            #set orders output csv headers 
                            fputcsv($file, $header_array);
                            #set orders output csv orders details
                            foreach ($orders_details as $orderraw) {
                                $order_items = $orderraw->line_items;
                                if (count($order_items) > 0) {
                                    foreach ($order_items as $itemraw) {
                                        fputcsv($file, array($orderraw->id, $itemraw->id, $orderraw->created_at, $orderraw->created_at, $orderraw->billing_address->email, $orderraw->billing_address->first_name, $orderraw->billing_address->phone, $itemraw->sku, 'variation-sku', $itemraw->name, $itemraw->quantity, $orderraw->currency, $itemraw->price, $itemraw->total_tax, '', '', '', $orderraw->shipping_address->first_name, $orderraw->shipping_address->address_1, $orderraw->shipping_address->address_2, '', $orderraw->shipping_address->city, $orderraw->shipping_address->state, $orderraw->shipping_address->postcode, $orderraw->shipping_address->country, $orderraw->shipping_address->phone, '', '', '', $orderraw->note, 'Woocommerce'));
                                    }
                                }
                            }

                            if (is_file($path . "/orders.csv")) {
                                #transfer orders output csv from inprogress to complete
                                @copy($path . "/orders.csv", str_replace('inprogress', 'complete', $path) . "/orders.csv");
                                #remove orders output csv from inprogress
                                @unlink($path . "/orders.csv");
                                #transfer orders request csv from inprogress to complete
                                @copy($this->currentCsvProcess, str_replace('inprogress', 'complete', $this->currentCsvProcess));
                                if (is_file(str_replace('inprogress', 'complete', $path) . "/orders.csv")) {
                                    echo str_replace('inprogress', 'complete', $path) . "/orders.csv << order out put csv generated for Woocommerce <br>";
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            } else if (strpos(dirname($this->temp_client), 'GALR') !== false) {
                #echo $this->temp_client.' << GALR << WOO <br>'; 
            } else if (strpos(dirname($this->temp_client), 'SLR') !== false) {
                $consumer_key = 'ck_bef149f9ce816ce6f10596f10d4887741f6e5918'; // Add your own Consumer Key here
                $consumer_secret = 'cs_a4434d8df8ff55fc0e59c53c939c999a551bf518'; // Add your own Consumer Secret here
                $store_url = 'http://easydatasearch.com/easydata1/wordpress_cSEB/'; // Add the home URL to the store you want to connect to here

                $woocommerce = new Client($store_url, $consumer_key, $consumer_secret, ['version' => 'v3',]);
                try {
                    if ($request_csv_details[0][0] == 'sku') {
                        $product = array();
                        for ($r = 1; $r < count($request_csv_details); $r++) {
                            if ($request_csv_details[$r][0] != '') {
                                try {
                                    if ($request_csv_details[$r][2] == '' || $request_csv_details[$r][3] == '' || $request_csv_details[$r][4] == '') {
                                        $status = 'draft';
                                    } else {
                                        $status = 'publish';
                                    }

                                    if ($request_csv_details[$r][2] != '') {
                                        $catidarr = array();
                                        $cats = explode('|', $request_csv_details[$r][2]);
                                        #checking categories on woocommerce
                                        for ($c = 0; $c < count($cats); $c++) {
                                            $availinw = '';
                                            $woo_avail_cats = $woocommerce->get('products/categories');
                                            $woo_avail_cats = $woo_avail_cats['product_categories'];
                                            if (count($woo_avail_cats) > 0) {
                                                for ($cw = 0; $cw < count($woo_avail_cats); $cw++) {
                                                    if ($cats[$c] == $woo_avail_cats[$cw]['name']) {
                                                        array_push($catidarr, $woo_avail_cats[$cw]['id']);
                                                        $availinw = 'Yes';
                                                        break;
                                                    } else {
                                                        $availinw = 'No';
                                                    }
                                                }
                                            } else {
                                                $availinw = 'No';
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

                                    $product_arr = array(
                                        'title' => $request_csv_details[$r][3],
                                        'sku' => $request_csv_details[$r][0],
                                        'regular_price' => $request_csv_details[$r][4],
                                        'type' => 'simple', 'description' => $request_csv_details[$r][7],
                                        'status' => $status,
                                        "categories" => $catidarr,
                                        'images' => array(array("src" => $request_csv_details[$r][9], "position" => '0')),
                                        "stock_quantity" => $request_csv_details[$r][5],
                                        "short_description" => $request_csv_details[$r][7],
                                        "description" => $request_csv_details[$r][8],
                                        "dimensions" => array("length" => $request_csv_details[$r][10], "width" => $request_csv_details[$r][11], "height" => $request_csv_details[$r][12])
                                    );
                                    $inserted = $woocommerce->post('products', array("product" => $product_arr));

                                    if (is_array($inserted)) {
                                        array_push($product, $inserted['product']);
                                    }
                                } catch (HttpClientException $e) {
                                    echo $e->getMessage(); // Error message.
                                    $e->getRequest(); // Last request data.
                                    $e->getResponse(); // Last response data.
                                }
                            }
                        }
                        
                        #echo "<pre>";
                        #print_r($product); 

                        #output path cration
                        $path = str_replace("ProdIn", "ProdOut", $this->temp_client);
                        #set product output csv
                        echo $path . "/" . date("Y-m-d") . "RCR.csv <<<<<<<<<<<<<<<<<<<< ";
                        $file = fopen($path . "/RCR.csv");
                        #set product output csv headers name array  
                        $header_array = array('sku', 'variation-sku', 'title', 'DateTimeListed', 'slr_result', 'sku-permalink', 'variation-sku-permalink');
                        #set product output csv headers 
                        fputcsv($file, $header_array);
                        #set product output csv orders details
                        if (count($product) > 0) {
                            for ($i = 0; $i < count($product); $i++) {
                                fputcsv($file, array($product[$i]['sku'], '', $product[$i]['title'], $product[$i]['created_at'], $product[$i]['id'], $product[$i]['permalink'], ''));
                            }
                        }

//                        if (is_file($path . "/" . date("Y-m-d") . "RCR.csv")) {
//                            #transfer product output csv from inprogress to complete
//                            @copy($path . "/" . date("Y-m-d") . "RCR.csv", str_replace('inprogress', 'complete', $path) . "/" . date("Y-m-d") . "RCR.csv");
//                            #remove product output csv from inprogress
//                            @unlink($path . "/" . date("Y-m-d") . "RCR.csv");
//                            #transfer product request csv from inprogress to complete
//                            @copy($this->currentCsvProcess, str_replace('inprogress', 'complete', $this->currentCsvProcess));
//                            if (is_file(str_replace('inprogress', 'complete', $path) . "/" . date("Y-m-d") . "RCR.csv")) {
//                                echo str_replace('inprogress', 'complete', $path) . "/orders.csv << order out put csv generated for Woocommerce <br>";
//                            }
//                        }
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        } else if (strpos(dirname($this->temp_client), 'BCC') !== false) {
            #bigcommerce actions
            if (strpos(dirname($this->temp_client), 'GO') !== false) {
                #get orders process
                try {
                    if ($request_csv_details[0][0] == 'GetNewOrders') {
                        $store = new connection('api_1', 'https://store-3a2hr0hsri.mybigcommerce.com/api/v2/', '8223bb1d4ea51a2d41cb42b59be9492dd99c0ce3');
                        $req = 'orders?sort=date_created:desc';
                        $orders = $store->get($req);
                        if (count($orders) > 0) {
                            #output path cration
                            $path = str_replace("ProdIn", "ProdOut", $this->temp_client);
                            #set orders output csv
                            $file = fopen($path . "/orders.csv", "w");
                            #set orders output csv headers name array  
                            $header_array = array('order-id', 'status', 'created-date', 'modified-date', 'shipped-date', 'amount', 'tax-amount', 'shipping-amount', 'payment-method', 'currency', 'billing-name', 'billing-phone', 'billing-email', 'billing-address1', 'billing-address2', 'billing-city', 'billing-state', 'billing-zip', 'billing-country');
                            #set orders output csv headers 
                            fputcsv($file, $header_array);
                            #set orders output csv orders details
                            for ($i = 0; $i < count($orders); $i++) {
                                fputcsv($file, array($orders[$i]['id'], $orders[$i]['status'], date('Y-m-d H:i:s', strtotime($orders[$i]['date_created'])), date('Y-m-d H:i:s', strtotime($orders[$i]['date_modified'])), date('Y-m-d H:i:s', strtotime($orders[$i]['date_shipped'])), $orders[$i]['total_inc_tax'], $orders[$i]['total_tax'], $orders[$i]['shipping_cost_inc_tax'], $orders[$i]['payment_method'], $orders[$i]['currency_code'], $orders[$i]['billing_address']['first_name'] . ' ' . $orders[$i]['billing_address']['first_name'], $orders[$i]['billing_address']['phone'], $orders[$i]['billing_address']['email'], $orders[$i]['billing_address']['street_1'], $orders[$i]['billing_address']['street_2'], $orders[$i]['billing_address']['city'], $orders[$i]['billing_address']['state'], $orders[$i]['billing_address']['zip'], $orders[$i]['billing_address']['country']));
                            }

                            if (is_file($path . "/orders.csv")) {
                                #transfer orders output csv from inprogress to complete
                                @copy($path . "/orders.csv", str_replace('inprogress', 'complete', $path) . "/orders.csv");
                                #remove orders output csv from inprogress
                                @unlink($path . "/orders.csv");
                                #transfer orders request csv from inprogress to complete
                                @copy($this->currentCsvProcess, str_replace('inprogress', 'complete', $this->currentCsvProcess));
                                if (is_file(str_replace('inprogress', 'complete', $path) . "/orders.csv")) {
                                    echo str_replace('inprogress', 'complete', $path) . "/orders.csv << order out put csv generated for Bigcommerce <br>";
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            } else if (strpos(dirname($this->temp_client), 'GALR') !== false) {
                #echo $this->temp_client.' << GALR << BCC <br>';
            }
        }


        //echo '<br>'.$this->currentCsvProcess.'<br>';
        //$action[]=$this->getCsvContent();

        /* if(is_array($action)){
          #here need to make actions
          if(strpos($this->temp_client, 'GO') !== false ){
          if($action[0][0] == 'GetNewOrders'){
          #bigcommerce actions
          $Username = 'api_1';
          $path = 'https://store-3a2hr0hsri.mybigcommerce.com/api/v2/';
          $token = '8223bb1d4ea51a2d41cb42b59be9492dd99c0ce3';

          $store = new connection($Username, $path, $token);

          $req = 'orders?sort=date_created:desc';
          $orders = $store->get($req);

          $header_array = array('order-id', 'status', 'created-date', 'modified-date', 'shipped-date', 'amount', 'tax-amount', 'shipping-amount', 'payment-method', 'currency', 'billing-name', 'billing-phone', 'billing-email', 'billing-address1', 'billing-address2', 'billing-city', 'billing-state', 'billing-zip', 'billing-country');

          if(count($orders) > 0){
          $path = str_replace("ProdIn","ProdOut",$this->temp_client);
          $patharr = explode('/', $path);
          array_pop($patharr);
          $path = implode('/', $patharr);
          $file = fopen($path."/inprogress/orders.csv","w");
          fputcsv($file, $header_array);
          for($i=0;$i<count($orders);$i++){
          fputcsv($file, array($orders[$i]['id'], $orders[$i]['status'], date('Y-m-d H:i:s',strtotime($orders[$i]['date_created'])), date('Y-m-d H:i:s',strtotime($orders[$i]['date_modified'])), date('Y-m-d H:i:s',strtotime($orders[$i]['date_shipped'])), $orders[$i]['total_inc_tax'], $orders[$i]['total_tax'], $orders[$i]['shipping_cost_inc_tax'], $orders[$i]['payment_method'], $orders[$i]['currency_code'], $orders[$i]['billing_address']['first_name'].' '.$orders[$i]['billing_address']['first_name'], $orders[$i]['billing_address']['phone'], $orders[$i]['billing_address']['email'], $orders[$i]['billing_address']['street_1'], $orders[$i]['billing_address']['street_2'], $orders[$i]['billing_address']['city'], $orders[$i]['billing_address']['state'], $orders[$i]['billing_address']['zip'], $orders[$i]['billing_address']['country']));
          }

          if(is_file($path."/inprogress/orders.csv")){
          @copy($path."/inprogress/orders.csv",$path."/complete/orders.csv");
          @unlink($path."/inprogress/orders.csv");

          @copy($this->currentCsvProcess,str_replace('inprogress', 'complete', $this->currentCsvProcess));
          }
          }
          }
          }else if(strpos($this->temp_client, 'GALR') !== false){
          #bigcommerce actions
          $Username = 'api_1';
          $path = 'https://store-3a2hr0hsri.mybigcommerce.com/api/v2/';
          $token = '8223bb1d4ea51a2d41cb42b59be9492dd99c0ce3';

          $store = new connection($Username, $path, $token);

          $req = 'products';
          $products = $store->get($req);

          $header_array = array('product-id', 'sku', 'name', 'created-date', 'price');

          if(count($products) > 0){
          $path = str_replace("ProdIn","ProdOut",$this->temp_client);
          $patharr = explode('/', $path);
          array_pop($patharr);
          $path = implode('/', $patharr);
          $file = fopen($path."/inprogress/GALR-RCR_get_ALR.csv","w");
          fputcsv($file, $header_array);
          for($i=0;$i<count($products);$i++){
          fputcsv($file, array($products[$i]['id'], $products[$i]['sku'], $products[$i]['name'], date('Y-m-d H:i:s',strtotime($products[$i]['date_created'])), $products[$i]['price']));
          }

          if(is_file($path."/inprogress/GALR-RCR_get_ALR.csv")){
          @copy($path."/inprogress/GALR-RCR_get_ALR.csv",$path."/complete/GALR-RCR_get_ALR.csv");
          @unlink($path."/inprogress/orders.csv");
          }
          }
          }

          } */
    }

    function getCsvContent() {
        $action = array();
        $row = 1;
        if (($handle = fopen($this->currentCsvProcess, "r")) !== FALSE) {
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

    private function dirList($path) {
        $files = scandir($path);
        foreach ($files as $item) {
            if ($item == '..' || $item == '.')
                continue;

            if (is_dir($path . '/' . $item)) {
                $this->dirctory_schema[$path] = $item;
                $this->dirList($path . '/' . $item); // recurssion 
            } else {
                if (is_file($path . DS . $item)) {
                    //$this->dirctory_schema[$path] = $item;
                    if (strpos($item, ".csv") !== false) {
                        #if(strtolower(basename(dirname($path.DS.$item)))=='inprogress'){
                        #$this->csv_file_schema[$path]=$item;//only inprogress CSV       
                        #}

                        if (strpos(strtolower(dirname($path . DS . $item)), 'prodin/go/inprogress') !== false || strpos(strtolower(dirname($path . DS . $item)), 'prodin/galr/inprogress') !== false || strpos(strtolower(dirname($path . DS . $item)), 'prodin/slr/inprogress') !== false) {
                            $this->csv_file_schema[$path] = $item; //only inprogress CSV              
                        }
                        $this->all_csv_file_schema[$path] = $item;
                    }
                }
            }
        }
    }

    function getAllClientCsvList() {
        // all list of csv with path of directory 
    }

}

$csvHandler = new CsvParser();
$csvHandler->processCsv($panel_path);


echo 'success';

exit;
?>