<?php      
  //header('Content-Type: application/json');
  require_once("connection.php"); 
  
  #/api/v2/orders?sort=date_created:desc&limit=25 
  
  $Username = 'api_1';
  $path = 'https://store-3a2hr0hsri.mybigcommerce.com/api/v2/';
  $token = '8223bb1d4ea51a2d41cb42b59be9492dd99c0ce3';
  
  $store = new connection($Username, $path, $token);
  
	$req = 'orders?sort=date_created:desc'; 
	$orders = $store->get($req); 
  
  $header_array = array('order-id', 'order-item- id', 'purchase-date', 'payments-date', 'buyer-email', 'buyer-name', 'buyer-phone-number', 'sku', 'variation-sku', 'product-name', 'quantity-purchased', 'currency', 'item-price', 'item-tax', 'shipping-price', 'shipping-tax', 'ship-service-level', 'recipient-name');
  
  if(count($orders) > 0){
    // output headers so that the file is downloaded rather than displayed
    #header('Content-Type: text/csv; charset=utf-8');
    #header('Content-Disposition: attachment; filename=data.csv');
    
    // create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    
    // output the column headings
    #fputcsv($output, $header_array);   
  
    for($i=0;$i<count($orders);$i++){       
      echo "<pre>";
      print_r($orders[$i]); 
      
      echo "<br>----------------- Order Products -------------------<br>"; 
    
      $order_products = $store->get(substr($orders[$i]['products']['resource'], 1));
      
      echo "<pre>";
        print_r($order_products);
      
      if(count($order_products) > 0){
        #echo "<pre>";
        #print_r($order_products);
        #for($k=0;$k<count($order_products);$k++){
          #fputcsv($output, array($order_products[$k]['order_id'], $order_products[$k]['product_id'], $orders[$i]['date_created'], '', $orders[$i]['billing_address']['email'], $orders[$i]['billing_address']['first_name'].' '.$orders[$i]['billing_address']['last_name'], $orders[$i]['billing_address']['phone'], $order_products[$k]['sku'], '', $order_products[$k]['name'], $order_products[$k]['quantity'], $orders[$i]['currency_code'], $order_products[$k]['total_ex_tax'], $order_products[$k]['total_tax'], '', '', '', ''));  
        #}
      }
      
      echo "<hr>";       
    }   
  }
  
  #echo "<pre>";
  #print_r($orders);      
?>