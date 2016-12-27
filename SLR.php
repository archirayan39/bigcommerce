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
                    "managing_stock" => true,
                    "stock_quantity" => $request_csv_details[$r][5],
                    "short_description" => $request_csv_details[$r][7],
                    "description" => $request_csv_details[$r][8],
                    "dimensions" => array("length" => $request_csv_details[$r][10], "width" => $request_csv_details[$r][11], "height" => $request_csv_details[$r][12])
                );
                
                $woo_prod_avail = 'No';

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
} catch (Exception $e) {
    echo $e->getMessage(); // Error message.
    echo $e->getRequest(); // Last request data.
    echo $e->getResponse(); // Last response data.
}

?>