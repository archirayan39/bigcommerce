<?php 

 Class Bigcommerce
  {       
    public function go_process($Username, $pathm, $token){   echo 'dsdsdsds'; exit;   
   // $store = new connection($Username, $path, $token);
    
  	/*$req = 'orders?sort=date_created:desc'; 
  	$orders = $store->get($req); 
    
    $header_array = array('order-id', 'order-item- id', 'purchase-date', 'payments-date', 'buyer-email', 'buyer-name', 'buyer-phone-number', 'sku', 'variation-sku', 'product-name', 'quantity-purchased', 'currency', 'item-price', 'item-tax', 'shipping-price', 'shipping-tax', 'ship-service-level', 'recipient-name');
    
    if(count($orders) > 0){
      $path = str_replace("ProdIn","ProdOut",$this->temp_client);  
      $patharr = explode('/', $path); 
      array_pop($patharr); 
      $path = implode('/', $patharr);              
      $file = fopen($path."/inprogress/orders.csv","w");  
      
      // output the column headings
      fputcsv($file, $header_array);   
    
      for($i=0;$i<count($orders);$i++){       
        $order_products = $store->get(substr($orders[$i]['products']['resource'], 1));
        
        if(count($order_products) > 0){
          #echo "<pre>";
          #print_r($order_products);
          for($k=0;$k<count($order_products);$k++){
            fputcsv($file, array($order_products[$k]['order_id'], $order_products[$k]['product_id'], $orders[$i]['date_created'], '', $orders[$i]['billing_address']['email'], $orders[$i]['billing_address']['first_name'].' '.$orders[$i]['billing_address']['last_name'], $orders[$i]['billing_address']['phone'], $order_products[$k]['sku'], '', $order_products[$k]['name'], $order_products[$k]['quantity'], $orders[$i]['currency_code'], $order_products[$k]['total_ex_tax'], $order_products[$k]['total_tax'], '', '', '', ''));  
          }
        }       
      }  
       
    } */
  
  } 
  }

  

?>